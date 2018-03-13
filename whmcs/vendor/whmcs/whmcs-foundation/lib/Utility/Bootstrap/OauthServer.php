<?php 
namespace WHMCS\Utility\Bootstrap;


class OauthServer extends Application
{
    public static function boot(\WHMCS\Config\RuntimeStorage $preBootInstances = NULL)
    {
        parent::boot($preBootInstances);
        \Di::make("app");
    }

}


