<?php 
require("init.php");
$id = (int) $whmcs->get_req_var("id");
$url = get_query_val("tbllinks", "link", array( "id" => $id ));
if( $url ) 
{
    update_query("tbllinks", array( "clicks" => "+1" ), array( "id" => $id ));
    WHMCS\Cookie::set("LinkID", $id, "3m");
    run_hook("LinkTracker", array( "linkid" => $id ));
    header("Location: " . WHMCS\Input\Sanitize::decode($url));
    exit();
}

redir("", "index.php");

