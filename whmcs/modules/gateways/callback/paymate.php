<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
try
{
    $GATEWAY = getGatewayVariables("paymateau");
}
catch( WHMCS\Exception $e ) 
{
    $GATEWAY = getGatewayVariables("paymatenz");
}
$invoiceid = checkCbInvoiceID($_POST["ref"], $GATEWAY["paymentmethod"]);
$redirectUrl = "id=" . $invoiceid . "&paymentfailed=true";
$transactionStatus = "Error";
if( $_POST["responseCode"] == "PA" && $invoiceid ) 
{
    addInvoicePayment($invoiceid, $_POST["transactionID"], "", "", "paymate");
    $redirectUrl = "id=" . $invoiceid . "&paymentsuccess=true";
    $transactionStatus = "Successful";
}

logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
redirSystemURL($redirectUrl, "viewinvoice.php");

