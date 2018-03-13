<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Configure Two-Factor Authentication");
$aInt->title = $aInt->lang("twofa", "title");
$aInt->sidebar = "config";
$aInt->icon = "security";
$aInt->helplink = "Security Modules";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array( "modulefunctions" ));
$frm = new WHMCS\Form();
$purchased = (int) $whmcs->get_req_var("purchased");
if( $frm->issubmitted() ) 
{
    if( defined("DEMO_MODE") ) 
    {
        redir("demo=1");
    }

    $currentSettings = unserialize(WHMCS\Config\Setting::getValue("2fasettings"));
    $forceClient = (int) (bool) $whmcs->get_req_var("forceclient");
    $forceAdmin = (int) (bool) $whmcs->get_req_var("forceadmin");
    $modules = ($whmcs->get_req_var("mod") ?: array(  ));
    if( !isset($modules["duosecurity"]["clientenabled"]) ) 
    {
        $modules["duosecurity"]["clientenabled"] = 0;
    }

    if( !isset($modules["duosecurity"]["adminenabled"]) ) 
    {
        $modules["duosecurity"]["adminenabled"] = 0;
    }

    if( !isset($modules["totp"]["clientenabled"]) ) 
    {
        $modules["totp"]["clientenabled"] = 0;
    }

    if( !isset($modules["totp"]["adminenabled"]) ) 
    {
        $modules["totp"]["adminenabled"] = 0;
    }

    if( !isset($modules["yubikey"]["clientenabled"]) ) 
    {
        $modules["yubikey"]["clientenabled"] = 0;
    }

    if( !isset($modules["yubikey"]["adminenabled"]) ) 
    {
        $modules["yubikey"]["adminenabled"] = 0;
    }

    $changes = array(  );
    if( $forceClient != $currentSettings["forceclient"] ) 
    {
        if( $forceClient ) 
        {
            $changes[] = "Force Clients to Enable on Next Login Enabled";
        }
        else
        {
            $changes[] = "Force Clients to Enable on Next Login Disabled";
        }

    }

    if( $forceAdmin != $currentSettings["forceadmin"] ) 
    {
        if( $forceClient ) 
        {
            $changes[] = "Force Admins to Enable on Next Login Enabled";
        }
        else
        {
            $changes[] = "Force Admins to Enable on Next Login Disabled";
        }

    }

    foreach( $modules as $module => $setting ) 
    {
        if( $module == "duosecurity" ) 
        {
            foreach( $setting as $settingName => $settingValue ) 
            {
                switch( $settingName ) 
                {
                    case "clientenabled":
                    case "adminenabled":
                        if( $settingValue != $currentSettings["modules"]["duosecurity"][$settingName] ) 
                        {
                            if( $settingValue ) 
                            {
                                $changes[] = "Duo Security Enabled for " . (($settingName == "clientenabled" ? "Clients" : "Staff"));
                            }
                            else
                            {
                                $changes[] = "Duo Security Disabled for " . (($settingName == "clientenabled" ? "Clients" : "Staff"));
                            }

                        }

                        break;
                    case "integrationKey":
                    case "secretKey":
                        $valueToStore = interpretMaskedPasswordChangeForStorage($settingValue, decrypt($currentSettings["modules"]["duosecurity"][$settingName]));
                        if( $valueToStore !== false ) 
                        {
                            if( $settingValue != decrypt($currentSettings["modules"]["duosecurity"][$settingName]) ) 
                            {
                                if( $settingName == "integrationKey" ) 
                                {
                                    $changes[] = "Duo Security Integration Key Modified";
                                }
                                else
                                {
                                    $changes[] = "Duo Security Secret Key Modified";
                                }

                            }

                            $modules["duosecurity"][$settingName] = $valueToStore;
                        }
                        else
                        {
                            $modules["duosecurity"][$settingName] = $currentSettings["modules"]["duosecurity"][$settingName];
                        }

                        break;
                    case "apiHostname":
                        if( $settingValue != $currentSettings["modules"]["duosecurity"][$settingName] ) 
                        {
                            $changes[] = "Duo Security API Hostname Modified";
                        }

                        break;
                }
            }
        }

        if( $module == "totp" ) 
        {
            if( $setting["clientenabled"] != $currentSettings["modules"]["totp"]["clientenabled"] ) 
            {
                if( $setting["clientenabled"] ) 
                {
                    $changes[] = "Time Based Tokens Enabled for Clients";
                }
                else
                {
                    $changes[] = "Time Based Tokens Disabled for Clients";
                }

            }

            if( $setting["adminenabled"] != $currentSettings["modules"]["totp"]["adminenabled"] ) 
            {
                if( $setting["adminenabled"] ) 
                {
                    $changes[] = "Time Based Tokens Enabled for Staff";
                }
                else
                {
                    $changes[] = "Time Based Tokens Disabled for Staff";
                }

            }

        }

        if( $module == "yubico" ) 
        {
            if( $setting["clientenabled"] != $currentSettings["modules"]["yubico"]["clientenabled"] ) 
            {
                if( $setting["clientenabled"] ) 
                {
                    $changes[] = "Yubico Enabled for Clients";
                }
                else
                {
                    $changes[] = "Yubico Disabled for Clients";
                }

            }

            if( $setting["adminenabled"] != $currentSettings["modules"]["yubico"]["adminenabled"] ) 
            {
                if( $setting["adminenabled"] ) 
                {
                    $changes[] = "Yubico Enabled for Staff";
                }
                else
                {
                    $changes[] = "Yubico Disabled for Staff";
                }

            }

            if( $setting["clientid"] != $currentSettings["modules"]["yubico"]["clientid"] ) 
            {
                $changes[] = "Yubico Client ID Modified";
            }

            if( $setting["secretkey"] != $currentSettings["modules"]["yubico"]["secretkey"] ) 
            {
                $changes[] = "Yubico Secret Key Modified";
            }

        }

    }
    $whmcs->set_config("2fasettings", serialize(array( "forceclient" => $forceClient, "forceadmin" => $forceAdmin, "modules" => $modules )));
    if( $changes ) 
    {
        logAdminActivity("Two Factor Authentication Settings Modified: " . implode(". ", $changes));
    }

    redir("success=1");
}

ob_start();
if( $purchased ) 
{
    $licensing->forceRemoteCheck();
    redir();
}

$twofasettings = $whmcs->get_config("2fasettings");
$twofasettings = unserialize($twofasettings);
$infobox = "";
if( defined("DEMO_MODE") ) 
{
    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
}

echo $infobox;
echo $frm->form();
echo "<table width=\"100%\"><tr><td width=\"45%\" valign=\"top\">\n\n<div style=\"padding:20px;background-color:#FAF5E4;-moz-border-radius: 10px;-webkit-border-radius: 10px;-o-border-radius: 10px;border-radius: 10px;\">\n\n<strong>What is Two-Factor Authentication?</strong><br /><br />\n\nTwo-factor authentication adds an additional layer of security by adding a second step to your login. It takes something you know (ie. your password) and adds a second factor, typically something you have (such as your phone.) Since both are required to log in, even if an attacker has your password they can't access your account.\n\n<div style=\"margin:20px auto;padding:10px;width:370px;background-color:#fff;-moz-border-radius: 10px;-webkit-border-radius: 10px;-o-border-radius: 10px;border-radius: 10px;\"><img src=\"images/twofahow.png\" width=\"350\" height=\"233\" /></div>\n\n<strong>Why do you need it?</strong><br /><br />\n\nPasswords are increasingly easy to compromise. They can often be guessed or leaked, they usually don't change very often, and despite advice otherwise, many of us have favorite passwords that we use for more than one thing. So Two-factor authentication gives you additional security because your password alone no longer allows access to your account.<br /><br />\n\n<strong>How it works?</strong><br /><br />\n\nThere are many different options available, and in WHMCS we support more than one so <i>you</i> have the choice.  But one of the most common and simplest to use is time based one-time passwords.  With these, in addition to your regular username & password, you also have to enter a 6 digit code that changes every 30 seconds.  Only your token device (typically a mobile smartphone) will know your secret key, and be able to generate valid one time passwords for your account.  And so your account is far safer.<br /><br />\n\n<strong>Force Settings</strong><br /><br />\n\n";
echo $frm->checkbox("forceclient", "Force Clients to enable Two Factor Authentication on Next Login", $twofasettings["forceclient"]) . "<br />";
echo $frm->checkbox("forceadmin", "Force Administrator Users to enable Two Factor Authentication on Next Login", $twofasettings["forceadmin"]) . "<br /><br />";
echo $frm->submit($aInt->lang("global", "savechanges"));
echo "</td><td width=\"55%\" valign=\"top\">";
$mod = new WHMCS\Module\Security();
$moduleslist = $mod->getList();
if( !$moduleslist ) 
{
    $aInt->gracefulExit("Security Module Folder Not Found. Please try reuploading all WHMCS related files.");
}

$i = 0;
foreach( $moduleslist as $module ) 
{
    $mod->load($module);
    $configarray = $mod->call("config");
    $moduleconfigdata = $twofasettings["modules"][$module];
    echo "<div style=\"width:90%;margin:" . (($i ? "10px" : "0")) . " auto;padding:10px 20px;border:1px solid #ccc;background-color:#fff;-moz-border-radius: 10px;-webkit-border-radius: 10px;-o-border-radius: 10px;border-radius: 10px;\">";
    if( $moduleconfigdata["clientenabled"] || $moduleconfigdata["adminenabled"] ) 
    {
        echo "<p style=\"float:right;\"><input type=\"button\" value=\"Deactivate\" class=\"btn btn-danger\" onclick=\"deactivate('" . $module . "')\" /></p>";
        $showstyle = "";
    }
    else
    {
        if( array_key_exists("Licensed", $configarray) ) 
        {
            if( $configarray["Licensed"]["Value"] ) 
            {
                echo "<p style=\"float:right;\"><input type=\"button\" value=\"Activate\" class=\"btn btn-success\" id=\"activatebtn" . $module . "\" onclick=\"activate('" . $module . "')\" /></p>";
            }
            else
            {
                echo "<p style=\"float:right;\"><input type=\"button\" value=\"Subscribe to Activate\" class=\"btn btn-default\" onclick=\"window.open('" . $configarray["SubscribeLink"]["Value"] . "');dialogOpen();\" /></p>";
            }

        }
        else
        {
            echo "<p style=\"float:right;\"><input type=\"button\" value=\"Activate\" class=\"btn btn-success\" id=\"activatebtn" . $module . "\" onclick=\"activate('" . $module . "')\" /></p>";
        }

        $showstyle = "display:none;";
    }

    if( file_exists(ROOTDIR . "/modules/security/" . $module . "/logo.gif") ) 
    {
        echo "<img src=\"../modules/security/" . $module . "/logo.gif\" />";
    }
    else
    {
        if( file_exists(ROOTDIR . "/modules/security/" . $module . "/logo.jpg") ) 
        {
            echo "<img src=\"../modules/security/" . $module . "/logo.jpg\" />";
        }
        else
        {
            if( file_exists(ROOTDIR . "/modules/security/" . $module . "/logo.png") ) 
            {
                echo "<img src=\"../modules/security/" . $module . "/logo.png\" />";
            }
            else
            {
                echo "<h2>" . ((isset($configarray["FriendlyName"]["Value"]) ? $configarray["FriendlyName"]["Value"] : ucfirst($module))) . "</h2>";
            }

        }

    }

    if( $configarray["Description"]["Value"] ) 
    {
        echo "<p>" . $configarray["Description"]["Value"] . "</p>";
    }

    echo "<div id=\"conf" . $module . "\" style=\"" . $showstyle . "\">";
    $tbl = new WHMCS\Table();
    $tbl->add("Enable for Clients", $frm->checkbox("mod[" . $module . "][clientenabled]", "Tick to Enable", $moduleconfigdata["clientenabled"], "1", "enable" . $module), 1);
    $tbl->add("Enable for Staff", $frm->checkbox("mod[" . $module . "][adminenabled]", "Tick to Enable", $moduleconfigdata["adminenabled"], "1", "enable" . $module), 1);
    foreach( $configarray as $key => $values ) 
    {
        if( $values["Type"] != "System" ) 
        {
            if( !isset($values["FriendlyName"]) ) 
            {
                $values["FriendlyName"] = $key;
            }

            $values["Name"] = "mod[" . $module . "][" . $key . "]";
            if( $values["Type"] == "password" ) 
            {
                $values["Value"] = htmlspecialchars(decrypt($moduleconfigdata[$key]));
            }
            else
            {
                $values["Value"] = htmlspecialchars($moduleconfigdata[$key]);
            }

            $tbl->add($values["FriendlyName"], moduleConfigFieldOutput($values), 1);
        }

    }
    echo $tbl->output();
    echo "<p align=\"center\">" . $frm->submit($aInt->lang("global", "savechanges")) . "</p>";
    echo "</div></div>";
    $i++;
}
echo "</td></tr></table>";
echo $frm->close();
$aInt->dialog("", "<div class=\"content\"><div style=\"padding:15px;\"><h2>Two-Factor Authentication Subscription</h2><br /><br /><div align=\"center\">You will now be redirected to purchase the selected<br />Two-Factor Authentcation solution in a new browser window.<br /><br />Once completed, please click on the button below to continue.<br /><br /><br /><form method=\"post\" action=\"configtwofa.php\"><input type=\"hidden\" name=\"purchased\" value=\"1\" /><input type=\"submit\" value=\"Continue &raquo;\" class=\"btn btn-default\" onclick=\"dialogClose()\" /></form></div></div></div>");
$content = ob_get_contents();
ob_end_clean();
$jscode = "\nfunction activate(mod) {\n    \$(\"#activatebtn\"+mod).hide();\n    \$(\"#conf\"+mod).fadeIn();\n}\nfunction deactivate(mod) {\n    \$(\".enable\"+mod).attr(\"checked\",false);\n    \$(\"#conf\"+mod).fadeOut();\n    \$(\"#" . $frm->getname() . "\").submit();\n}\n";
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

