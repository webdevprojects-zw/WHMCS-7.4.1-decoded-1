<?php 
require_once(__DIR__ . DIRECTORY_SEPARATOR . "functions.php");
if( !defined("PROXY_FILE") ) 
{
    try
    {
        $path = getWhmcsInitPath();
    }
    catch( Exception $e ) 
    {
        echo cronsFormatOutput(getInitPathErrorMessage());
        exit( 1 );
    }
    require_once($path);
}


