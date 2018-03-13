<?php 

class Plesk_Config
{
    private static $_settings = NULL;

    private static function _init()
    {
        if( !is_null(static::$_settings) ) 
        {
            return NULL;
        }

        static::$_settings = json_decode(json_encode(array_merge(static::getDefaults(), static::_getConfigFileSettings())));
    }

    public static function get()
    {
        self::_init();
        return static::$_settings;
    }

    public static function getDefaults()
    {
        return array( "account_limit" => 0 );
    }

    private static function _getConfigFileSettings()
    {
        $filename = dirname(dirname(dirname(__FILE__))) . "/config.ini";
        if( !file_exists($filename) ) 
        {
            return array(  );
        }

        $result = parse_ini_file($filename, true);
        return (!$result ? array(  ) : $result);
    }

}


