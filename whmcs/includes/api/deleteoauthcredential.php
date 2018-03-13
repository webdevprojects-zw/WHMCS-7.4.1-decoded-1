<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$credentialId = (int) $whmcs->getFromRequest("credentialId");
$client = WHMCS\ApplicationLink\Client::find($credentialId);
if( is_null($client) ) 
{
    $apiresults = array( "result" => "error", "message" => "Invalid Credential ID provided." );
}
else
{
    $client->delete();
    $apiresults = array( "result" => "success", "credentialId" => $credentialId );
}


