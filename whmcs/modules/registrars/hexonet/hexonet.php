<?php 
$xirca_config = array(  );
function hexonet_getConfigArray()
{
    $configarray = array( "Username" => array( "Type" => "text", "Size" => "30", "Description" => "" ), "Password" => array( "Type" => "text", "Size" => "30", "Description" => "" ), "TestMode" => array( "Type" => "yesno" ) );
    return $configarray;
}

function hexonet_GetNameservers($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "StatusDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"] );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

    $values = array(  );
    $values["ns1"] = $response["PROPERTY"]["NAMESERVER"][0];
    $values["ns2"] = $response["PROPERTY"]["NAMESERVER"][1];
    $values["ns3"] = $response["PROPERTY"]["NAMESERVER"][2];
    $values["ns4"] = $response["PROPERTY"]["NAMESERVER"][3];
    $values["ns5"] = $response["PROPERTY"]["NAMESERVER"][4];
    return $values;
}

function hexonet_SaveNameservers($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $update_domain_command = array( "COMMAND" => "ModifyDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"], "NAMESERVER" => array( $params["ns1"], $params["ns2"] ) );
    if( $params["ns3"] ) 
    {
        $update_domain_command["NAMESERVER"][] = $params["ns3"];
    }

    if( $params["ns4"] ) 
    {
        $update_domain_command["NAMESERVER"][] = $params["ns4"];
    }

    if( $params["ns5"] ) 
    {
        $update_domain_command["NAMESERVER"][] = $params["ns5"];
    }

    $response = xirca_call($update_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_GetRegistrarLock($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "StatusDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"] );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

    $lockstatus = ($response["PROPERTY"]["TRANSFERLOCK"][0] ? "locked" : "unlocked");
    return $lockstatus;
}

function hexonet_SaveRegistrarLock($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "ModifyDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"], "TRANSFERLOCK" => ($params["lockenabled"] == "locked" ? "1" : "0") );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_RegisterDomain($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $nameservers = array(  );
    $nameservers[] = $params["ns1"];
    $nameservers[] = $params["ns2"];
    if( $params["ns3"] ) 
    {
        $nameservers[] = $params["ns3"];
    }

    if( $params["ns4"] ) 
    {
        $nameservers[] = $params["ns4"];
    }

    if( $params["ns5"] ) 
    {
        $nameservers[] = $params["ns5"];
    }

    $create_domain_command = array( "COMMAND" => "AddDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"], "PERIOD" => $params["regperiod"], "OWNERCONTACT0" => array( "FIRSTNAME" => $params["firstname"], "LASTNAME" => $params["lastname"], "ORGANIZATION" => $params["companyname"], "STREET" => $params["address1"], "CITY" => $params["city"], "STATE" => $params["state"], "ZIP" => $params["postcode"], "COUNTRY" => $params["country"], "PHONE" => $params["fullphonenumber"], "FAX" => "", "EMAIL" => $params["email"] ), "ADMINCONTACT0" => array( "FIRSTNAME" => $params["adminfirstname"], "LASTNAME" => $params["adminlastname"], "ORGANIZATION" => $params["admincompanyname"], "STREET" => $params["adminaddress1"], "CITY" => $params["admincity"], "STATE" => $params["adminstate"], "ZIP" => $params["adminpostcode"], "COUNTRY" => $params["admincountry"], "PHONE" => $params["adminfullphonenumber"], "FAX" => "", "EMAIL" => $params["adminemail"] ), "TECHCONTACT0" => array( "FIRSTNAME" => $params["adminfirstname"], "LASTNAME" => $params["adminlastname"], "ORGANIZATION" => $params["admincompanyname"], "STREET" => $params["adminaddress1"], "CITY" => $params["admincity"], "STATE" => $params["adminstate"], "ZIP" => $params["adminpostcode"], "COUNTRY" => $params["admincountry"], "PHONE" => $params["adminfullphonenumber"], "FAX" => "", "EMAIL" => $params["adminemail"] ), "BILLINGCONTACT0" => array( "FIRSTNAME" => $params["adminfirstname"], "LASTNAME" => $params["adminlastname"], "ORGANIZATION" => $params["admincompanyname"], "STREET" => $params["adminaddress1"], "CITY" => $params["admincity"], "STATE" => $params["adminstate"], "ZIP" => $params["adminpostcode"], "COUNTRY" => $params["admincountry"], "PHONE" => $params["adminfullphonenumber"], "FAX" => "", "EMAIL" => $params["adminemail"] ), "NAMESERVER" => $nameservers );
    $response = xirca_call($create_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_TransferDomain($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $transfer_domain_command = array( "COMMAND" => "TransferDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"], "AUTH" => $params["eppcode"], "ACTION" => "request" );
    $response = xirca_call($transfer_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_RenewDomain($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $renew_domain_command = array( "COMMAND" => "RenewDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"], "EXPIRATION" => date("Y") + 1, "PERIOD" => $params["regperiod"] );
    $response = xirca_call($renew_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_GetContactDetails($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "StatusDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"] );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

    $contacts = array( "Registrant" => $response["PROPERTY"]["OWNERCONTACT"][0], "Admin" => $response["PROPERTY"]["ADMINCONTACT"][0], "Technical" => $response["PROPERTY"]["TECHCONTACT"][0], "Billing" => $response["PROPERTY"]["BILLINGCONTACT"][0] );
    $values = array(  );
    foreach( $contacts as $type => $contactid ) 
    {
        $query_contact_command = array( "COMMAND" => "StatusContact", "CONTACT" => $contactid );
        $response = xirca_call($query_contact_command);
        $values[$type] = array( "First Name" => $response["PROPERTY"]["FIRSTNAME"][0], "Last Name" => $response["PROPERTY"]["LASTNAME"][0], "Organization" => $response["PROPERTY"]["ORGANIZATION"][0], "Street" => $response["PROPERTY"]["STREET"][0], "City" => $response["PROPERTY"]["CITY"][0], "State" => $response["PROPERTY"]["STATE"][0], "Zip Code" => $response["PROPERTY"]["ZIP"][0], "Country" => $response["PROPERTY"]["COUNTRY"][0], "Phone" => $response["PROPERTY"]["PHONE"][0], "Email" => $response["PROPERTY"]["EMAIL"][0] );
    }
    return $values;
}

function hexonet_SaveContactDetails($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $contact_domain_command = array( "COMMAND" => "ModifyDomain", "DOMAIN" => $params["sld"] . "." . $params["tld"] );
    foreach( $params["contactdetails"] as $type => $values ) 
    {
        if( $type == "Registrant" ) 
        {
            $type = "OWNER";
        }

        if( $type == "Admin" ) 
        {
            $type = "ADMIN";
        }

        if( $type == "Technical" ) 
        {
            $type = "TECH";
        }

        if( $type == "Billing" ) 
        {
            $type = "BILLING";
        }

        $contact_domain_command[$type . "CONTACT0"] = array( "FIRSTNAME" => $values["First Name"], "LASTNAME" => $values["Last Name"], "ORGANIZATION" => $values["Organization"], "STREET" => $values["Street"], "CITY" => $values["City"], "STATE" => $values["State"], "ZIP" => $values["Zip Code"], "COUNTRY" => $values["Country"], "PHONE" => $values["Phone"], "FAX" => "", "EMAIL" => $values["Email"] );
    }
    $response = xirca_call($contact_domain_command);
}

function hexonet_RegisterNameserver($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "AddNameserver", "NAMESERVER" => $params["nameserver"], "IPADDRESS" => array( $params["ipaddress"] ) );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_ModifyNameserver($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "ModifyNameserver", "NAMESERVER" => $params["nameserver"], "DELIPADDRESS" => array( $params["currentipaddress"] ), "ADDIPADDRESS" => array( $params["newipaddress"] ) );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function hexonet_DeleteNameserver($params)
{
    xirca_set_config("url", "https://coreapi.1api.net/api/call.cgi");
    xirca_set_config("login", $params["Username"]);
    xirca_set_config("password", $params["Password"]);
    if( $params["TestMode"] ) 
    {
        xirca_set_config("entity", "1234");
    }
    else
    {
        xirca_set_config("entity", "54cd");
    }

    $query_domain_command = array( "COMMAND" => "DeleteNameserver", "NAMESERVER" => $params["nameserver"] );
    $response = xirca_call($query_domain_command);
    if( $response["CODE"] != 200 ) 
    {
        return array( "error" => $response["DESCRIPTION"] );
    }

}

function xirca_set_config($type, $value)
{
    global $xirca_config;
    $xirca_config[$type] = $value;
}

function xirca_call($command, $user = "", $config = "")
{
    return xirca_parse_response(xirca_call_raw($command, $user, $config));
}

function xirca_call_raw($command, $user = "", $config = "")
{
    global $xirca_config;
    $url = $xirca_config["url"];
    $args = array(  );
    if( isset($xirca_config["login"]) ) 
    {
        $args["s_login"] = $xirca_config["login"];
    }

    if( isset($xirca_config["password"]) ) 
    {
        $args["s_pw"] = $xirca_config["password"];
    }

    if( isset($xirca_config["user"]) ) 
    {
        $args["s_user"] = $xirca_config["user"];
    }

    if( isset($xirca_config["entity"]) ) 
    {
        $args["s_entity"] = $xirca_config["entity"];
    }

    if( isset($_SESSION["AUTH"]["SUBUSER"]) ) 
    {
        $args["s_user"] .= " " . $_SESSION["AUTH"]["SUBUSER"];
    }

    if( strlen($user) ) 
    {
        $args["s_user"] .= " " . $user;
    }

    $args["s_command"] = xirca_encode_command($command);
    $curl = curl_init();
    if( $curl === false ) 
    {
        return "[RESPONSE]\nCODE=423\nAPI access error: curl_init failed\nEOF\n";
    }

    $postfields = array(  );
    foreach( $args as $key => $value ) 
    {
        $postfields[] = urlencode($key) . "=" . urlencode($value);
    }
    $postfields = implode("&", $postfields);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($curl);
    logModuleCall("hexonet", $command["COMMAND"], $args, $response, "", array( $args["s_login"], $args["s_pw"] ));
    return $response;
}

function xirca_call_retry($retries = 1, $command, $user = "", $config = "")
{
    for( $i = 1; $i <= $retries; $i++ ) 
    {
        $response = xirca_parse_response(xirca_call_raw($command, $user, $config));
        if( substr($response["CODE"], 0, 1) != "4" ) 
        {
            return $response;
        }

    }
}

function xirca_encode_command($commandarray)
{
    if( !is_array($commandarray) ) 
    {
        return $commandarray;
    }

    $command = "";
    foreach( $commandarray as $k => $v ) 
    {
        if( is_array($v) ) 
        {
            $v = xirca_encode_command($v);
            $l = explode("\n", trim($v));
            foreach( $l as $line ) 
            {
                $command .= (string) $k . $line . "\n";
            }
        }
        else
        {
            $v = preg_replace("/\r|\n/", "", $v);
            $command .= (string) $k . "=" . $v . "\n";
        }

    }
    return $command;
}

function xirca_parse_response($response)
{
    if( is_array($response) ) 
    {
        return $response;
    }

    if( !$response ) 
    {
        return array( "CODE" => "423", "DESCRIPTION" => "Empty response from API" );
    }

    $hash = array( "PROPERTY" => array(  ) );
    $rlist = explode("\n", $response);
    foreach( $rlist as $item ) 
    {
        if( preg_match("/^([^\\=]*[^\t\\= ])[\t ]*=[\t ]*(.*)\$/", $item, $m) ) 
        {
            list(, $attr, $value) = $m;
            $value = preg_replace("/[\t ]*\$/", "", $value);
            if( preg_match("/^property\\[([^\\]]*)\\]/i", $attr, $m) ) 
            {
                $prop = strtoupper($m[1]);
                $prop = preg_replace("/\\s/", "", $prop);
                if( in_array($prop, array_keys($hash["PROPERTY"])) ) 
                {
                    array_push($hash["PROPERTY"][$prop], $value);
                }
                else
                {
                    $hash["PROPERTY"][$prop] = array( $value );
                }

            }
            else
            {
                $hash[strtoupper($attr)] = $value;
            }

        }

    }
    return $hash;
}


