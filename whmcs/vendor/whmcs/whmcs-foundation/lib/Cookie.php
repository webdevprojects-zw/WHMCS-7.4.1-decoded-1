<?php 
namespace WHMCS;


class Cookie
{
    public function __construct()
    {
    }

    public static function get($name, $treatAsArray = false)
    {
        $val = (array_key_exists("WHMCS" . $name, $_COOKIE) ? $_COOKIE["WHMCS" . $name] : "");
        if( $treatAsArray ) 
        {
            $val = json_decode(base64_decode($val), true);
            $val = (is_array($val) ? htmlspecialchars_array($val) : array(  ));
        }

        return $val;
    }

    public static function set($name, $value, $expires = 0, $secure = false)
    {
        if( is_array($value) ) 
        {
            $value = base64_encode(json_encode($value));
        }

        if( !is_numeric($expires) ) 
        {
            if( substr($expires, -1) == "m" ) 
            {
                $expires = time() + substr($expires, 0, -1) * 30 * 24 * 60 * 60;
            }
            else
            {
                $expires = 0;
            }

        }

        return setcookie("WHMCS" . $name, $value, $expires, "/", null, $secure, true);
    }

    public static function delete($name)
    {
        unset($_COOKIE["WHMCS" . $name]);
        return self::set($name, null, -86400);
    }

}


