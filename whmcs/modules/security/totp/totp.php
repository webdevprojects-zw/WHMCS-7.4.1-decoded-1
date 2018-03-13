<?php 
function totp_config()
{
    $licensing = DI::make("license");
    $licensedata = $licensing->getKeyData("configoptions");
    $totpenabled = (array_key_exists("TOTP", $licensedata) ? $licensedata["TOTP"] : 0);
    $configarray = array( "FriendlyName" => array( "Type" => "System", "Value" => "Time-based HMAC One-Time Password (TOTP)" ), "Description" => array( "Type" => "System", "Value" => "TOTP requires that a user enter a 6 digit code that changes every 30 seconds to complete login. This works with mobile apps such as OATH Token and Google Authenticator.<br /><br />For more information about Time Based Tokens, please <a href=\"http://go.whmcs.com/114/totp\" target=\"_blank\">click here</a>." . (($totpenabled ? "" : "<br /><br /><strong>Just \$1.50 per month (unlimited users)</strong>")) ), "Licensed" => array( "Type" => "System", "Value" => ($totpenabled ? true : false) ), "SubscribeLink" => array( "Type" => "System", "Value" => "http://go.whmcs.com/114/totp" ) );
    return $configarray;
}

function totp_activate($params)
{
    $whmcs = App::self();
    if( $whmcs->get_req_var("showqrimage") ) 
    {
        if( !isset($_SESSION["totpqrurl"]) ) 
        {
            exit();
        }

        include(ROOTDIR . "/modules/security/totp/phpqrcode.php");
        QRcode::png($_SESSION["totpqrurl"], false, 6, 6);
        exit();
    }

    $username = $params["user_info"]["username"];
    $tokendata = (isset($params["user_settings"]["tokendata"]) ? $params["user_settings"]["tokendata"] : "");
    totp_loadgaclass();
    $gaotp = new MyOauth();
    $gaotp->twoFactorAuthentication = $params["twoFactorAuthentication"];
    $username = $whmcs->sanitize("a-z", $whmcs->get_config("CompanyName")) . ":" . $username;
    if( $whmcs->get_req_var("step") == "verify" ) 
    {
        $verifyfail = false;
        if( $whmcs->get_req_var("verifykey") ) 
        {
            $ans = $gaotp->authenticateUser($username, $whmcs->get_req_var("verifykey"));
            if( $ans ) 
            {
                $output = array(  );
                $output["completed"] = true;
                $output["msg"] = "Key Verified Successfully!";
                $output["settings"] = array( "tokendata" => $tokendata );
                return $output;
            }

            $verifyfail = true;
        }

        $output = "<h2>" . $whmcs->get_lang("twoipverificationstep") . "</h2><p>" . $whmcs->get_lang("twoipverificationstepmsg") . "</p>";
        if( $verifyfail ) 
        {
            $output .= "<div class=\"errorbox alert alert-danger\"><strong>" . $whmcs->get_lang("twoipverificationerror") . "</strong><br />" . $whmcs->get_lang("twoipcodemissmatch") . "</div>";
        }

        $output .= "<form onsubmit=\"dialogSubmit();return false\">\n<input type=\"hidden\" name=\"2fasetup\" value=\"1\" />\n<input type=\"hidden\" name=\"module\" value=\"totp\" />\n<input type=\"hidden\" name=\"step\" value=\"verify\" />\n<div class=\"row\">\n    <div class=\"col-sm-6 col-sm-offset-3\">\n        <div class=\"form-group\">\n            <input type=\"text\" name=\"verifykey\" size=\"10\" maxlength=\"6\" style=\"font-size:18px;\" class=\"form-control input-lg\" />\n        </div>\n    </div>\n</div>\n<div class=\"form-group text-center\">\n    <input type=\"button\" value=\"" . $whmcs->get_lang("confirm") . " &raquo;\" class=\"btn btn-primary large\" onclick=\"dialogSubmit()\" />\n</div>\n</form>";
    }
    else
    {
        $key = $gaotp->setUser($username, "TOTP");
        $url = $gaotp->createUrl($username);
        $_SESSION["totpqrurl"] = $url;
        $output = "<h2>" . $whmcs->get_lang("twoiptimebasedpassword") . "</h2>\n<p>" . $whmcs->get_lang("twoiptimebasedexplain") . "</p>\n<p>" . $whmcs->get_lang("twoipconfigureapp") . "</p>\n<ul>\n<li>" . $whmcs->get_lang("twoipconfigurestep1") . "</li>\n<li>" . $whmcs->get_lang("twoipconfigurestep2") . "\"" . $gaotp->helperhex2b32($gaotp->getKey($username)) . "\"</li>\n</ul>\n\n<div align=\"center\">" . ((function_exists("imagecreate") ? "<img src=\"" . $_SERVER["PHP_SELF"] . "?2fasetup=1&module=totp&showqrimage=1\" />" : "<em>" . ${$whmcs}->get_lang("twoipgdmissing") . "</em>")) . "</div>\n\n<form onsubmit=\"dialogSubmit();return false\">\n<input type=\"hidden\" name=\"2fasetup\" value=\"1\" />\n<input type=\"hidden\" name=\"module\" value=\"totp\" />\n<input type=\"hidden\" name=\"step\" value=\"verify\" />\n<p align=\"center\"><input type=\"button\" value=\"" . $whmcs->get_lang("confirm") . " &raquo;\" onclick=\"dialogSubmit()\" class=\"btn btn-primary\" /></p>\n</form>\n\n";
    }

    return $output;
}

function totp_challenge($params)
{
    $output = "<form method=\"post\" action=\"dologin.php\">\n            <div align=\"center\">\n            <input type=\"text\" name=\"key\" maxlength=\"6\" class=\"form-control input-lg\" autofocus>\n        <br/>\n            <input id=\"btnLogin\" type=\"submit\" class=\"btn btn-primary btn-block btn-lg\" value=\"" . Lang::trans("loginbutton") . "\">\n            </div>\n</form>";
    return $output;
}

function totp_get_used_otps()
{
    $whmcs = App::self();
    $usedotps = $whmcs->get_config("TOTPUsedOTPs");
    $usedotps = ($usedotps ? unserialize($usedotps) : array(  ));
    if( !is_array($usedotps) ) 
    {
        $usedotps = array(  );
    }

    return $usedotps;
}

function totp_verify($params)
{
    $whmcs = App::self();
    $username = $params["admin_info"]["username"];
    $tokendata = $params["admin_settings"]["tokendata"];
    $key = $params["post_vars"]["key"];
    totp_loadgaclass();
    $gaotp = new MyOauth();
    $gaotp->twoFactorAuthentication = $params["twoFactorAuthentication"];
    $gaotp->setTokenData($tokendata);
    $username = "WHMCS:" . $username;
    $usedotps = totp_get_used_otps();
    $hash = md5($username . $key);
    if( array_key_exists($hash, $usedotps) ) 
    {
        return false;
    }

    $ans = false;
    $ans = $gaotp->authenticateUser($username, $key);
    if( $ans ) 
    {
        $usedotps[$hash] = time();
        $expiretime = time() - 5 * 60;
        foreach( $usedotps as $k => $time ) 
        {
            if( $time < $expiretime ) 
            {
                unset($usedotps[$k]);
            }
            else
            {
                break;
            }

        }
        $whmcs->set_config("TOTPUsedOTPs", serialize($usedotps));
    }

    return $ans;
}

function totp_loadgaclass()
{
    if( !class_exists("GoogleAuthenticator") ) 
    {
        include(ROOTDIR . "/modules/security/totp/ga4php.php");

class MyOauth extends GoogleAuthenticator
{
    private $tokendata = "";
    public $twoFactorAuthentication = NULL;

    public function setTokenData($token)
    {
        $this->tokendata = $token;
    }

    public function getData($username)
    {
        $twofa = $this->twoFactorAuthentication;
        $tokendata = ($this->tokendata ? $this->tokendata : $twofa->getUserSetting("tokendata"));
        return $tokendata;
    }

    public function putData($username, $data)
    {
        $twofa = $this->twoFactorAuthentication;
        $twofa->saveUserSettings(array( "tokendata" => $data ));
        return true;
    }

    public function getUsers()
    {
        return false;
    }

}

    }

}


