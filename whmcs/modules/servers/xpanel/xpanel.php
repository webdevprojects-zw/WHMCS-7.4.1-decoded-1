<?php 

class xpanel_apiRequestException extends Exception
{
}

function xpanel_MetaData()
{
    return array( "DisplayName" => "XPanel", "APIVersion" => "1.0", "DefaultNonSSLPort" => "80", "DefaultSSLPort" => "3737" );
}

function xpanel_curlInit($ipaddress, $hostname, $login, $password, $useSecure, $protocol, $port)
{
    $host = ($useSecure ? $ipaddress : $hostname);
    $script = "cgi-bin/xpanel/api/whmcs.cgi";
    $url = (string) $protocol . "://" . $host . ":" . $port . "/" . $script;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, (string) $login . ":" . $password);
    return $curl;
}

function xpanel_sendRequest($curl, $packet)
{
    curl_setopt($curl, CURLOPT_POSTFIELDS, $packet);
    $result = curl_exec($curl);
    if( curl_errno($curl) ) 
    {
        $errmsg = curl_error($curl);
        $errcode = curl_errno($curl);
        curl_close($curl);
        throw new xpanel_apiRequestException($errmsg, $errcode);
    }

    curl_close($curl);
    return $result;
}

function xpanel_parseResponse($response_string)
{
    $xml = new SimpleXMLElement($response_string);
    if( !is_a($xml, "SimpleXMLElement") ) 
    {
        throw new xpanel_apiRequestException("Cannot parse server response: " . $response_string);
    }

    return $xml;
}

function xpanel_ConfigOptions()
{
    global $defaultserver;
    global $packageconfigoption;
    if( $packageconfigoption[1] == "on" ) 
    {
        if( $defaultserver != 0 ) 
        {
            $result = full_query("SELECT `ipaddress`, `hostname`, `username`, `password`, `secure`, `port` FROM `tblservers` WHERE `id` = " . (int) $defaultserver);
        }
        else
        {
            $result = full_query("SELECT `ipaddress`, `hostname`, `username`, `password`, `secure`, `port` FROM `tblservers` WHERE `type` = 'xpanel' AND `active` = '1' limit 1");
        }

        if( $result ) 
        {
            $row = mysql_fetch_object($result);
            if( $row ) 
            {
                $curl = xpanel_curlinit($row->ipaddress, $row->hostname, $row->username, decrypt($row->password), $row->secure, ($row->secure ? "https" : "http"), ($row->port ? $row->port : ($row->secure ? "3737" : "80")));
                $data = "action=getpackagelist";
                try
                {
                    $response = xpanel_sendrequest($curl, $data);
                    $responseXml = xpanel_parseresponse($response);
                    foreach( $responseXml->xpath("/system/get/result") as $resultNode ) 
                    {
                        if( "error" == (string) $resultNode->status ) 
                        {
                            throw new xpanel_apiRequestException("XPanel API returned error: " . (string) $resultNode->result->errtext);
                        }

                        $configarray = array( "Get from server" => array( "Type" => "yesno", "Description" => "Get the available choices from the server" ), "Hosting Plan ID: " => array( "Type" => "dropdown", "Options" => "" . (string) $resultNode->packagelist . "" ) );
                    }
                }
                catch( xpanel_apiRequestException $e ) 
                {
                    return $e;
                }
            }

        }

    }
    else
    {
        $configarray = array( "Get from server" => array( "Type" => "yesno", "Description" => "Get the available choices from the server" ), "Hosting Plan ID: " => array( "Type" => "text", "Size" => "3", "Description" => "#" ) );
    }

    return $configarray;
}

function xpanel_CreateAccount($params)
{
    $serviceid = $params["serviceid"];
    $pid = $params["pid"];
    $producttype = $params["producttype"];
    $domain = $params["domain"];
    $username = $params["username"];
    $password = $params["password"];
    $clientsdetails = $params["clientsdetails"];
    $customfields = $params["customfields"];
    $configoptions = $params["configoptions"];
    $package_id = $params["configoption2"];
    $configoption3 = $params["configoption3"];
    $configoption4 = $params["configoption4"];
    $server = $params["server"];
    $serverid = $params["serverid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serveraccesshash = $params["serveraccesshash"];
    $serversecure = $params["serversecure"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $model = $params["model"];
    $orderid = $model->orderId;
    $billingcycle = $model->billingCycle;
    $paymentmethod = $model->paymentGateway;
    $nextduedate = $model->nextDueDate . " 00:00:00";
    if( $billingcycle == "Free Account" ) 
    {
        $billing_cycle = "0";
    }
    else
    {
        if( $billingcycle == "Quarterly" ) 
        {
            $billing_cycle = "3";
        }
        else
        {
            if( $billingcycle == "Semi-Annually" ) 
            {
                $billing_cycle = "6";
            }
            else
            {
                if( $billingcycle == "Annually" ) 
                {
                    $billing_cycle = "12";
                }
                else
                {
                    if( $billingcycle == "Biennially" ) 
                    {
                        $billing_cycle = "24";
                    }
                    else
                    {
                        $billing_cycle = 1;
                    }

                }

            }

        }

    }

    if( $paymentmethod == "tco" ) 
    {
        $payment_method = "Credit Card";
    }
    else
    {
        $payment_method = "Free";
    }

    if( $clientsdetails["companyname"] ) 
    {
        $organization = "&organization=" . $clientsdetails["companyname"];
        $account_type = 1;
    }
    else
    {
        $account_type = 0;
        $organization = "";
    }

    $data = "action=createacct" . "&customer_id=" . $clientsdetails["userid"] . "&login_name=" . $clientsdetails["email"] . "&password=" . $password . "&first_name=" . $clientsdetails["firstname"] . "&last_name=" . $clientsdetails["lastname"] . $organization . "&address1=" . $clientsdetails["address1"] . "&address2=" . $clientsdetails["address2"] . "&city=" . $clientsdetails["city"] . "&state=" . $clientsdetails["state"] . "&postal_code=" . $clientsdetails["postcode"] . "&country=" . $clientsdetails["country"] . "&work_phone=" . $clientsdetails["phonenumber"] . "&email=" . $clientsdetails["email"] . "&account_type=" . $account_type . "&domain_name=" . $domain . "&package_id=" . $package_id . "&billing_cycle=" . $billing_cycle . "&paymentmethod=" . $payment_method . "&nextduedate=" . $nextduedate . "&account_id=" . $serviceid . "&order_id=" . $orderid . "&account_login_name=" . $username . "&account_password=" . $password;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/add/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_TerminateAccount($params)
{
    $serviceid = $params["serviceid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serversecure = $params["serversecure"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $data = "action=removeacct" . "&account_id=" . $serviceid;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/del/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_SuspendAccount($params)
{
    $serviceid = $params["serviceid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serversecure = $params["serversecure"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $data = "action=suspendacct" . "&account_id=" . $serviceid;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/suspend/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_UnsuspendAccount($params)
{
    $serviceid = $params["serviceid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serversecure = $params["serversecure"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $data = "action=unsuspendacct" . "&account_id=" . $serviceid;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/unsuspend/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_ChangePassword($params)
{
    $serviceid = $params["serviceid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serversecure = $params["serversecure"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $username = $params["username"];
    $password = $params["password"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $data = "action=passwd" . "&account_id=" . $serviceid . "&account_login_name=" . $username . "&account_password=" . $password;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/passwd/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_ChangePackage($params)
{
    $serviceid = $params["serviceid"];
    $serverip = $params["serverip"];
    $serverhostname = $params["serverhostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serverprotocol = $params["serverhttpprefix"];
    $serverport = $params["serverport"];
    $serversecure = $params["serversecure"];
    $package_id = $params["configoption2"];
    $curl = xpanel_curlinit($serverip, $serverhostname, $serverusername, $serverpassword, $serversecure, $serverprotocol, $serverport);
    $data = "action=changepackage" . "&account_id=" . $serviceid . "&package_id=" . $package_id;
    try
    {
        $response = xpanel_sendrequest($curl, $data);
        $responseXml = xpanel_parseresponse($response);
        foreach( $responseXml->xpath("/account/changepackage/result") as $resultNode ) 
        {
            if( "error" == (string) $resultNode->status ) 
            {
                return "" . (string) $resultNode->errtext . "\n";
            }

            return "success";
        }
    }
    catch( xpanel_apiRequestException $e ) 
    {
        return $e;
    }
}

function xpanel_ClientArea($params)
{
    global $_LANG;
    $serverhostname = $params["serverhostname"];
    $serversecure = $params["serversecure"];
    $protocol = ($serversecure ? "https" : "http");
    $port = ($serversecure ? 3737 : 80);
    $script = "cgi-bin/xpanel/account_manager.cgi?a=log_in&privileges=account";
    $url = (string) $protocol . "://" . $serverhostname . ":" . $port . "/" . $script;
    $form = sprintf("<form action=\"%s\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"login_name\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "<input type=\"button\" value=\"%s\" onclick=\"window.open('http://%s/webmail')\" />" . "</form>", WHMCS\Input\Sanitize::encode($url), WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), $_LANG["xpanellogin"], $_LANG["xpanelmaillogin"], WHMCS\Input\Sanitize::encode($serverhostname));
    return $form;
}

function xpanel_AdminLink($params)
{
    $serverhostname = $params["serverhostname"];
    $serversecure = $params["serversecure"];
    $protocol = ($serversecure ? "https" : "http");
    $port = ($serversecure ? 3737 : 80);
    $script = "cgi-bin/xpanel/admin/index.cgi";
    $url = (string) $protocol . "://" . $serverhostname . ":" . $port . "/" . $script;
    $form = sprintf("<form action=\"%s\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"user\" value=\"%s\" />" . "<input type=\"hidden\" name=\"pass\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::encode($url), WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "XPanel");
    return $form;
}

function xpanel_LoginLink($params)
{
    $serverhostname = $params["serverhostname"];
    $serversecure = $params["serversecure"];
    $protocol = ($serversecure ? "https" : "http");
    $port = ($serversecure ? 3737 : 80);
    $script = "cgi-bin/xpanel/account_manager.cgi?a=log_in&privileges=account&login_link=1";
    $url = (string) $protocol . "://" . $serverhostname . ":" . $port . "/" . $script;
    $form = sprintf("<a href=\"%s&amp;login_name=%s&amp;password=%s\" target=\"_blank\" class=\"moduleloginlink\">%s</a>", WHMCS\Input\Sanitize::encode($url), WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), "login to control panel");
    return $form;
}


