<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("cyberbit");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

$hash = $_REQUEST["Hash"];
$xml = $_REQUEST["xml"];
$invoiceid = $OrderId = $_REQUEST["OrderId"];
$StatusCode = $_REQUEST["StatusCode"];
$StatusText = $_REQUEST["StatusText"];
$Time = $_REQUEST["Time"];
$invoiceid = explode("-", $invoiceid);
$invoiceid = $invoiceid[1];
$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
$fingerprint = sha1($StatusCode . $StatusText . $OrderId . $Time . $GATEWAY["hashkey"]);
if( $fingerprint != $hash ) 
{
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Invalid Hash");
    redirSystemURL("id=" . $invoiceid . "&paymentfailed=true", "viewinvoice.php");
}

$redirectUrl = "id=" . $invoiceid . "&paymentfailed=true";
$transactionStatus = "Unsuccessful";
if( $StatusCode == "000" ) 
{
    addInvoicePayment($invoiceid, $OrderId, "", "", "cyberbit");
    $result = select_query("tblinvoices", "userid", array( "id" => $invoiceid ));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    update_query("tblclients", array( "gatewayid" => $OrderId ), array( "id" => $userid ));
    $redirectUrl = "id=" . $invoiceid . "&paymentsuccess=true";
    $transactionStatus = "Successful";
}

logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
redirSystemURL($redirectUrl, "viewinvoice.php");

