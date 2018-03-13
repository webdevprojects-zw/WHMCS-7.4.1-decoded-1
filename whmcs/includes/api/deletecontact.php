<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$result = select_query("tblcontacts", "id", array( "id" => $contactid ));
$data = mysql_fetch_array($result);
if( !$data["id"] ) 
{
    $apiresults = array( "result" => "error", "message" => "Contact ID Not Found" );
}
else
{
    delete_query("tblcontacts", array( "id" => $contactid ));
    $apiresults = array( "result" => "success", "contactid" => $contactid );
}


