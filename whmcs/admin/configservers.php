<?php 
define("ADMINAREA", true);
require("../init.php");
if( $action == "singlesignon" && checkPermission("WHMCSConnect", true) ) 
{
    $aInt = new WHMCS\Admin("WHMCSConnect");
    if( $whmcs->get_req_var("error") ) 
    {
        if( WHMCS\Session::get("ServerModuleCallError") ) 
        {
            echo WHMCS\View\Helper::applicationError(AdminLang::trans("global.erroroccurred"), WHMCS\Session::get("ServerModuleCallError"));
        }
        else
        {
            echo WHMCS\View\Helper::applicationError(AdminLang::trans("global.erroroccurred"));
        }

        throw new WHMCS\Exception\ProgramExit();
    }

}
else
{
    $aInt = new WHMCS\Admin("Configure Servers");
}

$aInt->title = "Servers";
$aInt->sidebar = "config";
$aInt->icon = "servers";
$aInt->helplink = "Servers";
$action = $whmcs->get_req_var("action");
$id = (int) $whmcs->get_req_var("id");
if( $action == "getmoduleinfo" ) 
{
    check_token("WHMCS.admin.default");
    $moduleName = $whmcs->get_req_var("type");
    $moduleInfo = getmoduleinfo($moduleName);
    throw new WHMCS\Exception\ProgramExit(json_encode($moduleInfo));
}

if( $action == "testconnection" ) 
{
    check_token("WHMCS.admin.default");
    $moduleName = $whmcs->get_req_var("type");
    $moduleInterface = new WHMCS\Module\Server();
    if( !$moduleInterface->load($moduleName) ) 
    {
        throw new WHMCS\Exception\ProgramExit("Invalid Server Module Type");
    }

    if( $moduleInterface->functionExists("TestConnection") ) 
    {
        $passwordToTest = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("password"));
        $serverId = $whmcs->get_req_var("serverid");
        if( $serverId ) 
        {
            $storedPassword = get_query_val("tblservers", "password", array( "id" => $serverId ));
            $storedPassword = decrypt($storedPassword);
            if( !hasMaskedPasswordChanged($passwordToTest, $storedPassword) ) 
            {
                $passwordToTest = $storedPassword;
            }

        }

        $params = $moduleInterface->getServerParams($serverId, array( "ipaddress" => $whmcs->get_req_var("ipaddress"), "hostname" => $whmcs->get_req_var("hostname"), "username" => $whmcs->get_req_var("username"), "password" => encrypt($passwordToTest), "accesshash" => $whmcs->get_req_var("accesshash"), "secure" => $whmcs->get_req_var("secure"), "port" => $whmcs->get_req_var("port") ));
        $connectionTestResult = $moduleInterface->call("TestConnection", $params);
        if( array_key_exists("success", $connectionTestResult) && $connectionTestResult["success"] == true ) 
        {
            $htmlOutput = "<span style=\"padding:2px 10px;background-color:#5bb75b;color:#fff;font-weight:bold;\">" . $aInt->lang("configservers", "testconnectionsuccess") . "</div>";
        }
        else
        {
            $errorMsg = (array_key_exists("error", $connectionTestResult) ? $connectionTestResult["error"] : $aInt->lang("configservers", "testconnectionunknownerror"));
            $htmlOutput = "<span style=\"padding:2px 10px;background-color:#cc0000;color:#fff;\"><strong>" . $aInt->lang("configservers", "testconnectionfailed") . ":</strong> " . WHMCS\Input\Sanitize::makeSafeForOutput($errorMsg) . "</div>";
        }

        throw new WHMCS\Exception\ProgramExit($htmlOutput);
    }

    throw new WHMCS\Exception\ProgramExit($aInt->lang("configservers", "testconnectionnotsupported"));
}

if( $action == "singlesignon" ) 
{
    check_token("WHMCS.admin.default");
    $serverId = (int) $whmcs->get_req_var("serverid");
    $server = Illuminate\Database\Capsule\Manager::table("tblservers")->find($serverId);
    $allowedRoleIds = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $serverId)->pluck("role_id");
    if( count($restrictedRoles) == 0 ) 
    {
        $allowAccess = true;
    }
    else
    {
        $allowAccess = false;
        $adminAuth = new WHMCS\Auth();
        $adminAuth->getInfobyID(WHMCS\Session::get("adminid"));
        $adminRoleId = $adminAuth->getAdminRoleId();
        if( in_array($adminRoleId, $allowedRoleIds) ) 
        {
            $allowAccess = true;
        }

    }

    if( !$allowAccess ) 
    {
        $session = new WHMCS\Session();
        $session->create($whmcs->getWHMCSInstanceID());
        logAdminActivity("Single Sign-on Access Denied: '" . $server->name . "' - Server ID: " . $serverId);
        WHMCS\Session::set("ServerModuleCallError", "You do not have permisson to sign-in to this server. If you feel this message to be an error, please contact the system administrator.");
        redir("action=singlesignon&error=1");
    }

    try
    {
        $moduleInterface = new WHMCS\Module\Server();
        $redirectUrl = $moduleInterface->getSingleSignOnUrlForAdmin($serverId);
        logAdminActivity("Single Sign-on Completed: '" . $server->name . "' - Server ID: " . $serverId);
    }
    catch( WHMCS\Exception\Module\SingleSignOnError $e ) 
    {
        $session = new WHMCS\Session();
        $session->create($whmcs->getWHMCSInstanceID());
        WHMCS\Session::set("ServerModuleCallError", $e->getMessage());
        redir("action=singlesignon&error=1");
    }
    catch( Exception $e ) 
    {
        logActivity("Single Sign-On Request Failed with a Fatal Error: " . $e->getMessage());
        $session = new WHMCS\Session();
        $session->create($whmcs->getWHMCSInstanceID());
        WHMCS\Session::set("ServerModuleCallError", "A fatal error occurred. Please see activity log for more details.");
        redir("action=singlesignon&error=1");
    }
    header("Location: " . $redirectUrl);
    throw new WHMCS\Exception\ProgramExit();
}

if( $action == "delete" ) 
{
    check_token("WHMCS.admin.default");
    $numaccounts = get_query_val("tblhosting", "COUNT(*)", array( "server" => $id ));
    if( 0 < $numaccounts ) 
    {
        redir("deleteerror=true");
    }
    else
    {
        run_hook("ServerDelete", array( "serverid" => $id ));
        $server = Illuminate\Database\Capsule\Manager::table("tblservers")->find($id);
        logAdminActivity("Server Deleted: '" . $server->name . "' - Server ID: " . $id);
        delete_query("tblservers", array( "id" => $id ));
        redir("deletesuccess=true");
    }

}

if( $action == "deletegroup" ) 
{
    check_token("WHMCS.admin.default");
    $serverGroup = Illuminate\Database\Capsule\Manager::table("tblservergroups")->find($id);
    logAdminActivity("Server Group Deleted: '" . $serverGroup->name . "' - Server Group ID: " . $id);
    delete_query("tblservergroups", array( "id" => $id ));
    delete_query("tblservergroupsrel", array( "serverid" => $id ));
    redir("deletegroupsuccess=true");
}

if( $action == "save" ) 
{
    check_token("WHMCS.admin.default");
    $name = $whmcs->get_req_var("name");
    $hostname = $whmcs->get_req_var("hostname");
    $ipaddress = $whmcs->get_req_var("ipaddress");
    $assignedips = $whmcs->get_req_var("assignedips");
    $monthlycost = (double) $whmcs->get_req_var("monthlycost");
    $noc = $whmcs->get_req_var("noc");
    $maxaccounts = (int) $whmcs->get_req_var("maxaccounts");
    $statusaddress = $whmcs->get_req_var("statusaddress");
    $disabled = (int) (bool) $whmcs->get_req_var("disabled");
    $nameserver1 = $whmcs->get_req_var("nameserver1");
    $nameserver1ip = $whmcs->get_req_var("nameserver1ip");
    $nameserver2 = $whmcs->get_req_var("nameserver2");
    $nameserver2ip = $whmcs->get_req_var("nameserver2ip");
    $nameserver3 = $whmcs->get_req_var("nameserver3");
    $nameserver3ip = $whmcs->get_req_var("nameserver3ip");
    $nameserver4 = $whmcs->get_req_var("nameserver4");
    $nameserver4ip = $whmcs->get_req_var("nameserver4ip");
    $nameserver5 = $whmcs->get_req_var("nameserver5");
    $nameserver5ip = $whmcs->get_req_var("nameserver5ip");
    $type = $whmcs->get_req_var("type");
    $username = $whmcs->get_req_var("username");
    $password = $whmcs->get_req_var("password");
    $accesshash = $whmcs->get_req_var("accesshash");
    $secure = $whmcs->get_req_var("secure");
    $port = $whmcs->get_req_var("port");
    $restrictsso = (int) $whmcs->get_req_var("restrictsso");
    $moduleInfo = getmoduleinfo($type);
    $defaultPort = $moduleInfo["default" . (($secure ? "" : "non")) . "sslport"];
    if( !$port || $port == $defaultPort ) 
    {
        $port = "NULL";
    }

    if( $id ) 
    {
        $changes = array(  );
        $server = Illuminate\Database\Capsule\Manager::table("tblservers")->find($id);
        $active = ($type == $server->type ? $server->active : "");
        if( $name != $server->name ) 
        {
            $changes[] = "Name Modified: '" . $server->name . "' to '" . $name . "'";
        }

        if( $hostname != $server->hostname ) 
        {
            $changes[] = "Hostname Modified: '" . $server->hostname . "' to '" . $hostname . "'";
        }

        if( $ipaddress != $server->ipaddress ) 
        {
            $changes[] = "IP Address Modified: '" . $server->ipaddress . "' to '" . $ipaddress . "'";
        }

        if( $assignedips != $server->assignedips ) 
        {
            $changes[] = "Assigned IP Addresses Modified";
        }

        if( $monthlycost != $server->monthlycost ) 
        {
            $changes[] = "Monthly Cost Modified: '" . $server->monthlycost . "' to '" . $monthlycost . "'";
        }

        if( $noc != $server->noc ) 
        {
            $changes[] = "Datacenter/NOC Modified: '" . $server->noc . "' to '" . $noc . "'";
        }

        if( $maxaccounts != $server->maxaccounts ) 
        {
            $changes[] = "Maximum No. of Accounts Modified: '" . $server->maxaccounts . "' to '" . $maxaccounts . "'";
        }

        if( $statusaddress != $server->statusaddress ) 
        {
            $changes[] = "Server Status Address Modified: '" . $server->statusaddress . "' to '" . $statusaddress . "'";
        }

        if( $disabled != $server->disabled ) 
        {
            if( $disabled ) 
            {
                $changes[] = "Server Disabled";
            }
            else
            {
                $changes[] = "Server Enabled";
            }

        }

        if( $nameserver1 != $server->nameserver1 ) 
        {
            $changes[] = "Primary Nameserver Modified: '" . $server->nameserver1 . "' to '" . $nameserver1 . "'";
        }

        if( $nameserver1ip != $server->nameserver1ip ) 
        {
            $changes[] = "Primary Nameserver IP Modified: '" . $server->nameserver1ip . "' to '" . $nameserver1ip . "'";
        }

        if( $nameserver2 != $server->nameserver2 ) 
        {
            $changes[] = "Secondary Nameserver Modified: '" . $server->nameserver2 . "' to '" . $nameserver2 . "'";
        }

        if( $nameserver2ip != $server->nameserver2ip ) 
        {
            $changes[] = "Secondary Nameserver IP Modified: '" . $server->nameserver2ip . "' to '" . $nameserver2ip . "'";
        }

        if( $nameserver3 != $server->nameserver3 ) 
        {
            $changes[] = "Third Nameserver Modified: '" . $server->nameserver3 . "' to '" . $nameserver3 . "'";
        }

        if( $nameserver3ip != $server->nameserver3ip ) 
        {
            $changes[] = "Third Nameserver IP Modified: '" . $server->nameserver3ip . "' to '" . $nameserver3ip . "'";
        }

        if( $nameserver4 != $server->nameserver4 ) 
        {
            $changes[] = "Fourth Nameserver Modified: '" . $server->nameserver4 . "' to '" . $nameserver4 . "'";
        }

        if( $nameserver4ip != $server->nameserver4ip ) 
        {
            $changes[] = "Fourth Nameserver IP Modified: '" . $server->nameserver4ip . "' to '" . $nameserver4ip . "'";
        }

        if( $nameserver5 != $server->nameserver5 ) 
        {
            $changes[] = "Fifth Nameserver Modified: '" . $server->nameserver5 . "' to '" . $nameserver5 . "'";
        }

        if( $nameserver5ip != $server->nameserver5ip ) 
        {
            $changes[] = "Fifth Nameserver IP Modified: '" . $server->nameserver5ip . "' to '" . $nameserver5ip . "'";
        }

        if( $type != $server->type ) 
        {
            $changes[] = "Type Modified: '" . $server->type . "' to '" . $type . "'";
        }

        if( $username != $server->username ) 
        {
            $changes[] = "Username Modified: '" . $server->username . "' to '" . $username . "'";
        }

        if( $accesshash != $server->accesshash ) 
        {
            $changes[] = "Access Hash Modified: '" . $server->accesshash . "' to '" . $accesshash . "'";
        }

        if( $secure != $server->secure ) 
        {
            if( $secure ) 
            {
                $changes[] = "Secure Connection Enabled";
            }
            else
            {
                $changes[] = "Secure Connection Disabled";
            }

        }

        if( $port != $server->port && $port != "NULL" ) 
        {
            $changes[] = "Port Modified: '" . $server->port . "' to '" . $port . "'";
        }

        $saveData = array( "name" => $name, "type" => $type, "ipaddress" => trim($ipaddress), "assignedips" => trim($assignedips), "hostname" => trim($hostname), "monthlycost" => trim($monthlycost), "noc" => $noc, "statusaddress" => trim($statusaddress), "nameserver1" => trim($nameserver1), "nameserver1ip" => trim($nameserver1ip), "nameserver2" => trim($nameserver2), "nameserver2ip" => trim($nameserver2ip), "nameserver3" => trim($nameserver3), "nameserver3ip" => trim($nameserver3ip), "nameserver4" => trim($nameserver4), "nameserver4ip" => trim($nameserver4ip), "nameserver5" => trim($nameserver5), "nameserver5ip" => trim($nameserver5ip), "maxaccounts" => trim($maxaccounts), "username" => trim($username), "accesshash" => trim($accesshash), "secure" => $secure, "port" => $port, "disabled" => $disabled, "active" => $active );
        $newPassword = trim($whmcs->get_req_var("password"));
        $originalPassword = decrypt(get_query_val("tblservers", "password", array( "id" => $id )));
        $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
        if( $valueToStore !== false ) 
        {
            $saveData["password"] = $valueToStore;
            if( $newPassword != $originalPassword ) 
            {
                $changes[] = "Password Modified";
            }

        }

        update_query("tblservers", $saveData, array( "id" => $id ));
        if( $restrictsso ) 
        {
            $newSsoRoleRestrictions = $whmcs->get_req_var("restrictssoroles");
            if( !is_array($newSsoRoleRestrictions) ) 
            {
                $newSsoRoleRestrictions = array(  );
            }

            $adminRoleNames = $changedPermissions = array(  );
            $newSsoRoleRestrictions[] = "0";
            $existingAccesses = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->get();
            if( !$existingAccesses ) 
            {
                $changes[] = "Access Control Enabled";
            }

            Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->whereNotIn("role_id", $newSsoRoleRestrictions)->where("server_id", "=", $id)->delete();
            $currentSsoRoleRestrictions = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->pluck("role_id");
            foreach( $newSsoRoleRestrictions as $roleId ) 
            {
                if( !in_array($roleId, $currentSsoRoleRestrictions) ) 
                {
                    if( !isset($adminRoleNames[$roleId]) ) 
                    {
                        $adminRoleNames[$roleId] = Illuminate\Database\Capsule\Manager::table("tbladminroles")->find($roleId, array( "name" ))->name;
                    }

                    Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->insert(array( "server_id" => $id, "role_id" => $roleId ));
                    $changedPermissions["added"][] = $adminRoleNames[$roleId];
                }

            }
            foreach( $existingAccesses as $existingAccess ) 
            {
                if( !in_array($existingAccess->role_id, $newSsoRoleRestrictions) ) 
                {
                    if( !isset($adminRoleNames[$existingAccess->role_id]) ) 
                    {
                        $adminRoleNames[$existingAccess->role_id] = Illuminate\Database\Capsule\Manager::table("tbladminroles")->find($existingAccess->role_id, array( "name" ))->name;
                    }

                    $changedPermissions["removed"][] = $adminRoleNames[$existingAccess->role_id];
                }

            }
            if( $changedPermissions ) 
            {
                if( array_filter($changedPermissions["added"]) ) 
                {
                    $changes[] = "Added Access Control Group(s): " . implode(", ", $changedPermissions["added"]);
                }

                if( array_filter($changedPermissions["removed"]) ) 
                {
                    $changes[] = "Removed Access Control Group(s): " . implode(", ", $changedPermissions["removed"]);
                }

            }

        }
        else
        {
            $deletedRows = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->delete();
            if( $deletedRows ) 
            {
                $changes[] = "Access Control Disabled";
            }

        }

        if( $changes ) 
        {
            logAdminActivity("Server Modified: '" . $name . "' - Changes: " . implode(". ", $changes) . " - Server ID: " . $id);
        }

        run_hook("ServerEdit", array( "serverid" => $id ));
        redir("savesuccess=true");
    }
    else
    {
        $server = new WHMCS\Admin\Setup\Servers();
        $server->add($name, $type, $ipaddress, $assignedips, $hostname, $monthlycost, $noc, $statusaddress, $nameserver1, $nameserver1ip, $nameserver2, $nameserver2ip, $nameserver3, $nameserver3ip, $nameserver4, $nameserver4ip, $nameserver5, $nameserver5ip, $maxaccounts, $username, $password, $accesshash, $secure, $port, ($restrictsso ? $restrictssoroles : array(  )), $disabled);
        redir("createsuccess=true");
    }

}

if( $action == "savegroup" ) 
{
    check_token("WHMCS.admin.default");
    $name = $whmcs->get_req_var("name");
    $filltype = (int) $whmcs->get_req_var("filltype");
    $selectedservers = ($whmcs->get_req_var("selectedservers") ?: array(  ));
    $changes = $serverList = array(  );
    $serverUpdated = false;
    if( $id ) 
    {
        $serverGroup = Illuminate\Database\Capsule\Manager::table("tblservergroups")->find($id);
        if( $name != $serverGroup->name ) 
        {
            $changes[] = "Name Modified: '" . $serverGroup->name . "' to '" . $name . "'";
        }

        if( $filltype != $serverGroup->filltype ) 
        {
            if( $filltype == 1 ) 
            {
                $changes[] = "Fill Type Modified: Add to the least full server";
            }
            else
            {
                $changes[] = "Fill Type Modified: Fill active server until full then switch to next least used";
            }

        }

        $serverUpdated = true;
        update_query("tblservergroups", array( "name" => $name, "filltype" => $filltype ), array( "id" => $id ));
        $existingServers = Illuminate\Database\Capsule\Manager::table("tblservergroupsrel")->where("groupid", "=", $id)->get();
        foreach( $existingServers as $existingServer ) 
        {
            $serverList[] = $existingServer->serverid;
        }
        delete_query("tblservergroupsrel", array( "groupid" => $id ));
    }
    else
    {
        $id = insert_query("tblservergroups", array( "name" => $name, "filltype" => $filltype ));
        logAdminActivity("Server Group Created: '" . $name . "' - Server Group ID: " . $id);
    }

    if( $selectedservers ) 
    {
        $allocated = false;
        foreach( $selectedservers as $serverid ) 
        {
            insert_query("tblservergroupsrel", array( "groupid" => $id, "serverid" => $serverid ));
            if( !in_array($serverid, $serverList) && $allocated === false ) 
            {
                $changes[] = "Server(s) Added to Group";
                $allocated = true;
            }

        }
        foreach( $serverList as $serverId ) 
        {
            if( !in_array($serverId, $selectedservers) ) 
            {
                $changes[] = "Server(s) Removed from Group";
                break;
            }

        }
    }
    else
    {
        if( !$selectedservers && $serverList ) 
        {
            $changes[] = "All Servers Removed from Group";
        }

    }

    if( $serverUpdated && $changes ) 
    {
        logAdminActivity("Server Group Modified: '" . $name . "' - Changes: " . implode(". ", $changes) . " - Server Group ID: " . $id);
    }

    redir("savesuccess=1");
}

if( $action == "enable" ) 
{
    check_token("WHMCS.admin.default");
    $server = Illuminate\Database\Capsule\Manager::table("tblservers")->find($id);
    if( $server->disabled ) 
    {
        logAdminActivity("Server Enabled: '" . $server->name . "' - Server ID: " . $id);
        update_query("tblservers", array( "disabled" => "0" ), array( "id" => $id ));
    }

    redir("enablesuccess=1");
}

if( $action == "disable" ) 
{
    check_token("WHMCS.admin.default");
    $server = Illuminate\Database\Capsule\Manager::table("tblservers")->find($id);
    if( !$server->disabled ) 
    {
        logAdminActivity("Server Disabled: '" . $server->name . "' - Server ID: " . $id);
        update_query("tblservers", array( "disabled" => "1" ), array( "id" => $id ));
    }

    redir("disablesuccess=1");
}

if( $action == "makedefault" ) 
{
    check_token("WHMCS.admin.default");
    $result = select_query("tblservers", "", array( "id" => $id ));
    $data = mysql_fetch_array($result);
    $type = $data["type"];
    if( !$data["active"] ) 
    {
        logAdminActivity("Server Set to Default: '" . $data["name"] . "' - Server ID: " . $id);
        update_query("tblservers", array( "active" => "" ), array( "type" => $type ));
        update_query("tblservers", array( "active" => "1" ), array( "id" => $id ));
    }

    redir("makedefault=1");
}

ob_start();
if( $action == "" ) 
{
    if( $createsuccess ) 
    {
        infoBox($aInt->lang("configservers", "addedsuccessful"), $aInt->lang("configservers", "addedsuccessfuldesc"));
    }

    if( $deletesuccess ) 
    {
        infoBox($aInt->lang("configservers", "delsuccessful"), $aInt->lang("configservers", "delsuccessfuldesc"));
    }

    if( $deletegroupsuccess ) 
    {
        infoBox($aInt->lang("configservers", "groupdelsuccessful"), $aInt->lang("configservers", "groupdelsuccessfuldesc"));
    }

    if( $deleteerror ) 
    {
        infoBox($aInt->lang("configservers", "error"), $aInt->lang("configservers", "errordesc"));
    }

    if( $savesuccess ) 
    {
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("configservers", "changesuccessdesc"));
    }

    if( $enablesuccess ) 
    {
        infoBox($aInt->lang("configservers", "enabled"), $aInt->lang("configservers", "enableddesc"));
    }

    if( $disablesuccess ) 
    {
        infoBox($aInt->lang("configservers", "disabled"), $aInt->lang("configservers", "disableddesc"));
    }

    if( $makedefault ) 
    {
        infoBox($aInt->lang("configservers", "defaultchange"), $aInt->lang("configservers", "defaultchangedesc"));
    }

    if( $whmcs->get_req_var("error") && WHMCS\Session::get("ServerModuleCallError") ) 
    {
        infoBox($aInt->lang("global", "erroroccurred"), WHMCS\Session::get("ServerModuleCallError"));
        WHMCS\Session::delete("ServerModuleCallError");
    }

    echo $infobox;
    $aInt->deleteJSConfirm("doDelete", "configservers", "delserverconfirm", "?action=delete&id=");
    $aInt->deleteJSConfirm("doDeleteGroup", "configservers", "delgroupconfirm", "?action=deletegroup&id=");
    echo "\n<p>";
    echo $aInt->lang("configservers", "pagedesc");
    echo "</p>\n\n<p>\n    <div class=\"btn-group\" role=\"group\">\n        <a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=manage\" class=\"btn btn-default\"><i class=\"fa fa-plus\"></i> ";
    echo $aInt->lang("configservers", "addnewserver");
    echo "</a>\n        <a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=managegroup\" class=\"btn btn-default\"><i class=\"fa fa-plus-square\"></i> ";
    echo $aInt->lang("configservers", "createnewgroup");
    echo "</a>\n    </div>\n</p>\n\n";
    $adminAuth = new WHMCS\Auth();
    $adminAuth->getInfobyID(WHMCS\Session::get("adminid"));
    $adminRoleId = $adminAuth->getAdminRoleId();
    $server = new WHMCS\Module\Server();
    $modulesarray = $server->getList();
    $aInt->sortableTableInit("nopagination");
    $result3 = select_query("tblservers", "DISTINCT type", "", "type", "ASC");
    while( $data = mysql_fetch_array($result3) ) 
    {
        $moduleName = $data["type"];
        $module = new WHMCS\Module\Server();
        $module->load($moduleName);
        $moduleDisplayName = $module->getDisplayName();
        $tabledata[] = array( "dividingline", $moduleDisplayName );
        $disableddata = array(  );
        $result = select_query("tblservers", "", array( "type" => $moduleName ), "name", "ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $id = (int) $data["id"];
            $name = $data["name"];
            $ipaddress = $data["ipaddress"];
            $hostname = $data["hostname"];
            $maxaccounts = $data["maxaccounts"];
            $username = $data["username"];
            $password = decrypt($data["password"]);
            $accesshash = $data["accesshash"];
            $secure = $data["secure"];
            $active = $data["active"];
            $type = $data["type"];
            $disabled = $data["disabled"];
            $active = ($active ? "*" : "");
            $numaccounts = get_query_val("tblhosting", "COUNT(id)", "server='" . $id . "' AND (domainstatus='Active' OR domainstatus='Suspended')");
            $percentuse = @round($numaccounts / $maxaccounts * 100, 0);
            if( in_array($type, $modulesarray) ) 
            {
                $server->load($type);
                $params = $server->getServerParams($id, $data);
                if( $server->functionExists("AdminSingleSignOn") ) 
                {
                    $btnLabel = $server->getMetaDataValue("AdminSingleSignOnLabel");
                    $ssoRoleRestrictions = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->pluck("role_id");
                    $adminlogincode = sprintf("<form action=\"configservers.php\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"action\" value=\"%s\" />" . "<input type=\"hidden\" name=\"serverid\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\"%s class=\"btn btn-sm%s\" />" . "</form>", "singlesignon", $id, ($btnLabel ? $btnLabel : $aInt->lang("sso", "adminlogin")), (0 < count($ssoRoleRestrictions) && !in_array($adminRoleId, $ssoRoleRestrictions) ? " disabled=\"disabled\"" : ""), (0 < count($ssoRoleRestrictions) && !in_array($adminRoleId, $ssoRoleRestrictions) ? " btn-disabled\"" : " btn-default"));
                }
                else
                {
                    if( $server->functionExists("AdminLink") ) 
                    {
                        $adminlogincode = $server->call("AdminLink", $params);
                        $adminlogincode = str_replace("input type=\"submit\"", "input type=\"submit\" class=\"btn btn-sm btn-default\"", $adminlogincode);
                    }
                    else
                    {
                        $adminlogincode = "-";
                    }

                }

            }
            else
            {
                $adminlogincode = $aInt->lang("global", "modulefilemissing");
            }

            if( $disabled ) 
            {
                $disableddata[] = array( "<i>" . $name . " (" . $aInt->lang("emailtpls", "disabled") . ")</i>", "<i>" . $ipaddress . "</i>", "<i>" . $numaccounts . "/" . $maxaccounts . "</i>", "<i>" . $percentuse . "%</i>", $adminlogincode, "<div align=\"center\"><a href=\"?action=enable&id=" . $id . generate_token("link") . "\" title=\"" . $aInt->lang("configservers", "enableserver") . "\"><img src=\"images/icons/disabled.png\"></a></div>", "<a href=\"?action=manage&id=" . $id . "\" title=\"" . $aInt->lang("global", "edit") . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\" title=\"" . $aInt->lang("global", "delete") . "\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>" );
            }
            else
            {
                $tabledata[] = array( "<a href=\"?action=makedefault&id=" . $id . generate_token("link") . "\" title=\"" . $aInt->lang("configservers", "defaultsignups") . "\">" . $name . "</a> " . $active, $ipaddress, (string) $numaccounts . "/" . $maxaccounts, (string) $percentuse . "%", $adminlogincode, "<div align=\"center\"><a href=\"?action=disable&id=" . $id . generate_token("link") . "\" title=\"" . $aInt->lang("configservers", "disableserverclick") . "\"><img src=\"images/icons/tick.png\"></a></div>", "<a href=\"?action=manage&id=" . $id . "\" title=\"" . $aInt->lang("global", "edit") . "\">\n                <img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\" title=\"" . $aInt->lang("global", "delete") . "\">\n            <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>" );
            }

        }
        foreach( $disableddata as $data ) 
        {
            $tabledata[] = $data;
        }
    }
    echo $aInt->sortableTable(array( $aInt->lang("configservers", "servername"), $aInt->lang("fields", "ipaddress"), $aInt->lang("configservers", "activeaccounts"), $aInt->lang("configservers", "usage"), " ", $aInt->lang("fields", "status"), "", "" ), $tabledata);
    echo "\n<h2>";
    echo $aInt->lang("configservers", "groups");
    echo "</h2>\n\n<p>";
    echo $aInt->lang("configservers", "groupsdesc");
    echo "</p>\n\n";
    $tabledata = array(  );
    $result = select_query("tblservergroups", "", "", "name", "ASC");
    while( $data = mysql_fetch_array($result) ) 
    {
        $id = $data["id"];
        $name = $data["name"];
        $filltype = $data["filltype"];
        if( $filltype == 1 ) 
        {
            $filltype = $aInt->lang("configservers", "addleast");
        }
        else
        {
            if( $filltype == 2 ) 
            {
                $filltype = $aInt->lang("configservers", "fillactive");
            }

        }

        $servers = "";
        $result2 = select_query("tblservergroupsrel", "tblservers.name", array( "groupid" => $id ), "name", "ASC", "", "tblservers ON tblservers.id=tblservergroupsrel.serverid");
        while( $data = mysql_fetch_array($result2) ) 
        {
            $servers .= $data["name"] . ", ";
        }
        $servers = substr($servers, 0, -2);
        $tabledata[] = array( $name, $filltype, $servers, "<a href=\"?action=managegroup&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDeleteGroup('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>" );
    }
    echo $aInt->sortableTable(array( $aInt->lang("configservers", "groupname"), $aInt->lang("fields", "filltype"), $aInt->lang("setup", "servers"), "", "" ), $tabledata);
}
else
{
    if( $action == "manage" ) 
    {
        if( $id ) 
        {
            $result = select_query("tblservers", "", array( "id" => $id ));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $type = $data["type"];
            $name = $data["name"];
            $ipaddress = $data["ipaddress"];
            $assignedips = $data["assignedips"];
            $hostname = $data["hostname"];
            $monthlycost = $data["monthlycost"];
            $noc = $data["noc"];
            $statusaddress = $data["statusaddress"];
            $nameserver1 = $data["nameserver1"];
            $nameserver1ip = $data["nameserver1ip"];
            $nameserver2 = $data["nameserver2"];
            $nameserver2ip = $data["nameserver2ip"];
            $nameserver3 = $data["nameserver3"];
            $nameserver3ip = $data["nameserver3ip"];
            $nameserver4 = $data["nameserver4"];
            $nameserver4ip = $data["nameserver4ip"];
            $nameserver5 = $data["nameserver5"];
            $nameserver5ip = $data["nameserver5ip"];
            $maxaccounts = $data["maxaccounts"];
            $username = $data["username"];
            $password = decrypt($data["password"]);
            $accesshash = $data["accesshash"];
            $secure = $data["secure"];
            $port = $data["port"];
            $active = $data["active"];
            $disabled = $data["disabled"];
            $managetitle = $aInt->lang("configservers", "editserver");
            $isSsoRestricted = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->count();
            $currentSsoRoleRestrictions = Illuminate\Database\Capsule\Manager::table("tblserversssoperms")->where("server_id", "=", $id)->pluck("role_id");
        }
        else
        {
            $managetitle = $aInt->lang("configservers", "addserver");
            if( !$maxaccounts ) 
            {
                $maxaccounts = "200";
            }

            $id = "";
            $type = "";
            $secure = "";
            $port = "";
        }

        $moduleInfo = getmoduleinfo($type);
        $defaultPort = $moduleInfo["default" . (($secure ? "" : "non")) . "sslport"];
        $serverModuleDropdownHtml = "";
        $server = new WHMCS\Module\Server();
        foreach( $server->getList() as $moduleName ) 
        {
            $server->load($moduleName);
            if( $server->getMetaDataValue("RequiresServer") !== false ) 
            {
                $serverModuleDropdownHtml .= "<option value=\"" . $moduleName . "\"" . (($moduleName == $type ? " selected" : "")) . ">" . $server->getDisplayName() . "</option>";
            }

        }
        echo "<h2>" . $managetitle . "</h2>";
        echo "\n<form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=save";
        if( $id ) 
        {
            echo "&id=" . $id;
        }

        echo "\" id=\"frmServerConfig\">\n<input type=\"hidden\" name=\"serverid\" value=\"";
        echo $id;
        echo "\" />\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"23%\" class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "name");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"name\" size=\"30\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "hostname");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"hostname\" size=\"40\" value=\"";
        echo $hostname;
        echo "\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"ipaddress\" size=\"20\" value=\"";
        echo $ipaddress;
        echo "\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "assignedips");
        echo "<br/>\n                ";
        echo $aInt->lang("configservers", "assignedipsdesc");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <textarea name=\"assignedips\" cols=\"60\" rows=\"8\" class=\"form-control input-400\">";
        echo $assignedips;
        echo "</textarea>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "monthlycost");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"monthlycost\" size=\"10\" value=\"";
        echo $monthlycost;
        echo "\" class=\"form-control input-100\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "datacenter");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"noc\" size=\"30\" value=\"";
        echo $noc;
        echo "\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "maxaccounts");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"maxaccounts\" size=\"6\" value=\"";
        echo $maxaccounts;
        echo "\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "statusaddress");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"statusaddress\" size=\"60\" value=\"";
        echo $statusaddress;
        echo "\" class=\"form-control input-400\" />\n                ";
        echo $aInt->lang("configservers", "statusaddressdesc");
        echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("general", "enabledisable");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"disabled\" value=\"1\" class=\"checkbox\" ";
        if( $disabled ) 
        {
            echo "checked ";
        }

        echo "/>\n                    ";
        echo $aInt->lang("configservers", "disableserver");
        echo "                </label>\n            </td>\n        </tr>\n    </table>\n    <p>\n        <b>\n            ";
        echo $aInt->lang("configservers", "nameservers");
        echo "        </b>\n    </p>\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"23%\" class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "primarynameserver");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"nameserver1\" size=\"40\" value=\"";
        echo $nameserver1;
        echo "\" class=\"form-control input-200 input-inline\" />\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo ": <input type=\"text\" name=\"nameserver1ip\" size=\"25\" value=\"";
        echo $nameserver1ip;
        echo "\" class=\"form-control input-200 input-inline\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "secondarynameserver");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"nameserver2\" size=\"40\" value=\"";
        echo $nameserver2;
        echo "\" class=\"form-control input-200 input-inline\" />\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo ": <input type=\"text\" name=\"nameserver2ip\" size=\"25\" value=\"";
        echo $nameserver2ip;
        echo "\" class=\"form-control input-200 input-inline\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "thirdnameserver");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"nameserver3\" size=\"40\" value=\"";
        echo $nameserver3;
        echo "\" class=\"form-control input-200 input-inline\" />\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo ": <input type=\"text\" name=\"nameserver3ip\" size=\"25\" value=\"";
        echo $nameserver3ip;
        echo "\" class=\"form-control input-200 input-inline\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "fourthnameserver");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"nameserver4\" size=\"40\" value=\"";
        echo $nameserver4;
        echo "\" class=\"form-control input-200 input-inline\" />\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo ": <input type=\"text\" name=\"nameserver4ip\" size=\"25\" value=\"";
        echo $nameserver4ip;
        echo "\" class=\"form-control input-200 input-inline\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "fifthnameserver");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"nameserver5\" size=\"40\" value=\"";
        echo $nameserver5;
        echo "\" class=\"form-control input-200 input-inline\" />\n                ";
        echo $aInt->lang("fields", "ipaddress");
        echo ": <input type=\"text\" name=\"nameserver5ip\" size=\"25\" value=\"";
        echo $nameserver5ip;
        echo "\" class=\"form-control input-200 input-inline\" />\n            </td>\n        </tr>\n    </table>\n    <p>\n        <b>\n            ";
        echo $aInt->lang("configservers", "serverdetails");
        echo "        </b>\n    </p>\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"23%\" class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "type");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"type\" id=\"inputServerType\">";
        echo $serverModuleDropdownHtml;
        echo "</select>\n                <input type=\"button\" value=\"";
        echo $aInt->lang("configservers", "testconnection");
        echo "\" id=\"connectionTestBtn\" class=\"btn btn-danger btn-xs\"";
        echo ($moduleInfo["cantestconnection"] ? "" : " style=\"display:none;\"");
        echo " />\n                <span id=\"connectionTestResult\"></span>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "username");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"username\" size=\"25\" value=\"";
        echo $username;
        echo "\" autocomplete=\"off\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("fields", "password");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"password\" name=\"password\" size=\"25\" value=\"";
        echo replacePasswordWithMasks($password);
        echo "\" autocomplete=\"off\" class=\"form-control input-200\" />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        $apiTokenClass = " hidden";
        $accessHashClass = "";
        if( $type == "cpanel" ) 
        {
            $apiTokenClass = "";
            $accessHashClass = " hidden";
        }

        echo "<span class=\"access-hash" . $accessHashClass . "\">\n    " . $aInt->lang("configservers", "accesshash") . "<br />\n    " . $aInt->lang("configservers", "accesshashdesc") . "\n</span>\n<span class=\"api-key" . $apiTokenClass . "\">\n    " . $aInt->lang("configservers", "apiToken") . "\n</span>";
        echo "            </td>\n            <td class=\"fieldarea\">\n                ";
        $apiTokenDisabled = " disabled=\"disabled\"";
        $apiTokenClass = " hidden";
        $accessHashDisabled = "";
        $accessHashClass = "";
        $toolTip = AdminLang::trans("configservers.apiTokenInfo");
        if( $type == "cpanel" && (!$accesshash || $accesshash && !stristr($accesshash, "\r\n")) ) 
        {
            $apiTokenDisabled = "";
            $apiTokenClass = "";
            $accessHashDisabled = " disabled=\"disabled\"";
            $accessHashClass = " hidden";
        }

        echo "<input id=\"apiToken\" name=\"accesshash\" class=\"form-control input-500" . $apiTokenClass . "\"" . $apiTokenDisabled . " value=\"" . $accesshash . "\" data-toggle=\"tooltip\" data-placement=\"auto right\" data-trigger=\"focus\" title=\"" . $toolTip . "\"/>\n<textarea id=\"serverHash\" name=\"accesshash\" rows=\"8\" class=\"form-control input-500" . $accessHashClass . "\"" . $accessHashDisabled . ">" . $accesshash . "</textarea>";
        echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "secure");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"secure\" id=\"inputSecure\"";
        if( $secure ) 
        {
            echo " checked";
        }

        echo " class=\"checkbox\"/>\n                    ";
        echo $aInt->lang("configservers", "usessl");
        echo "                </label>\n            </td>\n        </tr>\n        <tr id=\"trPort\"";
        if( !$moduleInfo["defaultsslport"] && !$moduleInfo["defaultnonsslport"] ) 
        {
            echo " style=\"display:none;\"";
        }

        echo ">\n            <td class=\"fieldlabel\">\n                ";
        echo $aInt->lang("configservers", "port");
        echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"port\" id=\"inputPort\" class=\"form-control input-75 input-inline\" value=\"";
        echo ($port ? $port : $defaultPort);
        echo "\" size=\"8\"";
        if( !$port ) 
        {
            echo " disabled=\"disabled\"";
        }

        echo " />\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" id=\"inputOverridePort\"";
        if( $port ) 
        {
            echo " checked";
        }

        echo " />\n                    ";
        echo $aInt->lang("configservers", "portoverride");
        echo "                </label>\n            </td>\n        </tr>\n    </table>\n\n<div id=\"containerAccessControl\"";
        if( !$moduleInfo["supportsadminsso"] ) 
        {
            echo " style=\"display:none;\"";
        }

        echo ">\n\n<p><b>SSO Access Control</b></p>\n<p>This server module supports Single Sign-On for admin users. Below you can configure access permissions for this.</p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"23%\" class=\"fieldlabel\">Access Control</td><td class=\"fieldarea\">\n<label class=\"radio-inline\"><input type=\"radio\" name=\"restrictsso\" value=\"0\" onclick=\"hideAccessControl()\"";
        if( !$isSsoRestricted ) 
        {
            echo " checked";
        }

        echo "> Unrestricted - Allow all admin users to connect to this server</label><br />\n<label class=\"radio-inline\"><input type=\"radio\" name=\"restrictsso\" value=\"1\" onclick=\"showAccessControl()\"";
        if( $isSsoRestricted ) 
        {
            echo " checked";
        }

        echo "> Restricted - Only allow access to select admin role groups and/or users</label><br />\n</td></tr>\n<tr class=\"trAccessControl\"";
        if( !$isSsoRestricted ) 
        {
            echo " style=\"display:none;\"";
        }

        echo "><td width=\"23%\" class=\"fieldlabel\">Admin Role Groups</td><td class=\"fieldarea\">\nAllow access to any admin users in the following admin role groups:<br />\n";
        $adminRoles = Illuminate\Database\Capsule\Manager::table("tbladminroles")->orderBy("name", "asc")->pluck("name", "id");
        foreach( $adminRoles as $id => $name ) 
        {
            echo sprintf("<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"restrictssoroles[]\" value=\"%s\"%s />%s</label>", $id, (in_array($id, $currentSsoRoleRestrictions) ? " checked" : ""), $name);
        }
        echo "</td></tr>\n</table>\n\n</div>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"button btn btn-primary\">\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"window.location='configservers.php'\" />\n</div>\n\n</form>\n\n";
        $accessControlJSCode = "\nfunction hideAccessControl() {\n    \$(\".trAccessControl\").fadeOut();\n}\nfunction showAccessControl() {\n    \$(\".trAccessControl\").fadeIn();\n}\n";
        $aInt->addHeadJsCode($accessControlJSCode);
        $connectionTestJSCode = "\n\nvar defaultSSLPort = \"" . $moduleInfo["defaultsslport"] . "\";\nvar defaultNonSSLPort = \"" . $moduleInfo["defaultnonsslport"] . "\";\n\n\$(\"#inputOverridePort\").click(function() {\n    if (\$(\"#inputOverridePort\").prop(\"checked\")) {\n        \$(\"#inputPort\").prop(\"disabled\", false);\n    } else {\n        \$(\"#inputPort\").prop(\"disabled\", true);\n        if (\$(\"#inputSecure\").prop(\"checked\")) {\n            \$(\"#inputPort\").val(defaultSSLPort);\n        } else {\n            \$(\"#inputPort\").val(defaultNonSSLPort);\n        }\n    }\n});\n\n\$(\"#inputSecure\").click(function() {\n    if (!\$(\"#inputOverridePort\").prop(\"checked\")) {\n        if (\$(\"#inputSecure\").prop(\"checked\")) {\n            \$(\"#inputPort\").val(defaultSSLPort);\n        } else {\n            \$(\"#inputPort\").val(defaultNonSSLPort);\n        }\n    }\n});\n\n\$(\"#inputServerType\").change(function() {\n    \$.post(\"configservers.php\", 'token=" . generate_token("plain") . "&action=getmoduleinfo&type=' + \$(\"#inputServerType\").val(),\n    function(data) {\n        \$(\"#connectionTestResult\").fadeOut();\n        if (data.cantestconnection==\"1\") {\n            \$(\"#connectionTestBtn\").fadeIn();\n        } else {\n            \$(\"#connectionTestBtn\").fadeOut();\n        }\n        if (data.supportsadminsso==\"1\") {\n            \$(\"#containerAccessControl\").fadeIn();\n        } else {\n            \$(\"#containerAccessControl\").fadeOut();\n        }\n        defaultSSLPort = data.defaultsslport;\n        defaultNonSSLPort = data.defaultnonsslport;\n        if (!defaultSSLPort && !defaultNonSSLPort) {\n            \$(\"#trPort\").fadeOut();\n        } else {\n            \$(\"#trPort\").fadeIn();\n        }\n        if (!\$(\"#inputOverridePort\").prop(\"checked\")) {\n            if (\$(\"#inputSecure\").prop(\"checked\")) {\n                \$(\"#inputPort\").val(defaultSSLPort);\n            } else {\n                \$(\"#inputPort\").val(defaultNonSSLPort);\n            }\n        }\n        var accessHash = \$(\"#serverHash\"),\n            apiToken = \$(\"#apiToken\");\n        if (typeof data.apiTokens !== \"undefined\" && data.apiTokens === true) {\n            var currentAccessHash = accessHash.val();\n            if (accessHash.hasClass('hidden') === false && (!currentAccessHash || currentAccessHash.indexOf(\"\\n\") < 0)) {\n                apiToken.removeClass('hidden').prop('disabled', false);\n                apiToken.val(currentAccessHash);\n                accessHash.addClass('hidden').prop('disabled', true);\n                \$(\"span.access-hash\").hide('fast', function(){\n                    \$(\"span.api-key\").hide().removeClass('hidden').show();\n                });\n            }\n        } else {\n            if (accessHash.hasClass('hidden')) {\n                var currentApiToken = apiToken.val();\n                accessHash.removeClass('hidden').prop('disabled', false);\n                accessHash.text(currentApiToken);\n                apiToken.addClass('hidden').prop('disabled', true);\n                \$(\"span.api-key\").hide('fast', function(){\n                    \$(\"span.access-hash\").hide().removeClass('hidden').show();\n                });\n            }\n        }\n    }, \"json\");\n});\n\n\$(\"#connectionTestBtn\").click(function() {\n    \$(\"#connectionTestResult\").html(\"<img src=\\\"images/loading.gif\\\" align=\\\"absmiddle\\\" /> " . addslashes($aInt->lang("configservers", "testconnectionloading")) . "\");\n    \$(\"#connectionTestResult\").show();\n    \$.post(\"configservers.php\", \$(\"#frmServerConfig\").serialize() + '&action=testconnection',\n    function(data) {\n        \$(\"#connectionTestResult\").html(data);\n    });\n});\n\n";
        $aInt->addInternalJQueryCode($connectionTestJSCode);
    }
    else
    {
        if( $action == "managegroup" ) 
        {
            if( $id ) 
            {
                $managetitle = $aInt->lang("configservers", "editgroup");
                $result = select_query("tblservergroups", "", array( "id" => $id ));
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                $name = $data["name"];
                $filltype = $data["filltype"];
            }
            else
            {
                $managetitle = $aInt->lang("configservers", "newgroup");
                $filltype = "1";
            }

            echo "<h2>" . $managetitle . "</h2>";
            $jquerycode = "\$(\"#serveradd\").click(function () {\n  \$(\"#serverslist option:selected\").appendTo(\"#selectedservers\");\n  return false;\n});\n\$(\"#serverrem\").click(function () {\n  \$(\"#selectedservers option:selected\").appendTo(\"#serverslist\");\n  return false;\n});";
            echo "\n<form method=\"post\" action=\"";
            echo $_SERVER["PHP_SELF"];
            echo "?action=savegroup&id=";
            echo $id;
            echo "\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
            echo $aInt->lang("fields", "name");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" size=\"40\" value=\"";
            echo $name;
            echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "filltype");
            echo "</td><td class=\"fieldarea\"><input type=\"radio\" name=\"filltype\" value=\"1\"";
            if( $filltype == 1 ) 
            {
                echo " checked";
            }

            echo "> ";
            echo $aInt->lang("configservers", "addleast");
            echo "<br /><input type=\"radio\" name=\"filltype\" value=\"2\"";
            if( $filltype == 2 ) 
            {
                echo " checked";
            }

            echo "> ";
            echo $aInt->lang("configservers", "fillactive");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "selectedservers");
            echo "</td><td class=\"fieldarea\"><table><td><td><select size=\"10\" multiple=\"multiple\" id=\"serverslist\" style=\"width:200px;\">";
            $selectedservers = array(  );
            $result = select_query("tblservergroupsrel", "tblservers.id,tblservers.name,tblservers.disabled", array( "groupid" => $id ), "name", "ASC", "", "tblservers ON tblservers.id=tblservergroupsrel.serverid");
            while( $data = mysql_fetch_array($result) ) 
            {
                $id = $data["id"];
                $name = $data["name"];
                $disabled = $data["disabled"];
                if( $disabled ) 
                {
                    $name .= " (" . $aInt->lang("emailtpls", "disabled") . ")";
                }

                $selectedservers[$id] = $name;
            }
            $result = select_query("tblservers", "", "", "name", "ASC");
            while( $data = mysql_fetch_array($result) ) 
            {
                $id = $data["id"];
                $name = $data["name"];
                $disabled = $data["disabled"];
                if( $disabled ) 
                {
                    $name .= " (Disabled)";
                }

                if( !array_key_exists($id, $selectedservers) ) 
                {
                    echo "<option value=\"" . $id . "\">" . $name . "</option>";
                }

            }
            echo "</select></td><td align=\"center\"><input type=\"button\" id=\"serveradd\" value=\"";
            echo $aInt->lang("global", "add");
            echo " &raquo;\" class=\"btn btn-sm\" /><br /><br /><input type=\"button\" id=\"serverrem\" value=\"&laquo; ";
            echo $aInt->lang("global", "remove");
            echo "\" class=\"btn btn-sm\" /></td><td><select size=\"10\" multiple=\"multiple\" id=\"selectedservers\" name=\"selectedservers[]\" style=\"width:200px;\">";
            foreach( $selectedservers as $id => $name ) 
            {
                echo "<option value=\"" . $id . "\">" . $name . "</option>";
            }
            echo "</select></td></td></table></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
            echo $aInt->lang("global", "savechanges");
            echo "\" onclick=\"\$('#selectedservers *').attr('selected','selected')\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
            echo $aInt->lang("global", "cancelchanges");
            echo "\" class=\"btn btn-default\" onclick=\"window.location='configservers.php'\" />\n</div>\n</form>\n\n";
        }

    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->jquerycode = $jquerycode;
$aInt->display();
function getModuleInfo($moduleName)
{
    $returnData = array( "cantestconnection" => false, "supportsadminsso" => false, "defaultsslport" => "", "defaultnonsslport" => "" );
    $moduleInterface = new WHMCS\Module\Server();
    if( $moduleInterface->load($moduleName) ) 
    {
        if( $moduleInterface->functionExists("TestConnection") ) 
        {
            $returnData["cantestconnection"] = true;
        }

        if( $moduleInterface->functionExists("AdminSingleSignOn") ) 
        {
            $returnData["supportsadminsso"] = true;
        }

        $returnData["defaultsslport"] = $moduleInterface->getMetaDataValue("DefaultSSLPort");
        $returnData["defaultnonsslport"] = $moduleInterface->getMetaDataValue("DefaultNonSSLPort");
    }

    if( $moduleName == "cpanel" ) 
    {
        $returnData["apiTokens"] = true;
    }

    return $returnData;
}


