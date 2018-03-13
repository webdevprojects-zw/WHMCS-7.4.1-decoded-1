<?php 

class LxHelper
{
    public $protocol = NULL;
    public $server = NULL;
    public $port = NULL;
    public $username = NULL;
    public $password = NULL;

    public function __construct($server, $username, $password, $protocol, $port)
    {
        $this->protocol = $protocol;
        $this->server = $server;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function callLxApi($params)
    {
        $params = "login-class=client&login-name=" . $this->username . "&login-password=" . $this->password . "&output-type=json&" . $params;
        $ch = curl_init($this->protocol . "://" . $this->server . ":" . $this->port . "/webcommand.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $totalout = curl_exec($ch);
        curl_close($ch);
        $totalout = trim($totalout);
        require_once(dirname(__FILE__) . "/../hypervm/JSON.php");
        $json = new Services_JSON();
        $object = $json->decode($totalout);
        if( !is_object($object) ) 
        {
            print "Fatal Error. Got a non-object from the server: " . $totalout . "\n";
        }
        else
        {
            return $object;
        }

    }

    public function callLxApi_getResourcePlans()
    {
        return $this->callLxApi("action=simplelist&resource=resourceplan");
    }

    public function callLxApi_getDnsTemplates()
    {
        return $this->callLxApi("action=simplelist&resource=dnstemplate");
    }

    public function callLxApi_getServers()
    {
        return $this->callLxApi("action=simplelist&resource=pserver");
    }

    public static function objectToCommaList($object, $addDefault = true)
    {
        foreach( $object as $key => $value ) 
        {
            $options[] = $value;
        }
        if( $addDefault ) 
        {
            return "--- Please select ---," . implode(",", $options);
        }

        return implode(",", $options);
    }

    public static function getInternalResourceName($object, $name)
    {
        foreach( $object as $key => $value ) 
        {
            if( $value == $name ) 
            {
                return $key;
            }

        }
    }

}

function lxadmin_MetaData()
{
    return array( "DisplayName" => "LXAdmin", "APIVersion" => "1.0", "DefaultNonSSLPort" => "7778", "DefaultSSLPort" => "7777" );
}

function lxadmin_ConfigOptions()
{
    global $packageconfigoption;
    $serverslist = array(  );
    $result = select_query("tblservers", "id,name", array( "type" => "lxadmin" ));
    while( $data = mysql_fetch_array($result) ) 
    {
        $serverslist[] = $data[0] . "|" . $data[1];
    }
    if( $packageconfigoption[1] == "on" ) 
    {
        $serverid = explode("|", $packageconfigoption[8]);
        $serverid = $serverid[0];
        $result = select_query("tblservers", "ipaddress, username, password, secure, port", array( "id" => (int) $serverid ));
        if( $result ) 
        {
            $row = mysql_fetch_object($result);
            if( $row ) 
            {
                $lxHelper = new LxHelper($row->ipaddress, $row->username, decrypt($row->password), ($row->secure ? "https" : "http"), ($row->port ? $row->port : ($row->secure ? "7777" : "7778")));
                $json = $lxHelper->callLxApi_getResourcePlans();
                if( $json->return === "error" ) 
                {
                    print "ERROR: " . $json->message;
                    return NULL;
                }

                $resourcePlans = $json->result;
                $json = $lxHelper->callLxApi_getDnsTemplates();
                if( $json->return === "error" ) 
                {
                    print "ERROR: " . $json->message;
                    return NULL;
                }

                $dnsTemplates = $json->result;
                $json = $lxHelper->callLxApi_getServers();
                if( $json->return === "error" ) 
                {
                    print "ERROR: " . $json->message;
                    return NULL;
                }

                $servers = $json->result;
            }

        }

        $configarray = array( "Get from server" => array( "Type" => "yesno", "Description" => "Get the available choices from the server" ), "Resource Plan" => array( "Type" => "dropdown", "Options" => LxHelper::objectToCommaList($resourcePlans) ), "DNS Template" => array( "Type" => "dropdown", "Options" => LxHelper::objectToCommaList($dnsTemplates) ), "Web Server" => array( "Type" => "dropdown", "Options" => LxHelper::objectToCommaList($servers) ), "Mail Server" => array( "Type" => "dropdown", "Options" => LxHelper::objectToCommaList($servers) ), "MySQL Server" => array( "Type" => "dropdown", "Options" => LxHelper::objectToCommaList($servers) ), "DNS Servers" => array( "Type" => "text", "Size" => "40", "Description" => "(comma&nbsp;separated)<br/>Available servers: " . LxHelper::objectToCommaList($servers, false) ), "Server to Load Choices From" => array( "Type" => "dropdown", "Options" => implode(",", $serverslist) ) );
    }
    else
    {
        $configarray = array( "Get from server" => array( "Type" => "yesno", "Description" => "Get the available choices from the server" ), "Resource Plan" => array( "Type" => "text", "Size" => "30", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>Resource Plans</strong>" ), "DNS Template" => array( "Type" => "text", "Size" => "30", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>DNS Templates</strong>" ), "Wev Server" => array( "Type" => "text", "Size" => "30", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>Servers</strong>" ), "Mail Server" => array( "Type" => "text", "Size" => "30", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>Servers</strong>" ), "MySQL Server" => array( "Type" => "text", "Size" => "30", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>Servers</strong>" ), "DNS Servers" => array( "Type" => "text", "Size" => "40", "Description" => "<br/>As specified in <strong>Client Home (admin)</strong> -&gt; <strong>Servers</strong>. This is a comma separated list." ), "Server to Load Choices From" => array( "Type" => "dropdown", "Options" => implode(",", $serverslist) ) );
    }

    return $configarray;
}

function lxadmin_CreateAccount($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $domain = $params["domain"];
    $username = $params["username"];
    $password = $params["password"];
    $clientsdetails = $params["clientsdetails"];
    $resourcePlan = $params["configoption2"];
    $dnsTemplate = $params["configoption3"];
    $webServer = $params["configoption4"];
    $mailServer = $params["configoption5"];
    $mysqlServer = $params["configoption6"];
    $dnsServers = $params["configoption7"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi_getResourcePlans();
    if( $json->return === "error" ) 
    {
        return $json->message;
    }

    $resourcePlanInternal = LxHelper::getInternalResourceName($json->result, $resourcePlan);
    $json = $lxHelper->callLxApi("action=add" . "&class=client" . "&name=" . $username . "&v-password=" . $password . "&v-plan_name=" . $resourcePlanInternal . "&v-type=customer" . "&v-contactemail=" . $clientsdetails["email"] . "&v-send_welcome_f=off" . "&v-domain_name=" . $domain . "&v-dnstemplate_name=" . $dnsTemplate . "&v-websyncserver=" . $webServer . "&v-mmailsyncserver=" . $mailServer . "&v-mysqldbsyncserver=" . $mysqlServer . "&v-dnssyncserver_list=" . $dnsServers);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_TerminateAccount($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $username = $params["username"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi("action=delete" . "&class=client" . "&name=" . $username);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_SuspendAccount($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $username = $params["username"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi("action=update" . "&subaction=disable" . "&class=client" . "&name=" . $username);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_UnsuspendAccount($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $username = $params["username"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi("action=update" . "&subaction=enable" . "&class=client" . "&name=" . $username);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_ChangePassword($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $username = $params["username"];
    $password = $params["password"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi("action=update" . "&subaction=password" . "&class=client" . "&name=" . $username . "&v-password=" . $password);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_ChangePackage($params)
{
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $secure = $params["serversecure"];
    $protocol = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $username = $params["username"];
    $resourcePlan = $params["configoption2"];
    $lxHelper = new LxHelper($serverip, $serverusername, $serverpassword, $protocol, $port);
    $json = $lxHelper->callLxApi_getResourcePlans();
    if( $json->return === "error" ) 
    {
        return $json->message;
    }

    $resourcePlanInternal = LxHelper::getInternalResourceName($json->result, $resourcePlan);
    $json = $lxHelper->callLxApi("action=update" . "&subaction=change_plan" . "&class=client" . "&name=" . $username . "&v-resourceplan_name=" . $resourcePlanInternal);
    if( $json->return === "error" ) 
    {
        $result = $json->message;
    }
    else
    {
        $result = "success";
    }

    return $result;
}

function lxadmin_LoginLink($params)
{
    $code = sprintf("<a href=\"%s://%s:%s/htmllib/phplib/?frm_clientname=%s&amp;frm_password=%s\" target=\"_blank\" class=\"moduleloginlink\">%s</a>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), "login to LxAdmin");
    return $code;
}


