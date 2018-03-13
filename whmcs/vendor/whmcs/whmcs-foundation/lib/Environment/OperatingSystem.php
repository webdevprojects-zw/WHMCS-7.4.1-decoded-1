<?php 
namespace WHMCS\Environment;


class OperatingSystem
{
    public static function isWindows($phpOs = PHP_OS)
    {
        return in_array($phpOs, array( "Windows", "WIN32", "WINNT" ));
    }

    public function isOwnedByMe($path)
    {
        return fileowner($path) == Php::getUserRunningPhp();
    }

}


