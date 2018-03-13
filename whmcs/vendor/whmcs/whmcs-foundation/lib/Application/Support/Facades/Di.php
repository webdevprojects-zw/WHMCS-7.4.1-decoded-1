<?php 
namespace WHMCS\Application\Support\Facades;


class Di extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return "di";
    }

}


