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

$sendInvoice = App::get_req_var("sendinvoice");
$paymentMethod = App::get_req_var("paymentmethod");
$status = App::get_req_var("status");
$createAsDraft = (bool) App::get_req_var("draft");
$invoiceStatuses = WHMCS\Invoices::getInvoiceStatusValues();
$defaultStatus = "Unpaid";
$doprocesspaid = false;
$result = select_query("tblclients", "id", array( "id" => $_POST["userid"] ));
$data = mysql_fetch_array($result);
if( !$data["id"] ) 
{
    $apiresults = array( "result" => "error", "message" => "Client ID Not Found" );
}
else
{
    if( $createAsDraft && $sendInvoice ) 
    {
        $apiresults = array( "result" => "error", "message" => "Cannot create and send a draft invoice in a single API request. Please create and send separately." );
    }
    else
    {
        $taxrate = $_POST["taxrate"];
        $taxrate2 = $_POST["taxrate2"];
        if( WHMCS\Config\Setting::getValue("TaxEnabled") && !$taxrate && !$taxrate2 ) 
        {
            $clientsdetails = getClientsDetails($_POST["userid"]);
            if( !$clientsdetails["taxexempt"] ) 
            {
                $state = $clientsdetails["state"];
                $country = $clientsdetails["country"];
                $taxdata = getTaxRate(1, $state, $country);
                $taxdata2 = getTaxRate(2, $state, $country);
                $taxrate = $taxdata["rate"];
                $taxrate2 = $taxdata2["rate"];
            }

        }

        if( $createAsDraft ) 
        {
            $status = "Draft";
        }
        else
        {
            if( !in_array($status, $invoiceStatuses) ) 
            {
                $status = $defaultStatus;
            }

        }

        $invoiceid = insert_query("tblinvoices", array( "date" => $_POST["date"], "duedate" => $_POST["duedate"], "userid" => $_POST["userid"], "status" => $status, "taxrate" => $taxrate, "taxrate2" => $taxrate2, "paymentmethod" => $_POST["paymentmethod"], "notes" => $_POST["notes"] ));
        WHMCS\Invoices::adjustIncrementForNextInvoice($invoiceid);
        $invoiceArr = array( "source" => "api", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => $status );
        run_hook("InvoiceCreation", $invoiceArr);
        foreach( $_POST as $k => $v ) 
        {
            if( substr($k, 0, 10) == "itemamount" ) 
            {
                $counter = substr($k, 10);
                $description = $_POST["itemdescription" . $counter];
                $amount = $_POST["itemamount" . $counter];
                $taxed = $_POST["itemtaxed" . $counter];
                if( $description ) 
                {
                    insert_query("tblinvoiceitems", array( "invoiceid" => $invoiceid, "userid" => $userid, "description" => $description, "amount" => $amount, "taxed" => $taxed ));
                }

            }

        }
        updateInvoiceTotal($invoiceid);
        $invoiceArr = array( "source" => "api", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid );
        run_hook("InvoiceCreation", $invoiceArr);
        if( isset($autoapplycredit) && $autoapplycredit ) 
        {
            $result = select_query("tblclients", "credit", array( "id" => $userid ));
            $data = mysql_fetch_array($result);
            $credit = $data["credit"];
            $result = select_query("tblinvoices", "total", array( "id" => $invoiceid ));
            $data = mysql_fetch_array($result);
            $total = $data["total"];
            if( 0 < $credit ) 
            {
                if( $total <= $credit ) 
                {
                    $creditleft = $credit - $total;
                    $credit = $total;
                    $doprocesspaid = true;
                }
                else
                {
                    $creditleft = 0;
                }

                logActivity("Credit Automatically Applied at Invoice Creation - Invoice ID: " . $invoiceid . " - Amount: " . $credit, $userid);
                update_query("tblclients", array( "credit" => $creditleft ), array( "id" => $userid ));
                update_query("tblinvoices", array( "credit" => $credit ), array( "id" => $invoiceid ));
                insert_query("tblcredit", array( "clientid" => $userid, "date" => "now()", "description" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $credit * -1 ));
                updateInvoiceTotal($invoiceid);
            }

        }

        if( $sendInvoice ) 
        {
            run_hook("InvoiceCreationPreEmail", $invoiceArr);
            $where = array( "gateway" => $paymentMethod, "setting" => "type" );
            $result = select_query("tblpaymentgateways", "value", $where);
            $data = mysql_fetch_array($result);
            $paymentType = $data["value"];
            $emailTemplate = ($paymentType == "CC" || $paymentType == "OfflineCC" ? "Credit Card Invoice Created" : "Invoice Created");
            $template = WHMCS\Mail\Template::where("name", $emailTemplate)->get()->first();
            sendMessage($template, $invoiceid);
        }

        if( $status != "Draft" ) 
        {
            WHMCS\Invoice::saveClientSnapshotData($invoiceid);
            run_hook("InvoiceCreated", $invoiceArr);
        }

        if( $doprocesspaid ) 
        {
            processPaidInvoice($invoiceid);
        }

        $apiresults = array( "result" => "success", "invoiceid" => $invoiceid, "status" => $status );
    }

}


