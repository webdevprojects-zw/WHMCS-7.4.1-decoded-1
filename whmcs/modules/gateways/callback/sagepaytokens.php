<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("sagepaytokens");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

if( $protxsimmode ) 
{
    $url = "https://test.sagepay.com/simulator/VSPDirectCallback.asp";
}
else
{
    if( $GATEWAY["testmode"] ) 
    {
        $url = "https://test.sagepay.com/gateway/service/direct3dcallback.vsp";
    }
    else
    {
        $url = "https://live.sagepay.com/gateway/service/direct3dcallback.vsp";
    }

}

$response = sagepaytokens_call($url, $_POST);
$baseStatus = $response["Status"];
$invoiceid = $_REQUEST["invoiceid"];
if( !$invoiceid && isset($_SESSION["sagepaytokensinvoiceid"]) ) 
{
    $invoiceid = $_SESSION["sagepaytokensinvoiceid"];
}

$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
$callbacksuccess = false;
$email = "Credit Card Payment Failed";
switch( $response["Status"] ) 
{
    case "OK":
        checkCbTransID($response["VPSTxId"]);
        addInvoicePayment($invoiceid, $response["VPSTxId"], "", "", "sagepaytokens", "on");
        $transactionStatus = "Successful";
        $email = "Credit Card Payment Confirmation";
        logTransaction($GATEWAY["paymentmethod"], $response, "Successful");
        sendMessage("Credit Card Payment Confirmation", $invoiceid);
        $callbacksuccess = true;
        break;
    case "NOTAUTHED":
        $transactionStatus = "Not Authed";
        break;
    case "REJECTED":
        $transactionStatus = "Rejected";
        break;
    case "FAIL":
        $transactionStatus = "Failed";
        break;
    default:
        $transactionStatus = "Error";
        break;
}
logTransaction($GATEWAY["paymentmethod"], $response, $transactionStatus);
sendMessage($email, $invoiceid);
if( !$callbacksuccess ) 
{
    $userid = (int) get_query_val("tblinvoices", "userid", array( "id" => $invoiceid ));
    update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "" ), array( "id" => $userid ));
}

callback3DSecureRedirect($invoiceid, $callbacksuccess);

