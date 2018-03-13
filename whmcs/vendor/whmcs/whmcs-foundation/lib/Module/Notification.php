<?php 
namespace WHMCS\Module;


class Notification extends AbstractModule
{
    protected $type = "notifications";

    public function getClassPath()
    {
        $module = $this->getLoadedModule();
        return "WHMCS\\Module\\Notification\\" . $module . "\\" . $module;
    }

}


