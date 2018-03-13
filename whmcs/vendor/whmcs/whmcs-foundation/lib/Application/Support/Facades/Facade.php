<?php 
namespace WHMCS\Application\Support\Facades;


class Facade extends \Illuminate\Support\Facades\Facade
{
    public static function self()
    {
        return static::getFacadeRoot();
    }

}


