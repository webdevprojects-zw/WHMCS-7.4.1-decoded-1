<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("ServerSuspendAccount") ) 
{
    require(ROOTDIR . "/includes/modulefunctions.php");
}

$result = select_query("tblhosting", "packageid", array( "id" => $_POST["accountid"] ));
$data = mysql_fetch_array($result);
$packageid = $data["packageid"];
$result = ServerSuspendAccount($_POST["accountid"], $_POST["suspendreason"]);
if( $result == "success" ) 
{
    $apiresults = array( "result" => "success" );
}
else
{
    $apiresults = array( "result" => "error", "message" => $result );
}


