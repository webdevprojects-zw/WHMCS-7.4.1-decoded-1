<?php 
function veportal_MetaData()
{
    return array( "DisplayName" => "vePortal", "APIVersion" => "1.0", "DefaultNonSSLPort" => "2407", "DefaultSSLPort" => "2408" );
}

function veportal_ConfigOptions()
{
    $configarray = array( "Package ID" => array( "Type" => "text", "Size" => "25" ), "UBC Set ID" => array( "Type" => "text", "Size" => "25" ), "Welcome Email" => array( "Type" => "yesno", "Description" => "Send vePortal Welcome eMail (Reccomended)" ) );
    return $configarray;
}

function veportal_updateVPSinfo($veid, $ip, $hostname, $params)
{
    if( empty($hostname) ) 
    {
        $hostname = $params["domain"];
    }

    $updateArray = array( "domain" => $hostname, "dedicatedip" => "DO NOT EDIT THESE VALUES;veid=" . $veid . ";ip=" . $ip . ";hostname=" . $hostname, "Hostname" => $hostname, "VEID" => $veid, "IP" => $ip );
    $params["model"]->serviceProperties->save($updateArray);
}

function veportal_getUniqueCode($length)
{
    $code = md5(uniqid(rand(), true));
    if( $length != "" ) 
    {
        return substr($code, 0, $length);
    }

    return $code;
}

function veportal_generateUsername($domain, $params)
{
    $domain = str_replace(".", "", $domain);
    $domain = str_replace("-", "", $domain);
    $domain = str_replace("_", "", $domain);
    $hash = veportal_getuniquecode("5");
    $username = "" . $domain[0] . "" . $domain[1] . "" . $domain[2] . "" . $domain[3] . "" . $domain[4] . (string) $hash;
    $params["model"]->serviceProperties->save(array( "username" => $username ));
    return $username;
}

function veportal_processAPI($api, $postfields, $params)
{
    $api["user"] = $params["serverusername"];
    $api["key"] = $params["serverpassword"];
    $api["host"] = $params["serverip"];
    $api["protocol"] = $params["serverhttpprefix"];
    $api["port"] = $params["serverport"];
    $postfields["apikey"] = $api["key"];
    $postfields["apiuser"] = $api["user"];
    $postfields["apifunc"] = $api["function"];
    $url = $api["protocol"] . "://" . $api["host"] . ":" . $api["port"] . "/api.php";
    $query_string = http_build_query($postfields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    $data = curl_exec($ch);
    curl_close($ch);
    $data = explode(";", $data);
    $results = array(  );
    foreach( $data as $temp ) 
    {
        $temp = explode("=", $temp);
        $results[$temp[0]] = $temp[1];
    }
    logModuleCall("veportal", $api["function"], $postfields, $data, $results, array( $api["user"], $api["key"] ));
    return $results;
}

function veportal_CreateAccount($params)
{
    $serviceid = $params["serviceid"];
    $pid = $params["pid"];
    $domain = $params["domain"];
    $username = $params["username"];
    $password = $params["password"];
    $clientsdetails = $params["clientsdetails"];
    $customfields = $params["customfields"];
    $configoptions = $params["configoptions"];
    $params["veid"] = $customfields["VEID"];
    $params["hostname"] = $customfields["Hostname"];
    $params["ipaddress"] = $customfields["IP"];
    $api["function"] = "newacct";
    $post["package"] = $params["configoption1"];
    $post["ubcset"] = $params["configoption2"];
    $post["welcomeemail"] = $params["configoption13"];
    $post["ostemplate"] = $params["configoptions"]["OS Template"];
    $post["email"] = $params["clientsdetails"]["email"];
    $post["hostname"] = $params["customfields"]["Hostname"];
    $post["server"] = "localhost";
    $post["ippool"] = "any";
    $post["password"] = $params["password"];
    $post["username"] = veportal_generateusername($post["hostname"], $params);
    $apiResult = veportal_processapi($api, $post, $params);
    $result = "success";
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "useridtaken" ) 
        {
            $result = "Username Taken!";
        }
        else
        {
            if( $apiResult["problem"] == "wrongip" ) 
            {
                $result = "Incorrect API IP";
            }
            else
            {
                if( $apiResult["problem"] == "wrongkey" ) 
                {
                    $result = "Incorrect API Key";
                }
                else
                {
                    if( $apiResult["problem"] == "wrongrskey" ) 
                    {
                        $result = "Incorrect API Key For Reseller";
                    }

                }

            }

        }

    }
    else
    {
        veportal_updatevpsinfo($apiResult["veid"], $apiResult["ipad"], $post["hostname"], $params);
    }

    return $result;
}

function veportal_TerminateAccount($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "destroyacct";
    $post["veid"] = $params["veid"];
    $apiResult = veportal_processapi($api, $post, $params);
    $result = "success";
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }

    return $result;
}

function veportal_SuspendAccount($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "suspendacct";
    $post["veid"] = $params["veid"];
    $post["username"] = $params["username"];
    $apiResult = veportal_processapi($api, $post, $params);
    $result = "success";
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }

    return $result;
}

function veportal_UnsuspendAccount($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "unsuspendacct";
    $post["veid"] = $params["veid"];
    $post["username"] = $params["username"];
    $apiResult = veportal_processapi($api, $post, $params);
    $result = "success";
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }

    return $result;
}

function veportal_ChangePassword($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "changepass";
    $post["newpass"] = $params["password"];
    $post["username"] = $params["username"];
    $post["veid"] = $params["veid"];
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_ChangePackage($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    veportal_updatevpsinfo($params["veid"], $params["ipaddress"], $params["hostname"], $params);
    $api["function"] = "upgradevps";
    $post["veid"] = $params["veid"];
    $post["package"] = $params["configoption1"];
    $post["ubcset"] = $params["configoption2"];
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_ClientArea($params)
{
    global $_LANG;
    if( $params["username"] ) 
    {
        $code = sprintf("<a href=\"%s://%s:%s/login.php?user=%s&pass=%s\" target=\"_blank\">%s</a>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), $_LANG["veportallogin"]);
    }
    else
    {
        $code = "<s>" . $_LANG["veportallogin"] . "</s>";
    }

    return $code;
}

function veportal_AdminLink($params)
{
    $form = sprintf("<form action=\"%s://%s:%s/login.php\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"username\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["serverusername"]), "Login to vePortal");
    return $form;
}

function veportal_LoginLink($params)
{
    if( $params["username"] ) 
    {
        $code = sprintf("<a href=\"%s://%s:%s/login.php?user=%s&pass=%s\" target=\"_blank\" class=\"moduleloginlink\">%s</a>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), "Login to vePortal");
    }
    else
    {
        $code = "<s>Login to vePortal</s>";
    }

    return $code;
}

function veportal_AdminCustomButtonArray()
{
    $buttonarray = array( "Change Username" => "chusername", "Start VPS" => "startvps", "Stop VPS" => "stopvps", "Reboot VPS" => "rebootvps", "Backup VPS" => "backupvps", "Reload VPS OS" => "reloadvps", "Update Resource Usage" => "updateusage" );
    return $buttonarray;
}

function veportal_reloadvps($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "reloadvpsos";
    $post["veid"] = $params["veid"];
    $post["rootpass"] = $params["configoptions"]["OS Template"];
    $post["ostemplate"] = $params["password"];
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_backupvps($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "backupvps";
    $post["veid"] = $params["veid"];
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_startvps($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "commandvps";
    $post["veid"] = $params["veid"];
    $post["command"] = "start";
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_stopvps($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "commandvps";
    $post["veid"] = $params["veid"];
    $post["command"] = "stop";
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_rebootvps($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "commandvps";
    $post["veid"] = $params["veid"];
    $post["command"] = "restart";
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_chusername($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "chusername";
    $post["veid"] = $params["veid"];
    $post["username"] = $params["username"];
    $apiResult = veportal_processapi($api, $post, $params);
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }
    else
    {
        $successful = true;
        $result = "success";
    }

    return $result;
}

function veportal_updateusage($params)
{
    $customFields = $params["customfields"];
    $params["veid"] = $customFields["VEID"];
    $params["hostname"] = $customFields["Hostname"];
    $params["ipaddress"] = $customFields["IP"];
    $api["function"] = "getvmusage";
    $post["veid"] = $params["veid"];
    $apiResult = veportal_processapi($api, $post, $params);
    $hdd = $apiResult["hdd"] * 1024;
    $hdd = (double) number_format($hdd, 0, ".", "");
    $bw = $apiResult["bw"] * 1024;
    $bw = (double) number_format($bw, 0, ".", "");
    $currentbw = (double) $params["model"]->serviceProperties->get("bwusage");
    $currenthdd = (double) $params["model"]->serviceProperties->get("diskusage");
    $hdd = $currenthdd + $hdd;
    $bw = $currentbw + $bw;
    $params["model"]->serviceProperties->save(array( "bwusage" => $bw, "diskusage" => $hdd ));
    $result = "success";
    if( $apiResult["return"] == "error" ) 
    {
        if( $apiResult["problem"] == "wrongip" ) 
        {
            $result = "Incorrect API IP";
        }
        else
        {
            if( $apiResult["problem"] == "wrongkey" ) 
            {
                $result = "Incorrect API Key";
            }
            else
            {
                if( $apiResult["problem"] == "nolicense" ) 
                {
                    $result = "vePortal Node Not Licensed. <b>Visit <a href='http://www.veportal.com/'>vePortal</a> To Purchase a License</b>";
                }

            }

        }

    }

    return $result;
}


