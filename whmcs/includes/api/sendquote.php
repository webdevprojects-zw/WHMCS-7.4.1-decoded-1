<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getClientsDetails") ) 
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}

if( !function_exists("updateInvoiceTotal") ) 
{
    require(ROOTDIR . "/includes/invoicefunctions.php");
}

if( !function_exists("sendQuotePDF") ) 
{
    require(ROOTDIR . "/includes/quotefunctions.php");
}

$result = select_query("tblquotes", "", array( "id" => $quoteid ));
$data = mysql_fetch_array($result);
$quoteid = $data["id"];
if( !$quoteid ) 
{
    $apiresults = array( "result" => "error", "message" => "Quote ID Not Found" );
}
else
{
    sendQuotePDF($quoteid);
    $apiresults = array( "result" => "success" );
}


