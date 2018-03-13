<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("paymex");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

$invoiceid = checkCbInvoiceID($_GET["xinv"], $GATEWAY["paymentmethod"]);
$redirectUrl = "id=" . $invoiceid . "&paymentfailed=true";
$transactionStatus = "Unsuccessful";
if( $_GET["xresp"] == "1" ) 
{
    $result = select_query("tblinvoices", "total", array( "id" => $invoiceid ));
    $data = mysql_fetch_array($result);
    $total = $data["total"];
    $fee = $total * 0.0295 + 0.55;
    $pos = strpos($fee, ".");
    $pos = $pos + 3;
    $fee = substr($fee, 0, $pos);
    addInvoicePayment($invoiceid, $invoiceid, "", $fee, "paymex");
    $redirectUrl = "id=" . $invoiceid . "&paymentsuccess=true";
    $transactionStatus = "Successful";
}

logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
redirSystemURL($redirectUrl, "viewinvoice.php");

