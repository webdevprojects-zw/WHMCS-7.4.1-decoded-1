<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$GATEWAYMODULE["ntpnowname"] = "ntpnow";
$GATEWAYMODULE["ntpnowvisiblename"] = "Payment Leaf";
$GATEWAYMODULE["ntpnowtype"] = "CC";
function ntpnow_activate()
{
    defineGatewayField("ntpnow", "text", "merchantid", "", "Merchant ID", "20", "");
}

function ntpnow_capture($params)
{
    $url = "https://ntpnow.com/NTPnow_V3_interface.asp";
    $fields["NTPNowID"] = $params["merchantid"];
    $fields["Amount"] = $params["amount"];
    $fields["NameOnCard"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $fields["Street"] = $params["clientdetails"]["address1"];
    $fields["City"] = $params["clientdetails"]["city"];
    $fields["State"] = $params["clientdetails"]["state"];
    $fields["Zip"] = $params["clientdetails"]["postcode"];
    $fields["CreditCardNumber"] = $params["cardnum"];
    $fields["Month"] = substr($params["cardexp"], 0, 2);
    $fields["Year"] = substr($params["cardexp"], 2, 2);
    $fields["AVS"] = "True";
    if( $params["cccvv"] ) 
    {
        $fields["CVV2"] = "True";
        $fields["CVV2Number"] = $params["cccvv"];
    }

    $fields["OrderNumber"] = $params["invoiceid"];
    $poststring = "";
    foreach( $fields as $k => $v ) 
    {
        $poststring .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $poststring);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    curl_close($ch);
    $responseText = explode("|", $result);
    foreach( $responseText as $k ) 
    {
        $result1 = explode(":", $k);
        $resultsarray[$result1[0]] = $result1[1];
    }
    $desc = "Action => Auth_Capture\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\n";
    foreach( $resultsarray as $k => $v ) 
    {
        $desc .= (string) $k . " => " . $v . "\n";
    }
    if( $resultsarray["STATUS"] == "TRANSACTION SUCCESSFUL" ) 
    {
        return array( "status" => "success", "transid" => $resultsarray["Approval Code"], "rawdata" => $desc );
    }

    return array( "status" => "declined", "rawdata" => $desc );
}


