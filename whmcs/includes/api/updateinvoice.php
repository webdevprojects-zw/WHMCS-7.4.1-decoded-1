<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getClientsDetails") ) 
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}

if( !function_exists("updateInvoiceTotal") ) 
{
    require(ROOTDIR . "/includes/invoicefunctions.php");
}

$publish = App::get_req_var("publish");
$publishAndSendEmail = App::get_req_var("publishandsendemail");
$invoiceid = (int) App::getFromRequest("invoiceid");
$result = select_query("tblinvoices", "id, userid", array( "id" => $invoiceid ));
$data = mysql_fetch_array($result);
$invoiceid = $data["id"];
$userid = $data["userid"];
if( !$invoiceid ) 
{
    $apiresults = array( "result" => "error", "message" => "Invoice ID Not Found" );
}
else
{
    if( $itemdescription ) 
    {
        foreach( $itemdescription as $lineid => $description ) 
        {
            $amount = $itemamount[$lineid];
            $taxed = $itemtaxed[$lineid];
            $update = array( "userid" => $userid, "description" => $description, "amount" => $amount, "taxed" => $taxed );
            update_query("tblinvoiceitems", $update, array( "id" => $lineid ));
        }
    }

    if( $newitemdescription ) 
    {
        foreach( $newitemdescription as $k => $v ) 
        {
            $description = $v;
            $amount = $newitemamount[$k];
            $taxed = $newitemtaxed[$k];
            $insert = array( "invoiceid" => $invoiceid, "userid" => $userid, "description" => $description, "amount" => $amount, "taxed" => $taxed );
            insert_query("tblinvoiceitems", $insert);
        }
    }

    if( $deletelineids ) 
    {
        foreach( $deletelineids as $lineid ) 
        {
            delete_query("tblinvoiceitems", array( "id" => $lineid, "invoiceid" => $invoiceid ));
        }
    }

    updateInvoiceTotal($invoiceid);
    $updateqry = array(  );
    if( $invoicenum ) 
    {
        $updateqry["invoicenum"] = $invoicenum;
    }

    if( $date ) 
    {
        $updateqry["date"] = $date;
    }

    if( $duedate ) 
    {
        $updateqry["duedate"] = $duedate;
    }

    if( $datepaid ) 
    {
        $updateqry["datepaid"] = $datepaid;
    }

    if( $subtotal ) 
    {
        $updateqry["subtotal"] = $subtotal;
    }

    if( $credit ) 
    {
        $updateqry["credit"] = $credit;
    }

    if( $tax ) 
    {
        $updateqry["tax"] = $tax;
    }

    if( $tax2 ) 
    {
        $updateqry["tax2"] = $tax2;
    }

    if( $total ) 
    {
        $updateqry["total"] = $total;
    }

    if( $taxrate ) 
    {
        $updateqry["taxrate"] = $taxrate;
    }

    if( $taxrate2 ) 
    {
        $updateqry["taxrate2"] = $taxrate2;
    }

    if( $status ) 
    {
        if( in_array($status, WHMCS\Invoices::getInvoiceStatusValues()) ) 
        {
            $updateqry["status"] = $status;
        }
        else
        {
            $apiresults = array( "result" => "error", "message" => "Invalid status " . $status );
            return NULL;
        }

    }

    if( $paymentmethod ) 
    {
        $updateqry["paymentmethod"] = $paymentmethod;
    }

    if( $notes ) 
    {
        $updateqry["notes"] = $notes;
    }

    if( 0 < count($updateqry) ) 
    {
        update_query("tblinvoices", $updateqry, array( "id" => $invoiceid ));
    }

    if( $publish || $publishAndSendEmail ) 
    {
        $invoiceArr = array( "source" => "api", "user" => (WHMCS\Session::get("adminid") ?: "system"), "invoiceid" => $invoiceid );
        run_hook("InvoiceCreation", $invoiceArr);
        if( !$paymentmethod ) 
        {
            $paymentmethod = getClientsPaymentMethod($userid);
        }

        $paymentType = Illuminate\Database\Capsule\Manager::table("tblpaymentgateways")->where("setting", "type")->where("gateway", $paymentmethod)->value("value");
        Illuminate\Database\Capsule\Manager::table("tblinvoices")->where("id", $invoiceid)->update(array( "date" => Carbon\Carbon::now()->toDateString(), "status" => "Unpaid" ));
        updateInvoiceTotal($invoiceid);
        WHMCS\Invoice::saveClientSnapshotData($invoiceid);
        logActivity("Modified Invoice Options - Invoice ID: " . $invoiceid, $userid);
        if( $publishAndSendEmail ) 
        {
            run_hook("InvoiceCreationPreEmail", $invoiceArr);
            $emailName = (($paymentType == "CC" || $paymentType == "OfflineCC" ? "Credit Card " : "")) . "Invoice Created";
            sendMessage($emailName, $invoiceid);
            run_hook("InvoiceCreated", $invoiceArr);
        }

    }

    $apiresults = array( "result" => "success", "invoiceid" => $invoiceid );
}


