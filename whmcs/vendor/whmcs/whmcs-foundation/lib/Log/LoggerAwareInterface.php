<?php 
namespace WHMCS\Log;


interface LoggerAwareInterface extends \Psr\Log\LoggerAwareInterface
{
    public function getLogger();

}


