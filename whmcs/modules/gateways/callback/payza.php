<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("payza");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

if( $GATEWAY["testmode"] == "on" ) 
{
    $ipnv2handlerurl = "https://sandbox.payza.com/sandbox/ipn2.ashx";
}
else
{
    $ipnv2handlerurl = "https://secure.payza.com/ipn2.ashx";
}

$token = "token=" . urlencode($_POST["token"]);
$response = "";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ipnv2handlerurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);
if( 0 < strlen($response) ) 
{
    if( urldecode($response) == "INVALID TOKEN" ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Invalid Token");
        exit();
    }

    $response = urldecode($response);
    $aps = explode("&", $response);
    foreach( $aps as $ap ) 
    {
        $ele = explode("=", $ap);
        $info[$ele[0]] = $ele[1];
    }
    $result = select_query("tblcurrencies", "", array( "code" => $info["ap_currency"] ));
    $data = mysql_fetch_array($result);
    $currencyid = $data["id"];
    if( !$currencyid ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $response, "Unrecognised Currency");
        exit();
    }

    if( $info["ap_status"] == "Success" ) 
    {
        $_REQUEST = $info;
        $invoiceid = checkCbInvoiceID($info["apc_1"], $GATEWAY["paymentmethod"]);
        $transid = $info["ap_referencenumber"];
        checkCbTransID($transid);
        $amount = $info["ap_netamount"] + $info["ap_feeamount"];
        $fees = $info["ap_feeamount"];
        $where = array( "id" => $invoiceid );
        $result = select_query("tblinvoices", "userid,total", $where);
        $data = mysql_fetch_array($result);
        $userid = $data["userid"];
        $total = $data["total"];
        $currency = getCurrency($userid);
        if( $currencyid != $currency["id"] ) 
        {
            $amount = convertCurrency($amount, $currencyid, $currency["id"]);
            $fees = convertCurrency($fees, $currencyid, $currency["id"]);
            if( $total < $amount + 1 && $amount - 1 < $total ) 
            {
                $amount = $total;
            }

        }

        addInvoicePayment($invoiceid, $transid, $amount, $fees, "Payza");
        $resultStatus = "Successful";
        if( $amount != $info["ap_totalamount"] ) 
        {
            $resultStatus = "Successful with amount mismatch";
            $response .= "&whmcs_notification_message=Payza total not equal to net + fees!";
        }

        logTransaction($GATEWAY["paymentmethod"], $response, $resultStatus);
    }
    else
    {
        logTransaction($GATEWAY["paymentmethod"], $response, "Unsuccessful");
    }

}
else
{
    logTransaction($GATEWAY["paymentmethod"], $response, "No response received from Payza");
}


