<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getGatewaysArray") ) 
{
    require(ROOTDIR . "/includes/gatewayfunctions.php");
}

$paymentmethods = getGatewaysArray();
$apiresults = array( "result" => "success", "totalresults" => count($paymentmethods) );
foreach( $paymentmethods as $module => $name ) 
{
    $apiresults["paymentmethods"]["paymentmethod"][] = array( "module" => $module, "displayname" => $name );
}
$responsetype = "xml";

