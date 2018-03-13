<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$notes = array(  );
$result = select_query("tblticketnotes", "id,admin,date,message", array( "ticketid" => $ticketid ), "date", "ASC");
while( $data = mysql_fetch_assoc($result) ) 
{
    $notes[] = $data;
}
$apiresults = array( "result" => "success", "totalresults" => count($notes), "notes" => array( "note" => $notes ) );
$responsetype = "xml";

