<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$gateway = WHMCS\Module\Gateway::factory("paypalexpress");
$gatewayParams = $gateway->getParams();
$token = "";
if( isset($_REQUEST["token"]) ) 
{
    $token = $_REQUEST["token"];
}

if( !$token ) 
{
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Missing Token");
    exit();
}

$postfields = array(  );
$postfields["TOKEN"] = $token;
$results = paypalexpress_api_call($gatewayParams, "GetExpressCheckoutDetails", $postfields);
$ack = strtoupper($results["ACK"]);
if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" ) 
{
    logTransaction($GATEWAY["paymentmethod"], $results, "Successful");
    $email = $results["EMAIL"];
    $payerId = $results["PAYERID"];
    $payerStatus = $results["PAYERSTATUS"];
    $salutation = $results["SALUTATION"];
    $firstName = $results["FIRSTNAME"];
    $middleName = $results["MIDDLENAME"];
    $lastName = $results["LASTNAME"];
    $suffix = $results["SUFFIX"];
    $cntryCode = $results["COUNTRYCODE"];
    $business = $results["BUSINESS"];
    $shipToName = $results["PAYMENTREQUEST_0_SHIPTONAME"];
    $shipToStreet = $results["PAYMENTREQUEST_0_SHIPTOSTREET"];
    $shipToStreet2 = $results["PAYMENTREQUEST_0_SHIPTOSTREET2"];
    $shipToCity = $results["PAYMENTREQUEST_0_SHIPTOCITY"];
    $shipToState = $results["PAYMENTREQUEST_0_SHIPTOSTATE"];
    $shipToCntryCode = $results["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"];
    $shipToZip = $results["PAYMENTREQUEST_0_SHIPTOZIP"];
    $addressStatus = $results["ADDRESSSTATUS"];
    $invoiceNumber = $results["INVNUM"];
    $phonNumber = $results["PHONENUM"];
    $_SESSION["paypalexpress"]["payerid"] = $payerId;
    if( $_SESSION["uid"] ) 
    {
        redirSystemURL("a=checkout", "cart.php");
    }

    $is_registered = get_query_val("tblclients", "id", array( "email" => $email ));
    if( $is_registered ) 
    {
        redirSystemURL("a=login", "cart.php");
    }

    $_SESSION["cart"]["user"] = array( "firstname" => $firstName, "lastname" => $lastName, "companyname" => $business, "email" => $email, "address1" => $shipToStreet, "address2" => $shipToStreet2, "city" => $shipToCity, "state" => $shipToState, "postcode" => $shipToZip, "country" => $shipToCntryCode, "phonenumber" => $phonNumber );
    redirSystemURL("a=checkout", "cart.php");
}
else
{
    logTransaction($GATEWAY["paymentmethod"], $results, "Error");
    echo "An Error Occurred. Please contact support.";
}


