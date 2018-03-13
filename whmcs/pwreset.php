<?php 
define("CLIENTAREA", true);
require("init.php");
require("includes/clientfunctions.php");
$pagetitle = $_LANG["pwreset"];
$pageicon = "";
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"pwreset.php\">" . $_LANG["pwreset"] . "</a>";
$displayTitle = Lang::trans("pwreset");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$securityquestion = "";
$action = $whmcs->get_req_var("action");
$email = $whmcs->get_req_var("email");
$answer = $whmcs->get_req_var("answer");
$key = $whmcs->get_req_var("key");
$success = $whmcs->get_req_var("success");
$smartyvalues["action"] = $action;
$smartyvalues["email"] = $email;
$smartyvalues["key"] = $key;
$smartyvalues["answer"] = $answer;
$smartyvalues["success"] = false;
$smartyvalues["securityquestion"] = "";
$smartyvalues["showingLoginPage"] = true;
if( $action == "reset" ) 
{
    check_token();
    $templatefile = "pwreset";
    $errormessage = doResetPWEmail($email, $answer);
    if( $securityquestion ) 
    {
        $smartyvalues["securityquestion"] = $securityquestion;
    }

    if( $errormessage ) 
    {
        $smartyvalues["errormessage"] = $errormessage;
    }
    else
    {
        if( !$securityquestion || $securityquestion && $answer ) 
        {
            $smartyvalues["success"] = true;
        }

    }

}
else
{
    if( $key ) 
    {
        $invalidlink = doResetPWKeyCheck($key);
        if( $newpw && !$invalidlink ) 
        {
            $errormessage = doResetPW($key, $newpw, $confirmpw);
            if( !$errormessage ) 
            {
                $smartyvalues["success"] = true;
            }

        }

        $smartyvalues["invalidlink"] = $invalidlink;
        $smartyvalues["errormessage"] = $errormessage;
        $templatefile = "pwresetvalidation";
    }
    else
    {
        if( $success ) 
        {
            $smartyvalues["success"] = true;
            $templatefile = "pwresetvalidation";
        }
        else
        {
            $templatefile = "pwreset";
        }

    }

}

outputClientArea($templatefile, false, array( "ClientAreaPagePasswordReset" ));

