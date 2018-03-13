<?php 
if( !class_exists("WHMCS\\Module\\Autoloader") ) 
{
    include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php");
}
else
{
    WHMCS\Module\Autoloader::register();
}


