<?php 
namespace WHMCS\Application\Support\Facades;


class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "app";
    }

}


