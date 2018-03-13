<?php 
if( !defined("WHMCS") && !isset($_REQUEST["runcharge"]) ) 
{
    exit( "This file cannot be accessed directly" );
}

$GATEWAYMODULE["secpayname"] = "secpay";
$GATEWAYMODULE["secpayvisiblename"] = "SecPay";
$GATEWAYMODULE["secpaytype"] = "CC";
if( isset($_REQUEST["runcharge"]) ) 
{
    require("../../init.php");
    $whmcs->load_function("gateway");
    $GATEWAY = getGatewayVariables("secpay");
    if( !$GATEWAY["type"] ) 
    {
        exit( "Module Not Activated" );
    }

    $verifyhash = $_REQUEST["verifyhash"];
    $verifyhash2 = $whmcs->get_hash() . $_REQUEST["merchantid"] . $_REQUEST["invoiceid"] . $_REQUEST["amount"];
    $verifyhash2 = sha1($verifyhash2);
    if( $verifyhash != $verifyhash2 ) 
    {
        exit( "?error=Hash Verification Failed" );
    }

    require("../../includes/xmlrpc.php");
    $testmode = $_REQUEST["testmode"];
    if( !$testmode ) 
    {
        $testmode = "live";
    }

    $repeattrans = "";
    if( !$_REQUEST["cardcvv"] ) 
    {
        $repeattrans = "usage_type=R,repeat=true,";
    }

    $f = new xmlrpcmsg("SECVPN.validateCardFull");
    $f->addParam(new xmlrpcval($_REQUEST["merchantid"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["vpnpassword"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["invoiceid"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["ipaddress"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["name"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["cardnum"], "string"));
    $f->addParam(new xmlrpcval($_REQUEST["amount"], "string"));
    $f->addParam(new xmlrpcval(substr($_REQUEST["cardexp"], 0, 2) . "/" . substr($_REQUEST["cardexp"], 2, 2), "string"));
    $f->addParam(new xmlrpcval($_REQUEST["issuenum"], "string"));
    $f->addParam(new xmlrpcval(substr($_REQUEST["startdate"], 0, 2) . "/" . substr($_REQUEST["startdate"], 2, 2), "string"));
    $f->addParam(new xmlrpcval("", "string"));
    $f->addParam(new xmlrpcval("", "string"));
    $f->addParam(new xmlrpcval("name=" . $_REQUEST["clientdetailsfirstname"] . " " . $_REQUEST["clientdetailslastname"] . ",company=" . $_REQUEST["clientdetailscompanyname"] . ",addr_1=" . $_REQUEST["clientdetailsaddress1"] . ",addr_2=" . $_REQUEST["clientdetailsaddress2"] . ",city=" . $_REQUEST["clientdetailscity"] . ",state=" . $_REQUEST["clientdetailstate"] . ",post_code=" . $_REQUEST["clientdetailspostcode"] . ",tel=" . $_REQUEST["clientdetailsphonenumber"] . ",email=" . $_REQUEST["clientdetailsemail"] . "", "string"));
    $f->addParam(new xmlrpcval($repeattrans . "test_status=" . $testmode . ",dups=false,currency=" . $_REQUEST["currencycode"] . ",cv2=" . $_REQUEST["cardcvv"], "string"));
    $c = new xmlrpc_client("/secxmlrpc/make_call", "www.secpay.com", 443);
    $c->setSSLVerifyHost(0);
    $c->setSSLVerifyPeer(0);
    $r = $c->send($f, 20, "https");
    $v = $r->value();
    $faultcode = $r->faultCode();
    $faultreason = $r->faultString();
    if( $faultcode ) 
    {
        echo "?FaultCode=" . $faultcode . "&FaultReason=" . $faultreason;
    }
    else
    {
        $result = $v->scalarval();
        $result2 = htmlentities($r->serialize());
        echo $result;
    }

}

function secpay_activate()
{
    defineGatewayField("secpay", "text", "merchantid", "", "Merchant ID", "20", "");
    defineGatewayField("secpay", "text", "vpnpassword", "", "VPN Password", "20", "");
    defineGatewayField("secpay", "yesno", "testmode", "", "Test Mode", "", "");
}

function secpay_capture($params)
{
    global $whmcs;
    $url = $params["systemurl"] . "/modules/gateways/secpay.php";
    $postfields["runcharge"] = "true";
    $postfields["merchantid"] = $params["merchantid"];
    $postfields["vpnpassword"] = $params["vpnpassword"];
    $postfields["invoiceid"] = $params["invoiceid"];
    $postfields["name"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $postfields["cardnum"] = $params["cardnum"];
    $postfields["amount"] = $params["amount"];
    $postfields["cardexp"] = $params["cardexp"];
    $postfields["cardcvv"] = $params["cccvv"];
    $postfields["issuenum"] = $params["cardissuenum"];
    $postfields["startdate"] = $params["cardstart"];
    $postfields["currencycode"] = $params["currency"];
    $postfields["clientdetailsfirstname"] = $params["clientdetails"]["firstname"];
    $postfields["clientdetailslastname"] = $params["clientdetails"]["lastname"];
    $postfields["clientdetailscompanyname"] = $params["clientdetails"]["companyname"];
    $postfields["clientdetailsaddress1"] = $params["clientdetails"]["address1"];
    $postfields["clientdetailsaddress2"] = $params["clientdetails"]["address2"];
    $postfields["clientdetailscity"] = $params["clientdetails"]["city"];
    $postfields["clientdetailsstate"] = $params["clientdetails"]["state"];
    $postfields["clientdetailspostcode"] = $params["clientdetails"]["postcode"];
    $postfields["clientdetailsphonenumber"] = $params["clientdetails"]["phonenumber"];
    $postfields["clientdetailsemail"] = $params["clientdetails"]["email"];
    $postfields["ipaddress"] = $_SERVER["REMOTE_ADDR"];
    if( $params["testmode"] ) 
    {
        $postfields["testmode"] = "true";
    }

    $verifyhash = $whmcs->get_hash() . $params["merchantid"] . $params["invoiceid"] . $params["amount"];
    $verifyhash = sha1($verifyhash);
    $postfields["verifyhash"] = $verifyhash;
    $poststring = "";
    foreach( $postfields as $k => $v ) 
    {
        $poststring .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $poststring);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    if( curl_errno($ch) ) 
    {
        $data = "?ErrorNumber=" . curl_errno($ch) . "&ErrorMessage=CURL Error " . curl_error($ch);
    }

    curl_close($ch);
    $tempresultarray = substr($data, 1);
    $tempresultarray = str_replace("+", " ", $tempresultarray);
    $tempresultarray = explode("&", $tempresultarray);
    foreach( $tempresultarray as $tempvalue ) 
    {
        $tempvalue = explode("=", $tempvalue);
        $resultsarray[$tempvalue[0]] = $tempvalue[1];
    }
    $valid = $resultsarray["valid"];
    $code = $resultsarray["code"];
    $transid = $resultsarray["trans_id"];
    $authcode = $resultsarray["auth_code"];
    if( $code == "A" ) 
    {
        return array( "status" => "success", "transid" => $authcode, "rawdata" => $resultsarray );
    }

    return array( "status" => "declined", "rawdata" => $resultsarray );
}


