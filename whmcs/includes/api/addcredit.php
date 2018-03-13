<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !$amount || !is_numeric($amount) ) 
{
    $apiresults = array( "result" => "error", "message" => "No Amount Provided" );
}
else
{
    $result = select_query("tblclients", "id", array( "id" => $clientid ));
    $data = mysql_fetch_array($result);
    if( !$data["id"] ) 
    {
        $apiresults = array( "result" => "error", "message" => "Client ID Not Found" );
    }
    else
    {
        insert_query("tblcredit", array( "clientid" => $clientid, "date" => "now()", "description" => $description, "amount" => $amount ));
        update_query("tblclients", array( "credit" => "+=" . $amount ), array( "id" => (int) $clientid ));
        $currency = getCurrency($clientid);
        logActivity("Added Credit - User ID: " . $clientid . " - Amount: " . formatCurrency($amount), $clientid);
        $result = select_query("tblclients", "", array( "id" => $clientid ));
        $data = mysql_fetch_array($result);
        $creditbalance = $data["credit"];
        $apiresults = array( "result" => "success", "newbalance" => $creditbalance );
    }

}


