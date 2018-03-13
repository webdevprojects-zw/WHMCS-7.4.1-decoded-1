<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getAdminHomeStats") ) 
{
    require(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php");
}

$stats = getAdminHomeStats("api");
$apiresults = array( "result" => "success" );
foreach( $stats["income"] as $k => $v ) 
{
    $apiresults["income_" . $k] = $v;
}
$result = select_query("tblorders", "COUNT(*)", array( "status" => "Pending" ));
$data = mysql_fetch_array($result);
$apiresults["orders_pending"] = $data[0];
foreach( $stats["orders"]["today"] as $k => $v ) 
{
    $apiresults["orders_today_" . $k] = $v;
}
foreach( $stats["orders"]["yesterday"] as $k => $v ) 
{
    $apiresults["orders_yesterday_" . $k] = $v;
}
$apiresults["orders_thismonth_total"] = $stats["orders"]["thismonth"]["total"];
$apiresults["orders_thisyear_total"] = $stats["orders"]["thisyear"]["total"];
foreach( $stats["tickets"] as $k => $v ) 
{
    if( is_array($v) ) 
    {
        $v = $v["count"];
    }

    $apiresults["tickets_" . $k] = $v;
}
$apiresults["cancellations_pending"] = $stats["cancellations"]["pending"];
$apiresults["todoitems_due"] = $stats["todoitems"]["due"];
$apiresults["networkissues_open"] = $stats["networkissues"]["open"];
$apiresults["billableitems_uninvoiced"] = $stats["billableitems"]["uninvoiced"];
$apiresults["quotes_valid"] = $stats["quotes"]["valid"];
$result = select_query("tbladminlog", "COUNT(DISTINCT adminusername)", "lastvisit>='" . date("Y-m-d H:i:s", mktime(date("H"), date("i") - 15, date("s"), date("m"), date("d"), date("Y"))) . "' AND logouttime='0000-00-00'");
$data = mysql_fetch_array($result);
$apiresults["staff_online"] = $data[0];
if( $iphone ) 
{
    if( defined("IPHONELICENSE") ) 
    {
        exit( "License Hacking Attempt Detected" );
    }

    global $licensing;
    define("IPHONELICENSE", $licensing->isActiveAddon("iPhone App"));
    $apiresults["iphone"] = IPHONELICENSE;
}


