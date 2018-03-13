<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("paypal");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

$postipn = "cmd=_notify-validate";
$orgipn = "";
foreach( $_POST as $key => $value ) 
{
    $orgipn .= (string) $key . " => " . $value . "\n";
    $postipn .= "&" . $key . "=" . urlencode(WHMCS\Input\Sanitize::decode($value));
}
if( $GATEWAY["sandbox"] ) 
{
    $url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
}
else
{
    $url = "https://www.paypal.com/cgi-bin/webscr";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postipn);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 100);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "WHMCS V" . $whmcs->getVersion()->getCasual());
$reply = curl_exec($ch);
curl_close($ch);
if( !strcmp($reply, "VERIFIED") ) 
{
    $paypalemail = $_POST["receiver_email"];
    $payment_status = $_POST["payment_status"];
    $subscr_id = $_POST["subscr_id"];
    $txn_type = $_POST["txn_type"];
    $txn_id = $_POST["txn_id"];
    $mc_gross = $_POST["mc_gross"];
    $mc_fee = $_POST["mc_fee"];
    $idnumber = $_POST["custom"];
    $paypalcurrency = $_REQUEST["mc_currency"];
    $upgradeInvoice = false;
    if( substr($idnumber, 0, 1) == "U" ) 
    {
        $idnumber = (int) substr($idnumber, 1);
        $upgradeInvoice = true;
    }

    $paypalemails = explode(",", strtolower($GATEWAY["email"]));
    array_walk($paypalemails, "paypal_email_trim");
    if( !in_array(strtolower($paypalemail), $paypalemails) ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "Invalid Receiver Email");
        exit();
    }

    if( $payment_status == "Pending" ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "Pending");
        exit();
    }

    if( $txn_id ) 
    {
        checkCbTransID($txn_id);
    }

    if( !is_numeric($idnumber) ) 
    {
        $idnumber = "";
    }

    if( $txn_type == "web_accept" && $_POST["invoice"] && $payment_status == "Completed" ) 
    {
        update_query("tblaccounts", array( "fees" => $mc_fee ), array( "transid" => $txn_id ));
    }

    $result = select_query("tblcurrencies", "", array( "code" => $paypalcurrency ));
    $data = mysql_fetch_array($result);
    $paypalcurrencyid = $data["id"];
    $currencyconvrate = $data["rate"];
    if( !$paypalcurrencyid ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "Unrecognised Currency");
        exit();
    }

    switch( $txn_type ) 
    {
        case "subscr_signup":
            logTransaction($GATEWAY["paymentmethod"], $orgipn, "Subscription Signup");
            exit();
        case "subscr_cancel":
            update_query("tblhosting", array( "subscriptionid" => "" ), array( "subscriptionid" => $subscr_id ));
            logTransaction($GATEWAY["paymentmethod"], $orgipn, "Subscription Cancelled");
            exit();
        case "subscr_payment":
            if( $payment_status != "Completed" ) 
            {
                logTransaction($GATEWAY["paymentmethod"], $orgipn, "Incomplete");
                exit();
            }

            if( $upgradeInvoice ) 
            {
                $upgradeID = get_query_val("tblupgrades", "id", array( "relid" => $idnumber, "paid" => "N" ));
                $query = "SELECT tblinvoices.id, tblinvoices.userid FROM tblinvoiceitems\nINNER JOIN tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid\nWHERE tblinvoiceitems.relid=" . $upgradeID . " AND tblinvoiceitems.type='Upgrade' AND tblinvoices.status='Unpaid'\nORDER BY tblinvoices.id ASC;";
                $result = full_query($query);
                $data = mysql_fetch_array($result);
                $invoiceid = $data["id"];
                $userid = $data["userid"];
                if( $invoiceid ) 
                {
                    $orgipn .= "Invoice Found from Upgrade ID Match => " . $invoiceid . "\n";
                }

            }
            else
            {
                $invoiceitemsInvoiceIds = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("relid", $idnumber)->where("type", "Hosting")->lists("invoiceid");
                if( $invoiceitemsInvoiceIds ) 
                {
                    $firstUnpaidInvoice = WHMCS\Database\Capsule::table("tblinvoices")->where("status", "Unpaid")->whereIn("id", $invoiceitemsInvoiceIds)->orderBy("id", "asc")->first(array( "id", "userid" ));
                    $invoiceid = $firstUnpaidInvoice->id;
                    $userid = $firstUnpaidInvoice->userid;
                }
                else
                {
                    $invoiceid = NULL;
                    $userid = NULL;
                }

                if( $invoiceid ) 
                {
                    $orgipn .= "Invoice Found from Product ID Match => " . $invoiceid . "\n";
                }
                else
                {
                    $subscr_id = db_escape_string($subscr_id);
                    $query = "SELECT tblinvoiceitems.invoiceid,tblinvoices.userid FROM tblhosting\nINNER JOIN tblinvoiceitems ON tblhosting.id=tblinvoiceitems.relid\nINNER JOIN tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid\nWHERE tblinvoices.status='Unpaid' AND tblhosting.subscriptionid='" . $subscr_id . "'\n    AND tblinvoiceitems.type='Hosting'\nORDER BY tblinvoiceitems.invoiceid ASC;";
                    $result = full_query($query);
                    $data = mysql_fetch_array($result);
                    $invoiceid = $data["invoiceid"];
                    $userid = $data["userid"];
                    if( $invoiceid ) 
                    {
                        $orgipn .= "Invoice Found from Subscription ID Match => " . $invoiceid . "\n";
                    }

                }

                if( !$invoiceid ) 
                {
                    $invoiceitemsInvoiceIds = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("relid", $idnumber)->where("type", "Hosting")->lists("invoiceid");
                    if( $invoiceitemsInvoiceIds ) 
                    {
                        $lastPaidInvoice = WHMCS\Database\Capsule::table("tblinvoices")->where("status", "Paid")->whereIn("id", $invoiceitemsInvoiceIds)->orderBy("id", "desc")->first(array( "id", "userid" ));
                        $invoiceid = $lastPaidInvoice->id;
                        $userid = $lastPaidInvoice->userid;
                    }
                    else
                    {
                        $invoiceid = NULL;
                        $userid = NULL;
                    }

                    if( $invoiceid ) 
                    {
                        $orgipn .= "Paid Invoice Found from Product ID Match => " . $invoiceid . "\n";
                    }

                }

            }

            break;
        case "web_accept":
            if( $payment_status != "Completed" ) 
            {
                logTransaction($GATEWAY["paymentmethod"], $orgipn, "Incomplete");
                exit();
            }

            $result = select_query("tblinvoices", "", array( "id" => $idnumber ));
            $data = mysql_fetch_array($result);
            $invoiceid = $data["id"];
            $userid = $data["userid"];
            break;
    }
    if( !$txn_type && $payment_status == "Reversed" ) 
    {
        $originalTransactionId = App::getFromRequest("parent_txn_id");
        try
        {
            paymentReversed($txn_id, $originalTransactionId, 0, "paypal");
            logTransaction("PayPal", $orgipn, "Payment Reversed");
        }
        catch( Exception $e ) 
        {
            logTransaction("PayPal", $orgipn, "Payment Reversal Could Not Be Completed: " . $e->getMessage());
        }
        exit();
    }

    if( $invoiceid ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "Successful");
        $currency = getCurrency($userid);
        if( $paypalcurrencyid != $currency["id"] ) 
        {
            $mc_gross = convertCurrency($mc_gross, $paypalcurrencyid, $currency["id"]);
            $mc_fee = convertCurrency($mc_fee, $paypalcurrencyid, $currency["id"]);
            $result = select_query("tblinvoices", "total", array( "id" => $invoiceid ));
            $data = mysql_fetch_array($result);
            $total = $data["total"];
            if( $total < $mc_gross + 1 && $mc_gross - 1 < $total ) 
            {
                $mc_gross = $total;
            }

        }

        addInvoicePayment($invoiceid, $txn_id, $mc_gross, $mc_fee, "paypal");
        $relid = get_query_val("tblinvoiceitems", "relid", array( "invoiceid" => $invoiceid, "type" => "Hosting" ));
        if( $upgradeInvoice && !empty($upgradeID) ) 
        {
            $relid = get_query_val("tblupgrades", "relid", array( "id" => $upgradeID ));
        }

        if( $relid ) 
        {
            update_query("tblhosting", array( "subscriptionid" => $subscr_id ), array( "id" => $relid ));
        }

        exit();
    }

    if( $txn_type == "subscr_payment" ) 
    {
        $result = select_query("tblhosting", "userid", array( "subscriptionid" => $subscr_id ));
        $data = mysql_fetch_array($result);
        $userid = $data["userid"];
        if( $userid ) 
        {
            $orgipn .= "User ID Found from Subscription ID Match: User ID => " . $userid . "\n";
            insert_query("tblaccounts", array( "userid" => $userid, "currency" => $paypalcurrencyid, "gateway" => "paypal", "date" => "now()", "description" => "PayPal Subscription Payment", "amountin" => $mc_gross, "fees" => $mc_fee, "rate" => $currencyconvrate, "transid" => $txn_id ));
            insert_query("tblcredit", array( "clientid" => $userid, "date" => "now()", "description" => "PayPal Subscription Transaction ID " . $txn_id, "amount" => $mc_gross ));
            update_query("tblclients", array( "credit" => "+=" . $mc_gross ), array( "id" => (int) $userid ));
            logTransaction($GATEWAY["paymentmethod"], $orgipn, "Credit Added");
        }
        else
        {
            logTransaction($GATEWAY["paymentmethod"], $orgipn, "Invoice Not Found");
        }

    }
    else
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "Not Supported");
    }

}
else
{
    if( !strcmp($reply, "INVALID") ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $orgipn, "IPN Handshake Invalid");
        header("HTTP/1.0 406 Not Acceptable");
        exit();
    }

    logTransaction($GATEWAY["paymentmethod"], $orgipn . "\n\nIPN Handshake Response => " . $reply, "IPN Handshake Error");
    header("HTTP/1.0 406 Not Acceptable");
    exit();
}

function paypal_email_trim(&$value)
{
    $value = trim($value);
}


