<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getRegistrarConfigOptions") ) 
{
    require(ROOTDIR . "/includes/registrarfunctions.php");
}

if( !function_exists("ModuleBuildParams") ) 
{
    require(ROOTDIR . "/includes/modulefunctions.php");
}

if( !function_exists("changeOrderStatus") ) 
{
    require(ROOTDIR . "/includes/orderfunctions.php");
}

$result = select_query("tblorders", "", array( "id" => $orderid ));
$data = mysql_fetch_array($result);
$orderid = $data["id"];
if( !$orderid ) 
{
    $apiresults = array( "result" => "error", "message" => "Order ID Not Found" );
}
else
{
    changeOrderStatus($orderid, "Pending");
    $apiresults = array( "result" => "success" );
}


