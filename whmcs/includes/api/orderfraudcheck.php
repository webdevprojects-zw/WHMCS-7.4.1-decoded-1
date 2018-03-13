<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getClientsDetails") ) 
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}

$result = select_query("tblorders", "id,userid,ipaddress,invoiceid", array( "id" => $orderid ));
$data = mysql_fetch_array($result);
$orderid = $data[0];
if( !$orderid ) 
{
    $apiresults = array( "result" => "error", "message" => "Order ID Not Found" );
    return false;
}

$userid = $data["userid"];
$ipaddress = $data["ipaddress"];
$invoiceid = $data["invoiceid"];
if( isset($_REQUEST["ipaddress"]) ) 
{
    $ipaddress = $_REQUEST["ipaddress"];
}

$fraudmodule = "maxmind";
$results = $fraudresults = "";
$fraud = new WHMCS\Module\Fraud();
if( $fraud->load($fraudmodule) ) 
{
    $results = $fraud->doFraudCheck($orderid, $userid, $ipaddress);
    $fraudresults = $fraud->processResultsForDisplay($orderid, $results["fraudoutput"]);
}

if( !is_array($results) ) 
{
    $results = array(  );
}

$error = $results["error"];
if( $results["userinput"] ) 
{
    $status = "User Input Required";
}
else
{
    if( $results["error"] ) 
    {
        $status = "Fail";
        update_query("tblorders", array( "status" => "Fraud" ), array( "id" => $orderid ));
        $result = select_query("tblhosting", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tblhosting", array( "domainstatus" => "Fraud" ), array( "id" => $data["id"], "domainstatus" => "Pending" ));
        }
        $result = select_query("tblhostingaddons", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tblhostingaddons", array( "status" => "Fraud" ), array( "id" => $data["id"], "status" => "Pending" ));
        }
        $result = select_query("tbldomains", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tbldomains", array( "status" => "Fraud" ), array( "id" => $data["id"], "status" => "Pending" ));
        }
        update_query("tblinvoices", array( "status" => "Cancelled" ), array( "id" => $invoiceid, "status" => "Unpaid" ));
    }
    else
    {
        $status = "Pass";
        update_query("tblorders", array( "status" => "Pending" ), array( "id" => $orderid ));
        $result = select_query("tblhosting", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tblhosting", array( "domainstatus" => "Pending" ), array( "id" => $data["id"], "domainstatus" => "Fraud" ));
        }
        $result = select_query("tblhostingaddons", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tblhostingaddons", array( "status" => "Pending" ), array( "id" => $data["id"], "status" => "Fraud" ));
        }
        $result = select_query("tbldomains", "id", array( "orderid" => $orderid ));
        while( $data = mysql_fetch_array($result) ) 
        {
            update_query("tbldomains", array( "status" => "Pending" ), array( "id" => $data["id"], "status" => "Fraud" ));
        }
        update_query("tblinvoices", array( "status" => "Unpaid" ), array( "id" => $invoiceid, "status" => "Cancelled" ));
    }

}

$apiresults = array( "result" => "success", "status" => $status, "results" => serialize($fraudresults) );
$responsetype = "xml";

