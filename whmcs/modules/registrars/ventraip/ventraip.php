<?php 
function ventraip_getConfigArray()
{
    $moduleName = explode("_", "ventraip_getConfigArray");
    $check_config_exists = mysql_fetch_assoc(full_query("SELECT count(`id`) as `n` FROM `tblregistrars` WHERE `registrar` = '" . mysql_real_escape_string($moduleName[0]) . "'"));
    if( 0 < (int) $check_config_exists["n"] ) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://wholesalesystem.com.au/ipcheck");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $ipaddress = $data;
    }
    else
    {
        $ipaddress = "";
    }

    $configarray = array( "resellerID" => array( "FriendlyName" => "Reseller ID", "Type" => "text", "Size" => "15", "Description" => "Enter your Reseller ID here" ), "apiKey" => array( "FriendlyName" => "API Key", "Type" => "text", "Size" => "45", "Description" => "Enter your API Key here" ), "doRenewal" => array( "FriendlyName" => "Force .AU Renewal on Transfer", "Type" => "yesno", "Size" => "1", "Description" => "Tick if you wish to perform a renewal on any .au domains submitted for transfer that are within 90 days of expiry." ), array( "FriendlyName" => "IP Address", "Description" => $ipaddress . " - You'll need to provide this IP address within the Wholesale System > API Information to gain access to the API" ), array( "FriendlyName" => "Version", "Description" => "1.5.3" ) );
    if( !class_exists("SoapClient") ) 
    {
        $configarray["Description"] = array( "Type" => "System", "Value" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    return $configarray;
}

function ventraip_GetNameservers($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    return ventraip_APICall("getns", $request, $params);
}

function ventraip_SaveNameservers($params)
{
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["dnsConfigType"] = 1;
    if( $params["ns1"] ) 
    {
        $request["nameServers"][0] = $params["ns1"];
    }

    if( $params["ns2"] ) 
    {
        $request["nameServers"][1] = $params["ns2"];
    }

    if( $params["ns3"] ) 
    {
        $request["nameServers"][2] = $params["ns3"];
    }

    if( $params["ns4"] ) 
    {
        $request["nameServers"][3] = $params["ns4"];
    }

    if( $params["ns5"] ) 
    {
        $request["nameServers"][4] = $params["ns5"];
    }

    return ventraip_APICall("savens", $request, $params);
}

function ventraip_GetRegistrarLock($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    if( preg_match("/\\.au/i", $params["tld"]) ) 
    {
    }
    else
    {
        $values = ventraip_APICall("getlockstatus", $request, $params);
        return $values["lockstatus"];
    }

}

function ventraip_SaveRegistrarLock($params)
{
    if( preg_match("/\\.au/i", $params["tld"]) ) 
    {
        $values["error"] = "Unable to lock/unlock .AU domain names";
        return $values;
    }

    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $values = ventraip_APICall("getlockstatus", $request, $params);
    if( $values["lockstatus"] == "locked" ) 
    {
        return ventraip_APICall("unlockdomain", $request, $params);
    }

    if( $values["lockstatus"] == "unlocked" ) 
    {
        return ventraip_APICall("lockdomain", $request, $params);
    }

    $values["error"] = "Unknown Domain Status, Contact Technical Support";
    return $values;
}

function ventraip_ReleaseDomain($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["tagName"] = $params["transfertag"];
    return ventraip_APICall("releasedomain", $request, $params);
}

function ventraip_RegisterDomain($params)
{
    $countries = new WHMCS\Utility\Country();
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["years"] = $params["regperiod"];
    if( $params["idprotection"] == "1" ) 
    {
        $request["idProtect"] = "Y";
    }
    else
    {
        $request["idProtect"] = "N";
    }

    if( $params["ns1"] ) 
    {
        $request["nameServers"][0] = $params["ns1"];
    }

    if( $params["ns2"] ) 
    {
        $request["nameServers"][1] = $params["ns2"];
    }

    if( $params["ns3"] ) 
    {
        $request["nameServers"][2] = $params["ns3"];
    }

    if( $params["ns4"] ) 
    {
        $request["nameServers"][3] = $params["ns4"];
    }

    if( $params["ns5"] ) 
    {
        $request["nameServers"][4] = $params["ns5"];
    }

    $request["registrant_firstname"] = $params["firstname"];
    $request["registrant_lastname"] = $params["lastname"];
    $request["registrant_address"][0] = $params["address1"];
    $request["registrant_address"][1] = $params["address2"];
    if( $params["address2"] ) 
    {
        $request["registrant_address"][1] = $params["address2"];
    }

    $request["registrant_suburb"] = $params["city"];
    if( !($country = ventraip_validateCountry($params["country"])) ) 
    {
        return array( "error" => "Registrant Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
    }

    $request["registrant_country"] = $country;
    if( $country == "AU" ) 
    {
        if( !($state = ventraip_validateAUState($params["state"])) ) 
        {
            return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
        }

        $request["registrant_state"] = $state;
    }
    else
    {
        $request["registrant_state"] = $params["state"];
    }

    $request["registrant_postcode"] = $params["postcode"];
    if( strtoupper($params["country"]) == "AU" || strtoupper($params["country"] == "AUSTRALIA") ) 
    {
        if( !($phoneNumber = ventraip_formatAUPhone($params["phonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        if( !($faxNumber = ventraip_formatAUPhone($params["phonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        $request["registrant_phone"] = $phoneNumber;
        $request["registrant_fax"] = $faxNumber;
    }
    else
    {
        $countrycode = $params["country"];
        $countrycode = $params["phonecc"];
        $request["registrant_phone"] = "+" . $countrycode . "." . $params["phonenumber"];
        $request["registrant_fax"] = "+" . $countrycode . "." . $params["phonenumber"];
    }

    $request["registrant_email"] = $params["email"];
    $request["technical_firstname"] = $params["adminfirstname"];
    $request["technical_lastname"] = $params["adminlastname"];
    $request["technical_address"][0] = $params["adminaddress1"];
    if( $params["adminaddress2"] ) 
    {
        $request["technical_address"][1] = $params["adminaddress2"];
    }

    $request["technical_suburb"] = $params["admincity"];
    if( !($country = ventraip_validateCountry($params["admincountry"])) ) 
    {
        return array( "error" => "Technical Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
    }

    $request["technical_country"] = $country;
    if( $country == "AU" ) 
    {
        if( !($state = ventraip_validateAUState($params["adminstate"])) ) 
        {
            return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
        }

        $request["technical_state"] = $state;
    }
    else
    {
        $request["technical_state"] = $params["adminstate"];
    }

    $request["technical_postcode"] = $params["adminpostcode"];
    if( strtoupper($params["admincountry"]) == "AU" || strtoupper($params["admincountry"] == "AUSTRALIA") ) 
    {
        if( !($phoneNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        if( !($faxNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        $request["technical_phone"] = $phoneNumber;
        $request["technical_fax"] = $faxNumber;
    }
    else
    {
        $countrycode = $params["admincountry"];
        $countrycode = $params["adminphonecc"];
        $request["technical_phone"] = "+" . $countrycode . "." . $params["adminphonenumber"];
        $request["technical_fax"] = "+" . $countrycode . "." . $params["adminphonenumber"];
    }

    $request["technical_email"] = $params["adminemail"];
    $request["admin_firstname"] = $params["adminfirstname"];
    $request["admin_lastname"] = $params["adminlastname"];
    $request["admin_address"][0] = $params["adminaddress1"];
    if( $params["adminaddress2"] ) 
    {
        $request["admin_address"][1] = $params["adminaddress2"];
    }

    $request["admin_suburb"] = $params["admincity"];
    if( !($country = ventraip_validateCountry($params["admincountry"])) ) 
    {
        return array( "error" => "Admin Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
    }

    $request["admin_country"] = $country;
    if( $country == "AU" ) 
    {
        if( !($state = ventraip_validateAUState($params["adminstate"])) ) 
        {
            return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
        }

        $request["admin_state"] = $state;
    }
    else
    {
        $request["admin_state"] = $params["adminstate"];
    }

    $request["admin_postcode"] = $params["adminpostcode"];
    if( strtoupper($params["admincountry"]) == "AU" || strtoupper($params["admincountry"] == "AUSTRALIA") ) 
    {
        if( !($phoneNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        if( !($faxNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        $request["admin_phone"] = $phoneNumber;
        $request["admin_fax"] = $faxNumber;
    }
    else
    {
        $countrycode = $params["admincountry"];
        $countrycode = $params["adminphonecc"];
        $request["admin_phone"] = "+" . $countrycode . "." . $params["adminphonenumber"];
        $request["admin_fax"] = "+" . $countrycode . "." . $params["adminphonenumber"];
    }

    $request["admin_email"] = $params["adminemail"];
    $request["billing_firstname"] = $params["adminfirstname"];
    $request["billing_lastname"] = $params["adminlastname"];
    $request["billing_address"][0] = $params["adminaddress1"];
    if( $params["adminaddress2"] ) 
    {
        $request["billing_address"][1] = $params["adminaddress2"];
    }

    $request["billing_suburb"] = $params["admincity"];
    if( !($country = ventraip_validateCountry($params["admincountry"])) ) 
    {
        return array( "error" => "Billing Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
    }

    $request["billing_country"] = $country;
    if( $country == "AU" ) 
    {
        if( !($state = ventraip_validateAUState($params["adminstate"])) ) 
        {
            return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
        }

        $request["billing_state"] = $state;
    }
    else
    {
        $request["billing_state"] = $params["adminstate"];
    }

    $request["billing_postcode"] = $params["adminpostcode"];
    if( strtoupper($params["admincountry"]) == "AU" || strtoupper($params["admincountry"] == "AUSTRALIA") ) 
    {
        if( !($phoneNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        if( !($faxNumber = ventraip_formatAUPhone($params["adminphonenumber"])) ) 
        {
            $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
            return $values;
        }

        $request["billing_phone"] = $phoneNumber;
        $request["billing_fax"] = $faxNumber;
    }
    else
    {
        $countrycode = $params["admincountry"];
        $countrycode = $params["adminphonecc"];
        $request["billing_phone"] = "+" . $countrycode . "." . $params["adminphonenumber"];
        $request["billing_fax"] = "+" . $countrycode . "." . $params["adminphonenumber"];
    }

    $request["billing_email"] = $params["adminemail"];
    if( end(explode(".", $params["tld"])) == "au" ) 
    {
        $request["registrantName"] = $params["additionalfields"]["Registrant Name"];
        $request["registrantID"] = $params["additionalfields"]["Registrant ID"];
        if( $params["additionalfields"]["Registrant ID Type"] == "Business Registration Number" ) 
        {
            $params["additionalfields"]["Registrant ID Type"] = "OTHER";
        }

        $request["registrantIDType"] = $params["additionalfields"]["Registrant ID Type"];
        $request["eligibilityType"] = $params["additionalfields"]["Eligibility Type"];
        if( $params["additionalfields"]["Eligibility ID Type"] == "Australian Company Number (ACN)" ) 
        {
            $request["eligibilityIDType"] = "ACN";
        }
        else
        {
            if( $params["additionalfields"]["Eligibility ID Type"] == "ACT Business Number" ) 
            {
                $request["eligibilityIDType"] = "ACT BN";
            }
            else
            {
                if( $params["additionalfields"]["Eligibility ID Type"] == "NSW Business Number" ) 
                {
                    $request["eligibilityIDType"] = "NSW BN";
                }
                else
                {
                    if( $params["additionalfields"]["Eligibility ID Type"] == "NT Business Number" ) 
                    {
                        $request["eligibilityIDType"] = "NT BN";
                    }
                    else
                    {
                        if( $params["additionalfields"]["Eligibility ID Type"] == "QLD Business Number" ) 
                        {
                            $request["eligibilityIDType"] = "QLD BN";
                        }
                        else
                        {
                            if( $params["additionalfields"]["Eligibility ID Type"] == "SA Business Number" ) 
                            {
                                $request["eligibilityIDType"] = "SA BN";
                            }
                            else
                            {
                                if( $params["additionalfields"]["Eligibility ID Type"] == "TAS Business Number" ) 
                                {
                                    $request["eligibilityIDType"] = "TAS BN";
                                }
                                else
                                {
                                    if( $params["additionalfields"]["Eligibility ID Type"] == "VIC Business Number" ) 
                                    {
                                        $request["eligibilityIDType"] = "VIC BN";
                                    }
                                    else
                                    {
                                        if( $params["additionalfields"]["Eligibility ID Type"] == "WA Business Number" ) 
                                        {
                                            $request["eligibilityIDType"] = "WA BN";
                                        }
                                        else
                                        {
                                            if( $params["additionalfields"]["Eligibility ID Type"] == "Trademark (TM)" ) 
                                            {
                                                $request["eligibilityIDType"] = "TM";
                                            }
                                            else
                                            {
                                                if( $params["additionalfields"]["Eligibility ID Type"] == "Other - Used to record an Incorporated Association number" ) 
                                                {
                                                    $request["eligibilityIDType"] = "OTHER";
                                                }
                                                else
                                                {
                                                    if( $params["additionalfields"]["Eligibility ID Type"] == "Australian Business Number (ABN)" ) 
                                                    {
                                                        $request["eligibilityIDType"] = "ABN";
                                                    }

                                                }

                                            }

                                        }

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

        $request["eligibilityID"] = $params["additionalfields"]["Eligibility ID"];
        $request["eligibilityName"] = $params["additionalfields"]["Eligibility Name"];
    }

    if( end(explode(".", $params["tld"])) == "uk" ) 
    {
        $request["tradingName"] = $params["additionalfields"]["Registrant Name"];
        $request["number"] = $params["additionalfields"]["Registrant ID"];
        $request["type"] = $params["additionalfields"]["Registrant ID Type"];
        $request["optout"] = $params["additionalfields"]["WHOIS Opt-out"];
    }

    return ventraip_APICall("register", $request, $params);
}

function ventraip_TransferDomain($params)
{
    $countries = new WHMCS\Utility\Country();
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    if( end(explode(".", $params["tld"])) != "uk" ) 
    {
        $request["authInfo"] = $params["transfersecret"];
        $request["firstname"] = $params["firstname"];
        $request["lastname"] = $params["lastname"];
        $request["organisation"] = $params["companyname"];
        $request["address"][0] = $params["address1"];
        if( $params["address2"] ) 
        {
            $request["address"][1] = $params["address2"];
        }

        $request["suburb"] = $params["city"];
        if( !($country = ventraip_validateCountry($params["country"])) ) 
        {
            return array( "error" => "Registrant Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
        }

        $request["country"] = $country;
        if( $country == "AU" ) 
        {
            if( !($state = ventraip_validateAUState($params["state"])) ) 
            {
                return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
            }

            $request["state"] = $state;
        }
        else
        {
            $request["state"] = $params["state"];
        }

        $request["postcode"] = $params["postcode"];
        if( strtoupper($params["country"]) == "AU" || strtoupper($params["country"] == "AUSTRALIA") ) 
        {
            if( !($phoneNumber = ventraip_formatAUPhone($params["phonenumber"])) ) 
            {
                $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
                return $values;
            }

            if( !($faxNumber = ventraip_formatAUPhone($params["phonenumber"])) ) 
            {
                $values["error"] = "Invalid or Incorrectly Formatted AU Phone Number Supplied";
                return $values;
            }

            $request["phone"] = $phoneNumber;
            $request["fax"] = $faxNumber;
        }
        else
        {
            $countrycode = $params["country"];
            $countrycode = $params["phonecc"];
            $request["phone"] = "+" . $countrycode . "." . $params["phonenumber"];
            $request["fax"] = "+" . $countrycode . "." . $params["phonenumber"];
        }

        $request["email"] = $params["email"];
        if( $params["doRenewal"] == "on" ) 
        {
            $request["doRenewal"] = 1;
        }
        else
        {
            $request["doRenewal"] = 0;
        }

        if( $params["idprotection"] == 1 ) 
        {
            $request["idProtect"] = "Y";
        }
        else
        {
            $request["idProtect"] = "N";
        }

        return ventraip_APICall("transfer", $request, $params);
    }

    return array( "error" => "Domain name transfers for .UK domain names are completely different to other extensions. The domain name must first exist in the wholesale before it is assigned to our tag. Once the domain has been submitted you need to request the existing registrar assign it to our tag mentioned below. Our system will automatically detect the inbound transfer to us and complete the process and set the domain live in your account. UK Tag: VENTRAIP-AU" );
}

function ventraip_IDProtectToggle($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    if( $params["protectenable"] == 1 ) 
    {
        return ventraip_APICall("idprotect", $request, $params);
    }

    return ventraip_APICall("idunprotect", $request, $params);
}

function ventraip_RenewDomain($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["years"] = $params["regperiod"];
    return ventraip_APICall("renew", $request, $params);
}

function ventraip_GetEmailForwarding($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    return ventraip_APICall("listMailForwards", $request, $params);
}

function ventraip_SaveEmailForwarding($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $existingrecords = ventraip_getemailforwarding($params);
    foreach( $params["prefix"] as $key => $value ) 
    {
        if( $params["prefix"][$key] != $existingrecords[$key]["prefix"] ) 
        {
            if( empty($params["prefix"][$key]) && empty($params["forwardto"][$key]) ) 
            {
                $request["domainName"] = $params["sld"] . "." . $params["tld"];
                $request["forwardID"] = $key;
                ventraip_APICall("deleteMailForward", $request, $params);
            }
            else
            {
                $request["source"] = $params["prefix"][$key] . "@" . $params["sld"] . "." . $params["tld"];
                $request["destination"] = $params["forwardto"][$key];
                ventraip_APICall("addMailForward", $request, $params);
            }

        }

    }
}

function ventraip_GetDNS($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    return ventraip_APICall("listDNSZone", $request, $params);
}

function ventraip_AddDNSRec($record, $params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["recordName"] = $record["hostname"];
    $request["recordType"] = $record["type"];
    $request["recordContent"] = $record["address"];
    $request["recordTTL"] = 86400;
    $request["recordPrio"] = $record["priority"];
    ventraip_APICall("addDNSRecord", $request, $params);
}

function ventraip_DelDNSRec($record, $params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["recordID"] = $record["recid"];
    ventraip_APICall("deleteDNSRecord", $request, $params);
}

function ventraip_SaveDNS($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $existingrecords = ventraip_getdns($params);
    foreach( $params["dnsrecords"] as $key => $values ) 
    {
        if( $values && $values["address"] && !$values["recid"] ) 
        {
            ventraip_adddnsrec($values, $params);
        }

    }
    for( $i = 0; $i <= count($existingrecords); $i++ ) 
    {
        if( $params["dnsrecords"][$i]["address"] != $existingrecords[$i]["address"] && $params["dnsrecords"][$i]["recid"] ) 
        {
            ventraip_adddnsrec($params["dnsrecords"][$i], $params);
            ventraip_deldnsrec($params["dnsrecords"][$i], $params);
        }

    }
}

function ventraip_Sync($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    return ventraip_APICall("sync", $request, $params);
}

function ventraip_TransferSync($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    return ventraip_APICall("sync", $request, $params);
}

function ventraip_SaveContactDetails($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["registrant_firstname"] = $params["contactdetails"]["Registrant"]["First Name"];
    $request["registrant_lastname"] = $params["contactdetails"]["Registrant"]["Last Name"];
    $request["registrant_address"] = array( $params["contactdetails"]["Registrant"]["Address 1"], $params["contactdetails"]["Registrant"]["Address 2"] );
    $request["registrant_email"] = $params["contactdetails"]["Registrant"]["Email"];
    $request["registrant_suburb"] = $params["contactdetails"]["Registrant"]["City"];
    $request["registrant_postcode"] = $params["contactdetails"]["Registrant"]["Postcode"];
    if( !($country = ventraip_validateCountry($params["contactdetails"]["Registrant"]["Country"])) ) 
    {
        return array( "error" => "Registrant Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
    }

    $request["registrant_country"] = $country;
    if( $country == "AU" ) 
    {
        if( !($state = ventraip_validateAUState($params["contactdetails"]["Registrant"]["State"])) ) 
        {
            return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
        }

        $request["registrant_state"] = $state;
    }
    else
    {
        $request["registrant_state"] = $params["contactdetails"]["Registrant"]["State"];
    }

    if( $country == "AU" ) 
    {
        if( !($phone = ventraip_formatAUPhone($params["contactdetails"]["Registrant"]["Phone"])) ) 
        {
            return array( "error" => "Registrant Phone Number Is Not Valid Australian Format" );
        }

        $request["registrant_phone"] = $phone;
    }
    else
    {
        $request["registrant_phone"] = $params["contactdetails"]["Registrant"]["Phone"];
    }

    if( isset($params["contactdetails"]["Registrant"]["Fax"]) && 1 < strlen($params["contactdetails"]["Registrant"]["Fax"]) ) 
    {
        if( $country == "AU" ) 
        {
            if( !($fax = ventraip_formatAUPhone($params["contactdetails"]["Registrant"]["Fax"])) ) 
            {
                return array( "error" => "Registrant Fax Number Is Not Valid Australian Format" );
            }

            $request["registrant_fax"] = $fax;
        }
        else
        {
            $request["registrant_fax"] = $params["contactdetails"]["Registrant"]["Fax"];
        }

    }

    if( isset($params["contactdetails"]["Admin"]) ) 
    {
        $request["admin_firstname"] = $params["contactdetails"]["Admin"]["First Name"];
        $request["admin_lastname"] = $params["contactdetails"]["Admin"]["Last Name"];
        $request["admin_address"] = array( $params["contactdetails"]["Admin"]["Address 1"], $params["contactdetails"]["Admin"]["Address 2"] );
        $request["admin_suburb"] = $params["contactdetails"]["Admin"]["City"];
        $request["admin_postcode"] = $params["contactdetails"]["Admin"]["Postcode"];
        $request["admin_email"] = $params["contactdetails"]["Admin"]["Email"];
        if( !($country = ventraip_validateCountry($params["contactdetails"]["Admin"]["Country"])) ) 
        {
            return array( "error" => "Admin Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
        }

        $request["admin_country"] = $country;
        if( $country == "AU" ) 
        {
            if( !($state = ventraip_validateAUState($params["contactdetails"]["Admin"]["State"])) ) 
            {
                return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
            }

            $request["admin_state"] = $state;
        }
        else
        {
            $request["admin_state"] = $params["contactdetails"]["Admin"]["State"];
        }

        if( $country == "AU" ) 
        {
            if( !($phone = ventraip_formatAUPhone($params["contactdetails"]["Admin"]["Phone"])) ) 
            {
                return array( "error" => "Admin Phone Number Is Not Valid Australian Format" );
            }

            $request["admin_phone"] = $phone;
        }
        else
        {
            $request["admin_phone"] = $params["contactdetails"]["Admin"]["Phone"];
        }

        if( isset($params["contactdetails"]["Admin"]["Fax"]) && 1 < strlen($params["contactdetails"]["Admin"]["Fax"]) ) 
        {
            if( $country == "AU" ) 
            {
                if( !($fax = ventraip_formatAUPhone($params["contactdetails"]["Admin"]["Fax"])) ) 
                {
                    return array( "error" => "Admin Fax Number Is Not Valid Australian Format" );
                }

                $request["admin_fax"] = $fax;
            }
            else
            {
                $request["admin_fax"] = $params["contactdetails"]["Admin"]["Fax"];
            }

        }

    }

    if( isset($params["contactdetails"]["Tech"]) ) 
    {
        $request["technical_firstname"] = $params["contactdetails"]["Tech"]["First Name"];
        $request["technical_lastname"] = $params["contactdetails"]["Tech"]["Last Name"];
        $request["technical_address"] = array( $params["contactdetails"]["Tech"]["Address 1"], $params["contactdetails"]["Tech"]["Address 2"] );
        $request["technical_suburb"] = $params["contactdetails"]["Tech"]["City"];
        $request["technical_postcode"] = $params["contactdetails"]["Tech"]["Postcode"];
        $request["technical_email"] = $params["contactdetails"]["Tech"]["Email"];
        if( !($country = ventraip_validateCountry($params["contactdetails"]["Tech"]["Country"])) ) 
        {
            return array( "error" => "Tech Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
        }

        $request["technical_country"] = $country;
        if( $country == "AU" ) 
        {
            if( !($state = ventraip_validateAUState($params["contactdetails"]["Tech"]["State"])) ) 
            {
                return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
            }

            $request["technical_state"] = $state;
        }
        else
        {
            $request["technical_state"] = $params["contactdetails"]["Tech"]["State"];
        }

        if( $country == "AU" ) 
        {
            if( !($phone = ventraip_formatAUPhone($params["contactdetails"]["Tech"]["Phone"])) ) 
            {
                return array( "error" => "Tech Phone Number Is Not Valid Australian Format" );
            }

            $request["technical_phone"] = $phone;
        }
        else
        {
            $request["technical_phone"] = $params["contactdetails"]["Tech"]["Phone"];
        }

        if( isset($params["contactdetails"]["Tech"]["Fax"]) && 1 < strlen($params["contactdetails"]["Tech"]["Fax"]) ) 
        {
            if( $country == "AU" ) 
            {
                if( !($fax = ventraip_formatAUPhone($params["contactdetails"]["Tech"]["Fax"])) ) 
                {
                    return array( "error" => "Tech Fax Number Is Not Valid Australian Format" );
                }

                $request["technical_fax"] = $fax;
            }
            else
            {
                $request["technical_fax"] = $params["contactdetails"]["Admin"]["Fax"];
            }

        }

    }

    if( isset($params["contactdetails"]["Billing"]) ) 
    {
        $request["billing_firstname"] = $params["contactdetails"]["Billing"]["First Name"];
        $request["billing_lastname"] = $params["contactdetails"]["Billing"]["Last Name"];
        $request["billing_address"] = array( $params["contactdetails"]["Billing"]["Address 1"], $params["contactdetails"]["Billing"]["Address 2"] );
        $request["billing_suburb"] = $params["contactdetails"]["Billing"]["City"];
        $request["billing_postcode"] = $params["contactdetails"]["Billing"]["Postcode"];
        $request["billing_email"] = $params["contactdetails"]["Billing"]["Email"];
        if( !($country = ventraip_validateCountry($params["contactdetails"]["Billing"]["Country"])) ) 
        {
            return array( "error" => "Billing Country must be entered as 2 characters - ISO 3166 Standard. EG. AU" );
        }

        $request["billing_country"] = $country;
        if( $country == "AU" ) 
        {
            if( !($state = ventraip_validateAUState($params["contactdetails"]["Billing"]["State"])) ) 
            {
                return array( "error" => "A Valid Australian State Name Must Be Supplied, EG. NSW, VIC" );
            }

            $request["billing_state"] = $state;
        }
        else
        {
            $request["billing_state"] = $params["contactdetails"]["Billing"]["State"];
        }

        if( $country == "AU" ) 
        {
            if( !($phone = ventraip_formatAUPhone($params["contactdetails"]["Billing"]["Phone"])) ) 
            {
                return array( "error" => "Billing Phone Number Is Not Valid Australian Format" );
            }

            $request["billing_phone"] = $phone;
        }
        else
        {
            $request["billing_phone"] = $params["contactdetails"]["Billing"]["Phone"];
        }

        if( isset($params["contactdetails"]["Billing"]["Fax"]) && 1 < strlen($params["contactdetails"]["Billing"]["Fax"]) ) 
        {
            if( $country == "AU" ) 
            {
                if( !($fax = ventraip_formatAUPhone($params["contactdetails"]["Billing"]["Fax"])) ) 
                {
                    return array( "error" => "Billing Fax Number Is Not Valid Australian Format" );
                }

                $request["billing_fax"] = $fax;
            }
            else
            {
                $request["billing_fax"] = $params["contactdetails"]["Billing"]["Fax"];
            }

        }

    }

    return ventraip_APICall("savecontacts", $request, $params);
}

function ventraip_GetContactDetails($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $idProtectStatus = ventraip_APICall("getidprotectionstatus", $request, $params);
    if( $idProtectStatus["idProtect"] == "Enabled" ) 
    {
        $values["error"] = "ID Protection Is Enabled - Unable To Update Domain Contacts";
        return $values;
    }

    return ventraip_APICall("getcontacts", $request, $params);
}

function ventraip_GetEPPCode($params)
{
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $eppCode = ventraip_APICall("geteppcode", $request, $params);
    $values["eppcode"] = $eppCode["eppCode"];
    return $values;
}

function ventraip_RegisterNameserver($params)
{
    $domainName = "." . $params["sld"] . "." . $params["tld"];
    $result = preg_split("/" . $domainName . "/i", $params["nameserver"]);
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["host"] = $result[0];
    $request["ipAddress"] = array( $params["ipaddress"] );
    return ventraip_APICall("addhost", $request, $params);
}

function ventraip_ModifyNameserver($params)
{
    $domainName = "." . $params["sld"] . "." . $params["tld"];
    $result = preg_split("/" . $domainName . "/i", $params["nameserver"]);
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["host"] = $result[0];
    $request["ipAddress"] = array( $params["newipaddress"] );
    $results = ventraip_APICall("addhostip", $request, $params);
    if( isset($results["error"]) ) 
    {
        return $results;
    }

    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["host"] = $result[0];
    $request["ipAddress"] = array( $params["currentipaddress"] );
    $results = ventraip_APICall("delhostip", $request, $params);
    if( isset($results["error"]) ) 
    {
        return $results;
    }

}

function ventraip_DeleteNameserver($params)
{
    $domainName = "." . $params["sld"] . "." . $params["tld"];
    $result = preg_split("/" . $domainName . "/i", $params["nameserver"]);
    $request = array(  );
    $request["domainName"] = $params["sld"] . "." . $params["tld"];
    $request["host"] = $result[0];
    return ventraip_APICall("delhost", $request, $params);
}

function ventraip_APICall($command, $request, $params)
{
    if( !class_exists("SoapClient") ) 
    {
        logModuleCall("ventraip", $command, $request, "This module requires the PHP SOAP extension which is not currently compiled into your PHP build. No API call was attempted.");
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    $apiurl = "https://api.wholesalesystem.com.au/?wsdl";
    $values = array(  );
    $soaprequest = $request;
    $soaprequest["resellerID"] = $params["resellerID"];
    $soaprequest["apiKey"] = $params["apiKey"];
    try
    {
        $client = new SoapClient(NULL, array( "location" => $apiurl, "uri" => "" ));
        if( $command == "register" ) 
        {
            $params["tld"] = end(explode(".", $params["tld"]));
            if( $params["tld"] == "au" ) 
            {
                $soapresult = get_object_vars($client->domainRegisterAU($soaprequest));
            }
            else
            {
                if( $params["tld"] == "uk" ) 
                {
                    $soapresult = get_object_vars($client->domainRegisterUK($soaprequest));
                }
                else
                {
                    $soapresult = get_object_vars($client->domainRegister($soaprequest));
                }

            }

        }
        else
        {
            if( $command == "transfer" ) 
            {
                if( $params["tld"] == "uk" ) 
                {
                    $soapresult = get_object_vars($client->domainTransferUK($soaprequest));
                }
                else
                {
                    $soapresult = get_object_vars($client->transferDomain($soaprequest));
                }

            }
            else
            {
                if( $command == "renew" ) 
                {
                    $soapresult = get_object_vars($client->renewDomain($soaprequest));
                }
                else
                {
                    if( $command == "getns" ) 
                    {
                        $soapresult = get_object_vars($client->domainInfo($soaprequest));
                        $values["ns1"] = $soapresult["nameServers"][0];
                        $values["ns2"] = $soapresult["nameServers"][1];
                        $values["ns3"] = $soapresult["nameServers"][2];
                        $values["ns4"] = $soapresult["nameServers"][3];
                        $values["ns5"] = $soapresult["nameServers"][4];
                    }
                    else
                    {
                        if( $command == "savens" ) 
                        {
                            $soapresult = get_object_vars($client->updateNameServers($soaprequest));
                        }
                        else
                        {
                            if( $command == "getlockstatus" ) 
                            {
                                $soapresult = get_object_vars($client->domainInfo($soaprequest));
                                if( $soapresult["domain_status"] == "clientTransferProhibited" ) 
                                {
                                    $values["lockstatus"] = "locked";
                                }
                                else
                                {
                                    $values["lockstatus"] = "unlocked";
                                }

                            }
                            else
                            {
                                if( $command == "getidprotectionstatus" ) 
                                {
                                    $soapresult = get_object_vars($client->domainInfo($soaprequest));
                                    if( $soapresult["idProtect"] == "Enabled" ) 
                                    {
                                        $values["idProtect"] = "Enabled";
                                    }
                                    else
                                    {
                                        $values["idProtect"] = "Disabled";
                                    }

                                }
                                else
                                {
                                    if( $command == "geteppcode" ) 
                                    {
                                        $soapresult = get_object_vars($client->domainInfo($soaprequest));
                                        $values["eppCode"] = $soapresult["domainPassword"];
                                    }
                                    else
                                    {
                                        if( $command == "getcontacts" ) 
                                        {
                                            $soapresult = get_object_vars($client->listContacts($soaprequest));
                                            $values["Registrant"]["First Name"] = $soapresult["registrant"]->firstname;
                                            $values["Registrant"]["Last Name"] = $soapresult["registrant"]->lastname;
                                            $values["Registrant"]["Address 1"] = $soapresult["registrant"]->address1;
                                            $values["Registrant"]["Address 2"] = $soapresult["registrant"]->address2;
                                            $values["Registrant"]["City"] = $soapresult["registrant"]->suburb;
                                            $values["Registrant"]["State"] = $soapresult["registrant"]->state;
                                            $values["Registrant"]["Postcode"] = $soapresult["registrant"]->postcode;
                                            $values["Registrant"]["Country"] = $soapresult["registrant"]->country;
                                            $values["Registrant"]["Phone"] = $soapresult["registrant"]->phone;
                                            $values["Registrant"]["Fax"] = $soapresult["registrant"]->fax;
                                            $values["Registrant"]["Email"] = $soapresult["registrant"]->email;
                                            if( isset($soapresult["admin"]) ) 
                                            {
                                                $values["Admin"]["First Name"] = $soapresult["admin"]->firstname;
                                                $values["Admin"]["Last Name"] = $soapresult["admin"]->lastname;
                                                $values["Admin"]["Address 1"] = $soapresult["admin"]->address1;
                                                $values["Admin"]["Address 2"] = $soapresult["admin"]->address2;
                                                $values["Admin"]["City"] = $soapresult["admin"]->suburb;
                                                $values["Admin"]["State"] = $soapresult["admin"]->state;
                                                $values["Admin"]["Postcode"] = $soapresult["admin"]->postcode;
                                                $values["Admin"]["Country"] = $soapresult["admin"]->country;
                                                $values["Admin"]["Phone"] = $soapresult["admin"]->phone;
                                                $values["Admin"]["Fax"] = $soapresult["admin"]->fax;
                                                $values["Admin"]["Email"] = $soapresult["admin"]->email;
                                            }

                                            if( isset($soapresult["billing"]) ) 
                                            {
                                                $values["Billing"]["First Name"] = $soapresult["billing"]->firstname;
                                                $values["Billing"]["Last Name"] = $soapresult["billing"]->lastname;
                                                $values["Billing"]["Address 1"] = $soapresult["billing"]->address1;
                                                $values["Billing"]["Address 2"] = $soapresult["billing"]->address2;
                                                $values["Billing"]["City"] = $soapresult["billing"]->suburb;
                                                $values["Billing"]["State"] = $soapresult["billing"]->state;
                                                $values["Billing"]["Postcode"] = $soapresult["billing"]->postcode;
                                                $values["Billing"]["Country"] = $soapresult["billing"]->country;
                                                $values["Billing"]["Phone"] = $soapresult["billing"]->phone;
                                                $values["Billing"]["Fax"] = $soapresult["billing"]->fax;
                                                $values["Billing"]["Email"] = $soapresult["billing"]->email;
                                            }

                                            if( isset($soapresult["tech"]) ) 
                                            {
                                                $values["Tech"]["First Name"] = $soapresult["tech"]->firstname;
                                                $values["Tech"]["Last Name"] = $soapresult["tech"]->lastname;
                                                $values["Tech"]["Address 1"] = $soapresult["tech"]->address1;
                                                $values["Tech"]["Address 2"] = $soapresult["tech"]->address2;
                                                $values["Tech"]["City"] = $soapresult["tech"]->suburb;
                                                $values["Tech"]["State"] = $soapresult["tech"]->state;
                                                $values["Tech"]["Postcode"] = $soapresult["tech"]->postcode;
                                                $values["Tech"]["Country"] = $soapresult["tech"]->country;
                                                $values["Tech"]["Phone"] = $soapresult["tech"]->phone;
                                                $values["Tech"]["Fax"] = $soapresult["tech"]->fax;
                                                $values["Tech"]["Email"] = $soapresult["tech"]->email;
                                            }

                                        }
                                        else
                                        {
                                            if( $command == "savecontacts" ) 
                                            {
                                                $soapresult = get_object_vars($client->updateContact($soaprequest));
                                            }
                                            else
                                            {
                                                if( $command == "addhost" ) 
                                                {
                                                    $soapresult = get_object_vars($client->addHost($soaprequest));
                                                }
                                                else
                                                {
                                                    if( $command == "delhost" ) 
                                                    {
                                                        $soapresult = get_object_vars($client->deleteHost($soaprequest));
                                                    }
                                                    else
                                                    {
                                                        if( $command == "addhostip" ) 
                                                        {
                                                            $soapresult = get_object_vars($client->addHostIP($soaprequest));
                                                        }
                                                        else
                                                        {
                                                            if( $command == "delhostip" ) 
                                                            {
                                                                $soapresult = get_object_vars($client->deleteHostIP($soaprequest));
                                                            }
                                                            else
                                                            {
                                                                if( $command == "sync" ) 
                                                                {
                                                                    $soapresult = get_object_vars($client->domainInfo($soaprequest));
                                                                    if( in_array($soapresult["domain_status"], array( "ok", "clientTransferProhibited" )) ) 
                                                                    {
                                                                        $values["status"] = "Active";
                                                                        $values["active"] = true;
                                                                    }
                                                                    else
                                                                    {
                                                                        if( $soapresult["domain_status"] == "Transferred Away" ) 
                                                                        {
                                                                            update_query("tbldomains", array( "status" => "Cancelled" ), array( "domain" => $params["sld"] . "." . $params["tld"] ));
                                                                        }

                                                                    }

                                                                    $values["expirydate"] = substr($soapresult["domain_expiry"], 0, 10);
                                                                }
                                                                else
                                                                {
                                                                    if( $command == "lockdomain" ) 
                                                                    {
                                                                        $soapresult = get_object_vars($client->lockDomain($soaprequest));
                                                                    }
                                                                    else
                                                                    {
                                                                        if( $command == "unlockdomain" ) 
                                                                        {
                                                                            $soapresult = get_object_vars($client->unlockDomain($soaprequest));
                                                                        }
                                                                        else
                                                                        {
                                                                            if( $command == "idprotect" ) 
                                                                            {
                                                                                $soapresult = get_object_vars($client->enableIDProtection($soaprequest));
                                                                                if( $soapresult["status"] != "OK" ) 
                                                                                {
                                                                                    $values = array( "error" => $soapresult["errorMessage"] );
                                                                                }

                                                                            }
                                                                            else
                                                                            {
                                                                                if( $command == "idunprotect" ) 
                                                                                {
                                                                                    $soapresult = get_object_vars($client->disableIDProtection($soaprequest));
                                                                                    if( $soapresult["status"] != "OK" ) 
                                                                                    {
                                                                                        $values = array( "error" => $soapresult["errorMessage"] );
                                                                                    }

                                                                                }
                                                                                else
                                                                                {
                                                                                    if( $command == "releasedomain" ) 
                                                                                    {
                                                                                        $soapresult = get_object_vars($client->domainReleaseUK($soaprequest));
                                                                                    }
                                                                                    else
                                                                                    {
                                                                                        if( $command == "listMailForwards" ) 
                                                                                        {
                                                                                            $soapresult = get_object_vars($client->listMailForwards($soaprequest));
                                                                                            if( is_array($soapresult["forwards"]) ) 
                                                                                            {
                                                                                                foreach( $soapresult["forwards"] as $forward ) 
                                                                                                {
                                                                                                    $forward = get_object_vars($forward);
                                                                                                    $values[$forward["id"]]["prefix"] = str_replace("@" . $request["domainName"], "", $forward["source"]);
                                                                                                    $values[$forward["id"]]["forwardto"] = $forward["destination"];
                                                                                                }
                                                                                            }

                                                                                        }
                                                                                        else
                                                                                        {
                                                                                            if( $command == "addMailForward" ) 
                                                                                            {
                                                                                                $soapresult = get_object_vars($client->addMailForward($soaprequest));
                                                                                            }
                                                                                            else
                                                                                            {
                                                                                                if( $command == "deleteMailForward" ) 
                                                                                                {
                                                                                                    $soapresult = get_object_vars($client->deleteMailForward($soaprequest));
                                                                                                }
                                                                                                else
                                                                                                {
                                                                                                    if( $command == "deleteDNSRecord" ) 
                                                                                                    {
                                                                                                        $soapresult = get_object_vars($client->deleteDNSRecord($soaprequest));
                                                                                                    }
                                                                                                    else
                                                                                                    {
                                                                                                        if( $command == "addDNSRecord" ) 
                                                                                                        {
                                                                                                            $soapresult = get_object_vars($client->addDNSRecord($soaprequest));
                                                                                                        }
                                                                                                        else
                                                                                                        {
                                                                                                            if( $command == "listDNSZone" ) 
                                                                                                            {
                                                                                                                $soapresult = get_object_vars($client->listDNSZone($soaprequest));
                                                                                                                if( $soapresult["status"] == "OK" && is_array($soapresult["records"]) ) 
                                                                                                                {
                                                                                                                    $values = array(  );
                                                                                                                    foreach( $soapresult["records"] as $record ) 
                                                                                                                    {
                                                                                                                        $record = get_object_vars($record);
                                                                                                                        if( $record["type"] != "SOA" ) 
                                                                                                                        {
                                                                                                                            if( $record["type"] == "MX" ) 
                                                                                                                            {
                                                                                                                                $values[] = array( "hostname" => $record["hostName"], "type" => $record["type"], "address" => $record["content"], "priority" => $record["prio"], "recid" => $record["id"] );
                                                                                                                            }
                                                                                                                            else
                                                                                                                            {
                                                                                                                                $values[] = array( "hostname" => $record["hostName"], "type" => $record["type"], "address" => $record["content"], "recid" => $record["id"] );
                                                                                                                            }

                                                                                                                        }

                                                                                                                    }
                                                                                                                }

                                                                                                            }

                                                                                                        }

                                                                                                    }

                                                                                                }

                                                                                            }

                                                                                        }

                                                                                    }

                                                                                }

                                                                            }

                                                                        }

                                                                    }

                                                                }

                                                            }

                                                        }

                                                    }

                                                }

                                            }

                                        }

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

        $not_in_status = array( "OK", "OK_PENDING_REGO", "OK_TRANSFER_PENDING", "OK_TRANSFER_EMAILPENDING", "OK_TRANSFER_EMAIL", "OK_TRANSFER_UK_PENDING", "OK_TRANSFER_WAITING_AUTH" );
        if( !in_array($soapresult["status"], $not_in_status) ) 
        {
            $values["error"] = ($soapresult["errorMessage"] ? $soapresult["errorMessage"] : serialize($soapresult));
        }

        logModuleCall("ventraip", $command, $soaprequest, $soapresult, $soapresult, array( $params["resellerID"], $params["apiKey"] ));
    }
    catch( SoapFault $e ) 
    {
        $values["error"] = ($e->errorMessage ? $e->status . " - " . $e->errorMessage : $e);
        logModuleCall("ventraip", $command, $soaprequest, $soapresult, $e, array( $params["resellerID"], $params["apiKey"] ));
    }
    return $values;
}

function ventraip_formatAUPhone($phoneNumber)
{
    $phoneNumber = preg_replace("/^\\+61/", "", $phoneNumber);
    $phoneNumber = preg_replace("/^61/", "", $phoneNumber);
    $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
    if( strlen($phoneNumber) == 9 ) 
    {
        $phoneNumber = preg_replace("/(^4[0-9]{2})([0-9]{3})([0-9]{3})/", "+61.\$1\$2\$3", $phoneNumber);
        $phoneNumber = preg_replace("/(^[2378]{1})([0-9]{4})([0-9]{4})/", "+61.\$1\$2\$3", $phoneNumber);
        return $phoneNumber;
    }

    if( strlen($phoneNumber) == 10 ) 
    {
        $phoneNumber = preg_replace("/0(4[0-9]{2})([0-9]{3})([0-9]{3})/", "+61.\$1\$2\$3", $phoneNumber);
        $phoneNumber = preg_replace("/0([2378]{1})([0-9]{4})([0-9]{4})/", "+61.\$1\$2\$3", $phoneNumber);
        return $phoneNumber;
    }

    return false;
}

function ventraip_validateCountry($country)
{
    $country = strtoupper($country);
    $cc = "AF,AX,AL,DZ,AS,AD,AO,AI,AQ,AG,AR,AM,AW,AU,AT,AZ,BS,BH,BD,BB,BY,BE,BZ,BJ,BM,BT,BO,BQ,BA,BW,BV,BR,IO,BN,BG,BF,BI,\n\n            KH,CM,CA,CV,KY,CF,TD,CL,CN,CX,CC,CO,KM,CG,CD,CK,CR,CI,HR,CU,CW,CY,CZ,DK,DJ,DM,DO,EC,EG,SV,GQ,ER,EE,ET,FK,FO,FJ,FI,FR,\n\n            GF,PF,TF,GA,GM,GE,DE,GH,GI,GR,GL,GD,GP,GU,GT,GG,GN,GW,GY,HT,HM,VA,HN,HK,HU,IS,IN,ID,IR,IQ,IE,IM,IL,IT,JM,JP,JE,JO,KZ,KE,\n\n            KI,KP,KR,KW,KG,LA,LV,LB,LS,LR,LY,LI,LT,LU,MO,MK,MG,MW,MY,MV,ML,MT,MH,MQ,MR,MU,YT,MX,FM,MD,MC,MN,ME,MS,MA,MZ,MM,NA,NR,NP,\n\n            NL,NC,NZ,NI,NE,NG,NU,NF,MP,NO,OM,PK,PW,PS,PA,PG,PY,PE,PH,PN,PL,PT,PR,QA,RE,RO,RU,RW,BL,SH,KN,LC,MF,PM,VC,WS,SM,ST,SA,SN,\n\n            RS,SC,SL,SG,SX,SK,SI,SB,SO,ZA,GS,SS,ES,LK,SD,SR,SJ,SZ,SE,CH,SY,TW,TJ,TZ,TH,TL,TG,TK,TO,TT,TN,TR,TM,TC,TV,UG,UA,AE,GB,US,\n\n            UM,UY,UZ,VU,VE,VN,VG,VI,WF,EH,YE,ZM,ZW";
    $ccArray = explode(",", $cc);
    $needle = array_search($country, $ccArray);
    if( $needle === false ) 
    {
        return false;
    }

    return $ccArray[$needle];
}

function ventraip_validateAUState($state)
{
    $state = trim($state);
    $state = preg_replace("/ /", "", $state);
    $state = preg_replace("/\\./", "", $state);
    $state = strtoupper($state);
    switch( $state ) 
    {
        case "VICTORIA":
        case "VIC":
            return "VIC";
        case "NEWSOUTHWALES":
        case "NSW":
            return "NSW";
        case "QUEENSLAND":
        case "QLD":
            return "QLD";
        case "AUSTRALIANCAPITALTERRITORY":
        case "AUSTRALIACAPITALTERRITORY":
        case "ACT":
            return "ACT";
        case "SOUTHAUSTRALIA":
        case "SA":
            return "SA";
        case "WESTERNAUSTRALIA":
        case "WA":
            return "WA";
        case "NORTHERNTERRITORY":
        case "NT":
            return "NT";
        case "TASMANIA":
        case "TAS":
            return "TAS";
    }
    return false;
}


