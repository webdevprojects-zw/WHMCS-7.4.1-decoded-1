<?php 
define("CLIENTAREA", true);
require("init.php");
$pagetitle = $_LANG["unsubscribe"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"unsubscribe.php\">" . $_LANG["unsubscribe"] . "</a>";
$pageicon = "";
$displayTitle = Lang::trans("newsletterunsubscribe");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$email = $whmcs->get_req_var("email");
$key = $whmcs->get_req_var("key");
if( $email ) 
{
    $errormessage = dounsubscribe($email, $key);
    $smartyvalues["errormessage"] = $errormessage;
    if( !$errormessage ) 
    {
        $smartyvalues["successful"] = true;
        $smartyvalues["unsubscribesuccess"] = Lang::trans("unsubscribesuccess");
    }

    $templatefile = "unsubscribe";
    outputClientArea($templatefile, false, array( "ClientAreaPageUnsubscribe" ));
}
else
{
    redir("", "index.php");
}

function doUnsubscribe($email, $key)
{
    global $whmcs;
    global $_LANG;
    if( !$email ) 
    {
        return $_LANG["pwresetemailrequired"];
    }

    $result = select_query("tblclients", "id,email,emailoptout", array( "email" => $email ));
    $data = mysql_fetch_array($result);
    $userid = $data["id"];
    $email = $data["email"];
    $emailoptout = $data["emailoptout"];
    $newkey = sha1($email . $userid . $whmcs->get_hash());
    if( $newkey == $key ) 
    {
        if( !$userid ) 
        {
            return $_LANG["unsubscribehashinvalid"];
        }

        if( $emailoptout == 1 ) 
        {
            return $_LANG["alreadyunsubscribed"];
        }

        update_query("tblclients", array( "emailoptout" => "1" ), array( "id" => $userid ));
        sendMessage("Unsubscribe Confirmation", $userid);
        logActivity("Unsubscribed From Marketing Emails - User ID:" . $userid, $userid);
    }
    else
    {
        return $_LANG["unsubscribehashinvalid"];
    }

}


