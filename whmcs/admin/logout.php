<?php 
define("ADMINAREA", true);
require("../init.php");
$auth = new WHMCS\Auth();
if( $auth->logout() ) 
{
    redir("logout=1", "login.php");
}

redir("", "login.php");

