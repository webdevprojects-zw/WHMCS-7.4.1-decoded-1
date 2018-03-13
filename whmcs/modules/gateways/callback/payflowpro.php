<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$whmcs->load_function("client");
$whmcs->load_function("cc");
$gateway = WHMCS\Module\Gateway::factory("payflowpro");
$gatewayParams = $gateway->getParams();
$callbacksuccess = false;
$pares = $_REQUEST["PaRes"];
$invoiceid = $_REQUEST["MD"];
if( strcasecmp("", $pares) != 0 && $pares != NULL && isset($_SESSION["Centinel_TransactionId"]) ) 
{
    if( $gatewayParams["testmode"] ) 
    {
        $mapurl = "https://centineltest.cardinalcommerce.com/maps/txns.asp";
    }
    else
    {
        $mapurl = "https://paypal.cardinalcommerce.com/maps/txns.asp";
    }

    $currency = "";
    if( $gatewayParams["currency"] == "USD" ) 
    {
        $currency = "840";
    }

    if( $gatewayParams["currency"] == "GBP" ) 
    {
        $currency = "826";
    }

    if( $gatewayParams["currency"] == "EUR" ) 
    {
        $currency = "978";
    }

    if( $gatewayParams["currency"] == "CAD" ) 
    {
        $currency = "124";
    }

    $postfields = array(  );
    $postfields["MsgType"] = "cmpi_authenticate";
    $postfields["Version"] = "1.7";
    $postfields["ProcessorId"] = $gatewayParams["processorid"];
    $postfields["MerchantId"] = $gatewayParams["merchantid"];
    $postfields["TransactionPwd"] = $gatewayParams["transpw"];
    $postfields["TransactionType"] = "C";
    $postfields["PAResPayload"] = $pares;
    $postfields["OrderId"] = $_SESSION["Centinel_OrderId"];
    $postfields["TransactionId"] = $_SESSION["Centinel_TransactionId"];
    $queryString = "<CardinalMPI>\n";
    foreach( $postfields as $name => $value ) 
    {
        $queryString .= "<" . $name . ">" . $value . "</" . $name . ">\n";
    }
    $queryString .= "</CardinalMPI>";
    $data = "cmpi_msg=" . urlencode($queryString);
    $response = curlCall($mapurl, $data);
    $xmlarray = XMLtoArray($response);
    $xmlarray = $xmlarray["CARDINALMPI"];
    $errorno = $xmlarray["ERRORNO"];
    $paresstatus = $xmlarray["PARESSTATUS"];
    $sigverification = $xmlarray["SIGNATUREVERIFICATION"];
    $cavv = $xmlarray["CAVV"];
    $eciflag = $xmlarray["ECIFLAG"];
    $xid = $xmlarray["XID"];
    if( (strcasecmp("0", $errorno) == 0 || strcasecmp("1140", $errorno) == 0) && strcasecmp("Y", $sigverification) == 0 && (strcasecmp("Y", $paresstatus) == 0 || strcasecmp("A", $paresstatus) == 0) ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Auth Passed");
        $auth = array( "paresstatus" => $paresstatus, "cavv" => $cavv, "eciflag" => $eciflag, "xid" => $xid );
        $params = getCCVariables($invoiceid);
        if( isset($_SESSION["Centinel_Details"]) ) 
        {
            $params["cardtype"] = $_SESSION["Centinel_Details"]["cardtype"];
            $params["cardnum"] = $_SESSION["Centinel_Details"]["cardnum"];
            $params["cardexp"] = $_SESSION["Centinel_Details"]["cardexp"];
            $params["cccvv"] = $_SESSION["Centinel_Details"]["cccvv"];
            $params["cardstart"] = $_SESSION["Centinel_Details"]["cardstart"];
            $params["cardissuenum"] = $_SESSION["Centinel_Details"]["cardissuenum"];
            unset($_SESSION["Centinel_Details"]);
        }

        $result = payflowpro_capture($params, $auth);
        if( $result["status"] == "success" ) 
        {
            logTransaction($GATEWAY["paymentmethod"], $result["rawdata"], "Successful");
            addInvoicePayment($invoiceid, $result["transid"], "", "", "payflowpro", "on");
            sendMessage("Credit Card Payment Confirmation", $invoiceid);
            $callbacksuccess = true;
        }
        else
        {
            logTransaction($GATEWAY["paymentmethod"], $result["rawdata"], "Failed");
        }

    }
    else
    {
        if( strcasecmp("N", $paresstatus) == 0 ) 
        {
            logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Auth Failed");
        }
        else
        {
            logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Unexpected Status, Capture Anyway");
            $auth = array( "paresstatus" => $paresstatus, "cavv" => $cavv, "eciflag" => $eciflag, "xid" => $xid );
            $params = getCCVariables($invoiceid);
            if( isset($_SESSION["Centinel_Details"]) ) 
            {
                $params["cardtype"] = $_SESSION["Centinel_Details"]["cardtype"];
                $params["cardnum"] = $_SESSION["Centinel_Details"]["cardnum"];
                $params["cardexp"] = $_SESSION["Centinel_Details"]["cardexp"];
                $params["cccvv"] = $_SESSION["Centinel_Details"]["cccvv"];
                $params["cardstart"] = $_SESSION["Centinel_Details"]["cardstart"];
                $params["cardissuenum"] = $_SESSION["Centinel_Details"]["cardissuenum"];
                unset($_SESSION["Centinel_Details"]);
            }

            $result = payflowpro_capture($params, $auth);
            if( $result["status"] == "success" ) 
            {
                logTransaction($GATEWAY["paymentmethod"], $result["rawdata"], "Successful");
                addInvoicePayment($invoiceid, $result["transid"], "", "", "payflowpro", "on");
                sendMessage("Credit Card Payment Confirmation", $invoiceid);
                $callbacksuccess = true;
            }
            else
            {
                logTransaction($GATEWAY["paymentmethod"], $result["rawdata"], "Failed");
            }

        }

    }

}
else
{
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Error");
}

if( !$callbacksuccess ) 
{
    sendMessage("Credit Card Payment Failed", $invoiceid);
}

callback3DSecureRedirect($invoiceid, $callbacksuccess);

