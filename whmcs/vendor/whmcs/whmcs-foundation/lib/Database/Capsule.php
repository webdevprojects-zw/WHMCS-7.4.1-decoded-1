<?php 
namespace WHMCS\Database;


class Capsule extends \Illuminate\Database\Capsule\Manager
{
    public static function getInstance()
    {
        return static::$instance;
    }

}


