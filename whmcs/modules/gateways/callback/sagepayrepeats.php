<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("sagepayrepeats");
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

$data = "PaRes=" . urlencode($_POST["PaRes"]) . "&MD=" . $_POST["MD"];
$data = sagepayrepeats_formatData($_POST);
$response = sagepayrepeats_requestPost($url, $data);
$baseStatus = $response["Status"];
$transdump = "";
foreach( $response as $key => $value ) 
{
    $transdump .= (string) $key . " => " . $value . "\n";
}
$invoiceid = $_REQUEST["invoiceid"];
if( !$invoiceid && isset($_SESSION["sagepayrepeatsinvoiceid"]) ) 
{
    $invoiceid = $_SESSION["sagepayrepeatsinvoiceid"];
}

$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
$userid = get_query_val("tblinvoices", "userid", array( "id" => $invoiceid ));
$gatewayid = get_query_val("tblclients", "gatewayid", array( "id" => $userid ));
$callbacksuccess = false;
switch( $response["Status"] ) 
{
    case "OK":
        checkCbTransID($response["VPSTxId"]);
        addInvoicePayment($invoiceid, $response["VPSTxId"], "", "", "sagepayrepeats", "on");
        $gatewayid .= $response["VPSTxId"] . "," . $response["SecurityKey"] . "," . $response["TxAuthNo"];
        update_query("tblclients", array( "gatewayid" => $gatewayid, "cardnum" => "" ), array( "id" => $userid ));
        logTransaction($GATEWAY["paymentmethod"], $transdump, "Successful");
        sendMessage("Credit Card Payment Confirmation", $invoiceid);
        $callbacksuccess = true;
        break;
    case "NOTAUTHED":
        logTransaction($GATEWAY["paymentmethod"], $transdump, "Not Authed");
        sendMessage("Credit Card Payment Failed", $invoiceid);
        update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "" ), array( "id" => $userid ));
        break;
    case "REJECTED":
        logTransaction($GATEWAY["paymentmethod"], $transdump, "Rejected");
        sendMessage("Credit Card Payment Failed", $invoiceid);
        update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "" ), array( "id" => $userid ));
        break;
    case "FAIL":
        logTransaction($GATEWAY["paymentmethod"], $transdump, "Failed");
        sendMessage("Credit Card Payment Failed", $invoiceid);
        update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "" ), array( "id" => $userid ));
        break;
    default:
        logTransaction($GATEWAY["paymentmethod"], $transdump, "Error");
        sendMessage("Credit Card Payment Failed", $invoiceid);
        update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "" ), array( "id" => $userid ));
        break;
}
callback3DSecureRedirect($invoiceid, $callbacksuccess);

