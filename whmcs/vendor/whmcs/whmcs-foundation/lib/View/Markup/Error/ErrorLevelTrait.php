<?php 
namespace WHMCS\View\Markup\Error;


trait ErrorLevelTrait
{
    protected $errorLevel = ErrorLevelInterface::ERROR;

    public function isAnError()
    {
        return ErrorLevelInterface::ERROR <= $this->errorLevel;
    }

    public function errorName()
    {
        return ucfirst(strtolower(\Monolog\Logger::getLevelName($this->errorLevel)));
    }

}


