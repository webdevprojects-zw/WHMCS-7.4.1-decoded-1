<?php 

class WHMCS_DuoSecurity
{
    public static $deprecationDate = "2016-11-30 00:00:00";

    const DUO_PREFIX = "TX";
    const APP_PREFIX = "APP";
    const AUTH_PREFIX = "AUTH";
    const DUO_EXPIRE = 300;
    const APP_EXPIRE = 3600;
    const IKEY_LEN = 20;
    const SKEY_LEN = 40;
    const AKEY_LEN = 40;
    const ERR_USER = "ERR|The username passed to sign_request() is invalid.";
    const ERR_IKEY = "ERR|The Duo integration key passed to sign_request() is invalid.";
    const ERR_SKEY = "ERR|The Duo secret key passed to sign_request() is invalid.";
    const ERR_AKEY = "ERR|The application secret key passed to sign_request() must be at least 40 characters.";

    private static function sign_vals($key, $vals, $prefix, $expire, $time = NULL)
    {
        $exp = (($time ? $time : time())) + $expire;
        $val = $vals . "|" . $exp;
        $b64 = base64_encode($val);
        $cookie = $prefix . "|" . $b64;
        $sig = hash_hmac("sha1", $cookie, $key);
        return $cookie . "|" . $sig;
    }

    private static function parse_vals($key, $val, $prefix, $ikey, $time = NULL)
    {
        $ts = ($time ? $time : time());
        $parts = explode("|", $val);
        if( count($parts) != 3 ) 
        {
            return NULL;
        }

        list($u_prefix, $u_b64, $u_sig) = $parts;
        $sig = hash_hmac("sha1", $u_prefix . "|" . $u_b64, $key);
        if( hash_hmac("sha1", $sig, $key) != hash_hmac("sha1", $u_sig, $key) ) 
        {
            return NULL;
        }

        if( $u_prefix != $prefix ) 
        {
            return NULL;
        }

        $cookie_parts = explode("|", base64_decode($u_b64));
        if( count($cookie_parts) != 3 ) 
        {
            return NULL;
        }

        list($user, $u_ikey, $exp) = $cookie_parts;
        if( $u_ikey != $ikey ) 
        {
            return NULL;
        }

        if( intval($exp) <= $ts ) 
        {
            return NULL;
        }

        return $user;
    }

    public static function signRequest($ikey, $skey, $akey, $username, $time = NULL)
    {
        if( !isset($username) || strlen($username) == 0 ) 
        {
            return self::ERR_USER;
        }

        if( strpos($username, "|") !== false ) 
        {
            return self::ERR_USER;
        }

        if( !isset($ikey) || strlen($ikey) != self::IKEY_LEN ) 
        {
            return self::ERR_IKEY;
        }

        if( !isset($skey) || strlen($skey) != self::SKEY_LEN ) 
        {
            return self::ERR_SKEY;
        }

        if( !isset($akey) || strlen($akey) < self::AKEY_LEN ) 
        {
            return self::ERR_AKEY;
        }

        $vals = $username . "|" . $ikey;
        $duo_sig = self::sign_vals($skey, $vals, self::DUO_PREFIX, self::DUO_EXPIRE, $time);
        $app_sig = self::sign_vals($akey, $vals, self::APP_PREFIX, self::APP_EXPIRE, $time);
        return $duo_sig . ":" . $app_sig;
    }

    public static function verifyResponse($ikey, $skey, $akey, $sig_response, $time = NULL)
    {
        list($auth_sig, $app_sig) = explode(":", $sig_response);
        $auth_user = self::parse_vals($skey, $auth_sig, self::AUTH_PREFIX, $ikey, $time);
        $app_user = self::parse_vals($akey, $app_sig, self::APP_PREFIX, $ikey, $time);
        if( $auth_user != $app_user ) 
        {
            return NULL;
        }

        return $auth_user;
    }

}

function duosecurity_config()
{
    $whmcs = App::self();
    $existingConfig = safe_unserialize(WHMCS\Config\Setting::getValue("2fasettings"));
    $extraDescription = "";
    $integrationKey = decrypt($existingConfig["modules"]["duosecurity"]["integrationKey"]);
    $secretKey = decrypt($existingConfig["modules"]["duosecurity"]["secretKey"]);
    $apiHostname = $existingConfig["modules"]["duosecurity"]["apiHostname"];
    $isConfigurationCustom = duosecurity_isConfigurationCustom($integrationKey, $secretKey, $apiHostname);
    $isAdminEnabled = $existingConfig["modules"]["duosecurity"]["adminenabled"];
    $isClientEnabled = $existingConfig["modules"]["duosecurity"]["clientenabled"];
    $daysUntilDeprecation = duosecurity_daysUntilDeprecation();
    if( !$isConfigurationCustom && ($isAdminEnabled || $isClientEnabled) ) 
    {
        if( 0 < $daysUntilDeprecation ) 
        {
            $dayOrDays = ($daysUntilDeprecation != 1 ? "days" : "day");
            $extraDescription .= "<div class=\"alert alert-warning\" style=\"margin:10px 0;\">\n    <strong><i class=\"fa fa-exclamation-triangle fa-fw\"></i> Action Required</strong><br />\n    You have " . $daysUntilDeprecation . " " . $dayOrDays . " remaining to configure DuoSecurity account credentials.\n    Failure to do this will result in DuoSecurity Two-Factor Authentication being unavailable.\n    Please act now to avoid interruption in service.\n    <a href=\"http://go.whmcs.com/966/duosecurity-find-out-more\" class=\"alert-link autoLinked\">Learn more &raquo;</a>\n</div>";
        }
        else
        {
            $extraDescription .= "<div class=\"alert alert-danger\" style=\"margin:10px 0;\">\n    <strong><i class=\"fa fa-exclamation-triangle fa-fw\"></i> Action Required</strong><br />\n    You must create and enter DuoSecurity account credentials below to continue using the service.\n    <a href=\"http://go.whmcs.com/966/duosecurity-find-out-more\" class=\"alert-link autoLinked\">Learn more &raquo;</a><br />\n</div>";
        }

    }

    if( !decrypt($existingConfig["modules"]["duosecurity"]["integrationKey"]) && !decrypt($existingConfig["modules"]["duosecurity"]["secretKey"]) && !decrypt($existingConfig["modules"]["duosecurity"]["apiHostname"]) ) 
    {
        $extraDescription .= "<div class=\"alert alert-success\" style=\"margin:10px 0;padding:8px 15px;\">New to Duo Security? " . "<a href=\"http://go.whmcs.com/918/duo-security-signup\" target=\"_blank\" class=\"alert-link\">" . "Click here to create an account</a>" . "</div>";
    }

    $configArray = array( "FriendlyName" => array( "Type" => "System", "Value" => "Duo Security" ), "Description" => array( "Type" => "System", "Value" => "Duo Security enables your users to secure their logins using their smartphones. " . "Authentication options include push notifications, passcodes, text messages and/or phone calls." . $extraDescription ), "integrationKey" => array( "FriendlyName" => "Integration Key", "Type" => "password", "Size" => "25" ), "secretKey" => array( "FriendlyName" => "Secret Key", "Type" => "password", "Size" => "45" ), "apiHostname" => array( "FriendlyName" => "API Hostname", "Type" => "text", "Size" => "45" ) );
    if( !$isConfigurationCustom && -1 < $daysUntilDeprecation ) 
    {
        $licensing = DI::make("license");
        $licenseData = $licensing->getKeyData("configoptions");
        $duoUserLimit = (array_key_exists("Duo Security", $licenseData) ? $licenseData["Duo Security"] : 0);
        $userCount = WHMCS\Database\Capsule::connection()->select("select (select count(id) from tblclients where authmodule = 'duosecurity')" . " + (select count(id) from tbladmins where authmodule = 'duosecurity') as cnt;")[0]->cnt;
        $configArray["UserLimit"] = array( "Type" => "System", "Value" => $duoUserLimit );
        $configArray["User Limit"] = array( "Type" => "System", "Value" => $userCount / $duoUserLimit );
    }

    return $configArray;
}

function duosecurity_activate(array $params)
{
    $existingConfig = safe_unserialize(WHMCS\Config\Setting::getValue("2fasettings"));
    $integrationKey = decrypt($existingConfig["modules"]["duosecurity"]["integrationKey"]);
    $secretKey = decrypt($existingConfig["modules"]["duosecurity"]["secretKey"]);
    $apiHostname = $existingConfig["modules"]["duosecurity"]["apiHostname"];
    $isConfigurationCustom = duosecurity_isConfigurationCustom($integrationKey, $secretKey, $apiHostname);
    $daysUntilDeprecation = duosecurity_daysUntilDeprecation();
    if( !$isConfigurationCustom && -1 < $daysUntilDeprecation ) 
    {
        $licensing = DI::make("license");
        $licenseData = $licensing->getKeyData("configoptions");
        $duoUserLimit = (array_key_exists("Duo Security", $licenseData) ? $licenseData["Duo Security"] : 0);
        $userCount = WHMCS\Database\Capsule::connection()->select("select (select count(id) from tblclients where authmodule = 'duosecurity')" . " + (select count(id) from tbladmins where authmodule = 'duosecurity') as cnt;")[0]->cnt;
        if( $duoUserLimit == 0 ) 
        {
            if( defined("ADMINAREA") ) 
            {
                return "<h2>DuoSecurity Activation Problem</h2>\n<p>\n    This WHMCS license has not had Duo Security Users purchased yet.<br />\n    Please navigate to Setup > Staff Management > Two-Factor Authentication to configure a Duo Security account.\n</p>\n<br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
            }

            return "<h2>DuoSecurity Activation Problem</h2>\n<p>Error Code 101. Cannot continue. Please contact support.</p><br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
        }

        if( $duoUserLimit <= $userCount ) 
        {
            if( defined("ADMINAREA") ) 
            {
                return "<h2>DuoSecurity Activation Problem</h2>\n<p>This WHMCS license has reached the allowed number of Duo Security users.</p>\n<p>Please contact the system administrator to configure a custom Duo Security account.</p><br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
            }

            return "<h2>DuoSecurity Activation Problem</h2>\n<p>Error Code 102. Cannot continue. Please contact support.</p><br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
        }

    }
    else
    {
        if( !$isConfigurationCustom && $daysUntilDeprecation < 0 ) 
        {
            if( defined("ADMINAREA") ) 
            {
                return "<h2>DuoSecurity Activation Problem</h2>\n<p>This WHMCS does not have a custom Duo Security account configured.</p>\n<p>Please navigate to Setup > Staff Management > Two-Factor Authentication to configure a Duo Security account.</p><br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
            }

            return "<h2>DuoSecurity Activation Problem</h2>\n<p>Error Code 103. Cannot continue. Please contact support.</p><br />\n<p align=\"center\"><input type=\"button\" value=\"Close Window\" onclick=\"dialogClose()\" /></p>";
        }

    }

    return array( "completed" => true, "msg" => "You will be asked to configure your Duo Security Two-Factor Authentication the next time you login." );
}

function duosecurity_challenge(array $params)
{
    $whmcs = App::self();
    $appsecretkey = sha1("Duo" . $whmcs->get_hash());
    $username = $params["user_info"]["username"];
    $email = $params["user_info"]["email"];
    $inAdmin = defined("ADMINAREA");
    $integrationkey = (!empty($params["settings"]["integrationKey"]) ? decrypt($params["settings"]["integrationKey"]) : "");
    $secretkey = (!empty($params["settings"]["secretKey"]) ? decrypt($params["settings"]["secretKey"]) : "");
    $integrationkey = ($integrationkey ?: "DILXRHE92017KPRVVM4T");
    $secretkey = ($secretkey ?: "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT");
    $apihostname = (!empty($params["settings"]["apiHostname"]) ? $params["settings"]["apiHostname"] : "api-3ce575d8.duosecurity.com");
    $uid = $username . ":" . $email . ":" . $whmcs->get_license_key();
    $sig_request = WHMCS_DuoSecurity::signRequest($integrationkey, $secretkey, $appsecretkey, $uid);
    if( $sig_request != NULL ) 
    {
        $alert = "";
        if( !duosecurity_isConfigurationCustom($integrationkey, $secretkey, $apihostname) ) 
        {
            $daysUntilDeprecation = duosecurity_daysUntilDeprecation();
            $dayOrDays = ($daysUntilDeprecation != 1 ? "days" : "day");
            $message = "<strong><i class=\"fa fa-exclamation-triangle\"></i> DuoSecurity Action Required</strong><br />\nYou have " . $daysUntilDeprecation . " " . $dayOrDays . " remaining to configure your DuoSecurity account credentials.\nFailure to do this will result in DuoSecurity Two-Factor Authentication being unavailable.\nPlease act now to avoid interruption in service.\n<a href=\"http://go.whmcs.com/966/duosecurity-find-out-more\" class=\"alert-link autoLinked\">Learn more &raquo;</a>";
            switch( true ) 
            {
                case $daysUntilDeprecation <= 30 && 8 <= $daysUntilDeprecation:
                    $alertType = "warning";
                    break;
                case $daysUntilDeprecation <= 7 && 0 <= $daysUntilDeprecation:
                    $alertType = "danger";
                    break;
                case $daysUntilDeprecation < 0:
                    if( $inAdmin ) 
                    {
                        $admin = WHMCS\User\Admin::find(WHMCS\Session::getAndDelete("2faadminid"));
                        $admin->twoFactorAuthData = "";
                        $admin->twoFactorAuthModule = "";
                        $admin->save();
                        $auth = new WHMCS\Auth();
                        $auth->getInfobyID($admin->id);
                        $auth->setSessionVars();
                        $auth->processLogin();
                        if( WHMCS\Session::getAndDelete("2farememberme") ) 
                        {
                            $auth->setRememberMeCookie();
                        }
                        else
                        {
                            $auth->unsetRememberMeCookie();
                        }

                        WHMCS\Session::delete("2faverify");
                        if( $loginUrlRedirect = WHMCS\Session::getAndDelete("admloginurlredirect") ) 
                        {
                            $urlParts = explode("?", $loginUrlRedirect, 2);
                            $filename = (!empty($urlParts[0]) ? $urlParts[0] : "");
                            $qry_string = (!empty($urlParts[1]) ? $urlParts[1] : "");
                        }
                        else
                        {
                            $qry_string = "";
                            $filename = "index.php";
                        }

                        redir($qry_string, $filename);
                    }
                    else
                    {
                        $client = WHMCS\User\Client::find(WHMCS\Session::getAndDelete("2faclientid"));
                        $client->twoFactorAuthData = "";
                        $client->twoFactorAuthModule = "";
                        $client->save();
                        $authentication = new WHMCS\Authentication\Client($client->email, "");
                        $authentication->finalizeLogin();
                        $gotoUrl = WHMCS\Session::getAndDelete("loginurlredirect");
                        $gotoUrl = ($gotoUrl ?: ($gotoUrl = "clientarea.php"));
                        if( in_array(substr($gotoUrl, -15), array( "&incorrect=true", "?incorrect=true" )) ) 
                        {
                            $gotoUrl = substr($gotoUrl, 0, strlen($gotoUrl) - 15);
                        }

                        if( in_array(substr($gotoUrl, -28), array( "&incorrect=true&backupcode=1", "?incorrect=true&backupcode=1", "&backupcode=1&incorrect=true", "?backupcode=1&incorrect=true" )) ) 
                        {
                            $gotoUrl = substr($gotoUrl, 0, strlen($gotoUrl) - 28);
                        }

                        $urlParts = explode("?", $gotoUrl, 2);
                        $gotoUrl = (!empty($urlParts[0]) ? $urlParts[0] : "clientarea.php");
                        $qry_string = (!empty($urlParts[1]) ? $urlParts[1] : "");
                        redir($qry_string, $gotoUrl);
                    }

                    break;
                default:
                    $alertType = "";
                    $message = "";
            }
            if( $message && $inAdmin ) 
            {
                $alert = "<div class=\"alert alert-" . $alertType . "\" role=\"alert\">\n" . $message . "\n</div>";
            }

        }

        $output = "<script src=\"" . (($inAdmin ? "../" : "")) . "modules/security/duosecurity/Duo-Web-v2.min.js\"></script>\n<script>\n  Duo.init({\n    \"host\": \"" . $apihostname . "\",\n    \"sig_request\": \"" . $sig_request . "\",\n    \"post_action\": \"dologin.php\"\n  });\n</script>" . $alert . "\n<iframe id=\"duo_iframe\" width=\"100%\" height=\"500\" frameborder=\"0\"></iframe>";
    }
    else
    {
        $output = "There is an error with the DuoSecurity module configuration. Please try again.";
    }

    return $output;
}

function duosecurity_verify(array $params)
{
    $whmcs = App::self();
    $appsecretkey = sha1("Duo" . $whmcs->get_hash());
    $integrationkey = (!empty($params["settings"]["integrationKey"]) ? decrypt($params["settings"]["integrationKey"]) : "");
    $secretkey = (!empty($params["settings"]["secretKey"]) ? decrypt($params["settings"]["secretKey"]) : "");
    $integrationkey = ($integrationkey ?: "DILXRHE92017KPRVVM4T");
    $secretkey = ($secretkey ?: "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT");
    if( WHMCS_DuoSecurity::verifyResponse($integrationkey, $secretkey, $appsecretkey, $_POST["sig_response"]) ) 
    {
        return true;
    }

    return false;
}

function duosecurity_isConfigurationOk($integrationKey, $secretKey, $apiHostname)
{
    if( duosecurity_isDeprecated() ) 
    {
        if( !duosecurity_isConfigurationCustom($integrationKey, $secretKey, $apiHostname) ) 
        {
            return false;
        }

        return true;
    }

    if( $integrationKey && $integrationKey != "DILXRHE92017KPRVVM4T" && (!$secretKey || $secretKey == "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT" || !$apiHostname || $apiHostname == "api-3ce575d8.duosecurity.com") || $secretKey && $secretKey != "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT" && (!$integrationKey || $integrationKey == "DILXRHE92017KPRVVM4T" || !$apiHostname || $apiHostname == "api-3ce575d8.duosecurity.com") || $apiHostname && $apiHostname != "api-3ce575d8.duosecurity.com" && (!$integrationKey || $integrationKey == "DILXRHE92017KPRVVM4T" || !$secretKey || $secretKey == "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT") ) 
    {
        return false;
    }

    return true;
}

function duosecurity_isConfigurationCustom($integrationKey, $secretKey, $apiHostname)
{
    if( (!$integrationKey || $integrationKey == "DILXRHE92017KPRVVM4T") && (!$secretKey || $secretKey == "lUQE5dQlJn69ime5PtWJ8f8A0oMjmVXZY6wA5tqT") && (!$apiHostname || $apiHostname == "api-3ce575d8.duosecurity.com") ) 
    {
        return false;
    }

    return true;
}

function duosecurity_isDeprecated()
{
    if( duosecurity_daysUntilDeprecation() <= 0 ) 
    {
        return true;
    }

    return false;
}

function duosecurity_daysUntilDeprecation()
{
    $dateDifference = Carbon\Carbon::now()->diff(Carbon\Carbon::createFromFormat("Y-m-d H:i:s", WHMCS_DuoSecurity::$deprecationDate));
    return (($dateDifference->invert == 1 ? -1 : 1)) * $dateDifference->days;
}


