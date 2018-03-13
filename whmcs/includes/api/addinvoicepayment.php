<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("addInvoicePayment") ) 
{
    require(ROOTDIR . "/includes/invoicefunctions.php");
}

$whmcs = WHMCS\Application::getInstance();
$id = (int) $whmcs->get_req_var("invoiceid");
$where = array( "id" => $id );
$result = select_query("tblinvoices", "id", $where);
$data = mysql_fetch_array($result);
$invoiceid = $data["id"];
if( !$invoiceid ) 
{
    $apiresults = array( "result" => "error", "message" => "Invoice ID Not Found" );
}
else
{
    $invoice = new WHMCS\Invoice($invoiceid);
    $invoiceStatus = $invoice->getData("status");
    if( $invoiceStatus == "Cancelled" ) 
    {
        $apiresults = array( "result" => "error", "message" => "It is not possible to add a payment to an invoice that is Cancelled" );
    }
    else
    {
        $date = $whmcs->get_req_var("date");
        $date = ($date ? fromMySQLDate($date) : "");
        $date2 = $whmcs->get_req_var("date2");
        if( $date2 ) 
        {
            $date = fromMySQLDate($date2);
        }

        $transid = $whmcs->get_req_var("transid");
        $amount = $whmcs->get_req_var("amount");
        $fees = $whmcs->get_req_var("fees");
        $gateway = $whmcs->get_req_var("gateway");
        $noemail = $whmcs->get_req_var("noemail");
        addInvoicePayment($invoiceid, $transid, $amount, $fees, $gateway, $noemail, $date);
        $apiresults = array( "result" => "success" );
    }

}


