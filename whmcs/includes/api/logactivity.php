<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$userid = (int) $userid;
logActivity($description, $userid);
$apiresults = array( "result" => "success" );

