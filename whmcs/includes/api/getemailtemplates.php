<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$query = WHMCS\Mail\Template::query();
if( $type ) 
{
    $query->where("type", "=", $type);
}

if( $language ) 
{
    $query->where("language", "=", $language);
}

$templates = $query->orderBy("name")->get();
$apiresults = array( "result" => "success", "totalresults" => $templates->count(), "emailtemplates" => array( "emailtemplate" => array(  ) ) );
foreach( $templates as $template ) 
{
    $apiresults["emailtemplates"]["emailtemplate"][] = array( "id" => $template->id, "name" => $template->name, "subject" => $template->subject, "custom" => (int) $template->custom );
}
$responsetype = "xml";

