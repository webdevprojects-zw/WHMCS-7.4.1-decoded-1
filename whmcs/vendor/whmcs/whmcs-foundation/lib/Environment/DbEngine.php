<?php 
namespace WHMCS\Environment;


class DbEngine
{
    public static function isSupportedByWhmcs($version)
    {
    }

    public static function isSqlStrictMode()
    {
        return \DI::make("db")->isSqlStrictMode();
    }

    public static function getInfo()
    {
        $fullName = \DI::make("db")->getSqlVersionComment();
        if( stripos($fullName, "MariaDB") !== false ) 
        {
            $familyName = "MariaDB";
        }
        else
        {
            if( stripos($fullName, "MySQL") !== false ) 
            {
                $familyName = "MySQL";
            }
            else
            {
                $familyName = "Other";
            }

        }

        $dbVersion = preg_replace("/^([\\d\\.]+)/", "\$1", \DI::make("db")->getSqlVersion());
        return array( "family" => $familyName, "fullName" => $fullName, "version" => $dbVersion );
    }

}


