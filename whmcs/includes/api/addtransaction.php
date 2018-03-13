<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("addTransaction") ) 
{
    require(ROOTDIR . "/includes/invoicefunctions.php");
}

$whmcs = App::self();
if( $userid ) 
{
    $result = select_query("tblclients", "id", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    if( !$data["id"] ) 
    {
        $apiresults = array( "result" => "error", "message" => "Client ID Not Found" );
        return NULL;
    }

}

if( $invoiceid ) 
{
    $result = select_query("tblinvoices", "id", array( "id" => (int) $_POST["invoiceid"] ));
    $data = mysql_fetch_array($result);
    $invoiceid = $data["id"];
    if( !$invoiceid ) 
    {
        $apiresults = array( "result" => "error", "message" => "Invoice ID Not Found" );
        return NULL;
    }

}

if( !$paymentmethod ) 
{
    $apiresults = array( "result" => "error", "message" => "Payment Method is required" );
}
else
{
    if( $transid && !isUniqueTransactionID($transid, $paymentmethod) ) 
    {
        $apiresults = array( "result" => "error", "message" => "Transaction ID must be Unique" );
    }
    else
    {
        $date = $whmcs->get_req_var("date");
        if( empty($date) ) 
        {
            $date = fromMySQLDate(date("Y-m-d H:i:s"));
        }

        addTransaction($userid, $currencyid, $description, $amountin, $fees, $amountout, $paymentmethod, $transid, $invoiceid, $date, "", $rate);
        if( $userid && $credit && (!$invoiceid || $invoiceid == 0) ) 
        {
            if( $transid ) 
            {
                $description .= " (Trans ID: " . $transid . ")";
            }

            insert_query("tblcredit", array( "clientid" => $userid, "date" => toMySQLDate($date), "description" => $description, "amount" => $amountin ));
            update_query("tblclients", array( "credit" => "+=" . $amountin ), array( "id" => (int) $userid ));
        }

        if( 0 < $invoiceid ) 
        {
            $totalPaid = get_query_val("tblaccounts", "SUM(amountin)-SUM(amountout)", array( "invoiceid" => $invoiceid ));
            $invoiceData = get_query_vals("tblinvoices", "status, total", array( "id" => $invoiceid ));
            $balance = $invoiceData["total"] - $totalPaid;
            if( $balance <= 0 && $invoiceData["status"] == "Unpaid" ) 
            {
                processPaidInvoice($invoiceid, "", $date);
            }

        }

        $apiresults = array( "result" => "success" );
    }

}


