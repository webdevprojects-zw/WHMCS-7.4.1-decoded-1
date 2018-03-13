<?php 
function ovh_getConfigArray()
{
    $configarray = array( "FriendlyName" => array( "Type" => "System", "Value" => "OVH" ), "Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your nic handle here xxxxxxx-ovh" ), "Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your password here" ), "TestMode" => array( "Type" => "yesno", "Description" => "Enable Test Mode" ) );
    if( !class_exists("SoapClient") ) 
    {
        $configarray["Description"] = array( "Type" => "System", "Value" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    return $configarray;
}

function ovh_GetNameservers($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $information = $soap->domainInfo($session, (string) $domain);
        $values["ns1"] = $information->dns[0]->name;
        $values["ns2"] = $information->dns[1]->name;
        $values["ns3"] = $information->dns[2]->name;
        $values["ns4"] = $information->dns[3]->name;
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Get Nameservers", $soap->__getLastRequest(), $e . $information, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}

function ovh_SaveNameservers($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $nameserver1 = $params["ns1"];
        $nameserver2 = $params["ns2"];
        $nameserver3 = $params["ns3"];
        $nameserver4 = $params["ns4"];
        $nameserver5 = $params["ns5"];
        $result = $soap->domainDnsUpdate($session, (string) $domain, (string) $nameserver1, "", (string) $nameserver2, "", (string) $nameserver3, "", (string) $nameserver4, "", (string) $nameserver5, "");
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Save Nameservers", $soap->__getLastRequest(), $e . $result, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}

function ovh_GetRegistrarLock($params)
{
    ini_set("display_errors", "off");
    error_reporting(0);
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $information = $soap->domainLockStatus($session, (string) $domain);
        $lock = $information;
    }
    catch( SoapFault $fault ) 
    {
        logModuleCall("ovh", "Get Registrar Lock", $soap->__getLastRequest(), $fault, NULL, $session);
        if( $fault ) 
        {
            return array( "error" => $fault );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    if( $lock ) 
    {
        $lockstatus = "locked";
    }
    else
    {
        $lockstatus = "unlocked";
    }

    $soap->logout($session);
    return $lockstatus;
}

function ovh_SaveRegistrarLock($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        if( $params["lockenabled"] ) 
        {
            $information = $soap->domainLock($session, (string) $domain);
        }
        else
        {
            $information = $soap->domainUnlock($session, (string) $domain);
        }

    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Get Registrar Lock", $soap->__getLastRequest(), $e . $information, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}

function ovh_RegisterDomain($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $regperiod = $params["regperiod"];
        $nameserver1 = $params["ns1"];
        $nameserver2 = $params["ns2"];
        $nameserver3 = $params["ns3"];
        $nameserver4 = $params["ns4"];
        $nameserver5 = $params["ns5"];
        $RegistrantFirstName = $params["firstname"];
        $RegistrantLastName = $params["lastname"];
        $RegistrantCompanyName = $params["companyname"];
        $RegistrantAddress1 = $params["address1"];
        $RegistrantAddress2 = $params["address2"];
        $RegistrantCity = $params["city"];
        $RegistrantStateProvince = $params["state"];
        $RegistrantPostalCode = $params["postcode"];
        $RegistrantCountry = $params["country"];
        $RegistrantEmailAddress = $params["email"];
        $RegistrantPhone = $params["fullphonenumber"];
        $legalform = ($params["additionalfields"]["Legal Form"] ? $params["additionalfields"]["Legal Form"] : ($RegistrantCompanyName ? "corporation" : "individual"));
        $legalnumber = ($params["additionalfields"]["Legal Number"] ? $params["additionalfields"]["Legal Number"] : "");
        $vat = ($params["additionalfields"]["VAT Number"] ? $params["additionalfields"]["VAT Number"] : "");
        $sex = ($params["additionalfields"]["Sex"] ? $params["additionalfields"]["Sex"] : "Male");
        $birthday = ($params["additionalfields"]["Birth Day"] ? $params["additionalfields"]["Birth Day"] : "");
        $birthcity = ($params["additionalfields"]["Birth City"] ? $params["additionalfields"]["Birth City"] : (string) $RegistrantCity);
        $nin = ($params["additionalfields"]["National Identification Number"] ? $params["additionalfields"]["National Identification Number"] : "");
        $cnin = ($params["additionalfields"]["Company National Identification Number"] ? $params["additionalfields"]["Company National Identification Number"] : "Male");
        $corptype = ($params["additionalfields"]["Corporation Type"] ? $params["additionalfields"]["Corporation Type"] : "individuale");
        if( $tld == "it" ) 
        {
            $owner = $soap->nicCreateIT($session, (string) $RegistrantLastName, (string) $RegistrantFirstName, (string) $sex, md5($sld), $RegistrantEmailAddress, (string) $RegistrantPhone, "", (string) $RegistrantAddress1, (string) $RegistrantCity, (string) $RegistrantStateProvince, (string) $RegistrantPostalCode, (string) $RegistrantCountry, "en", true, (string) $legalform, (string) $RegistrantCompanyName, (string) $RegistrantFirstName . " " . $RegistrantLastName, (string) $legalnumber, (string) $vat, (string) $birthday, (string) $birthcity, (string) $nin, (string) $cnin, (string) $corptype);
        }
        else
        {
            $owner = $soap->nicCreate($session, (string) $RegistrantLastName, (string) $RegistrantFirstName, md5($sld), $RegistrantEmailAddress, (string) $RegistrantPhone, "", (string) $RegistrantAddress1, (string) $RegistrantCity, (string) $RegistrantStateProvince, (string) $RegistrantPostalCode, (string) $RegistrantCountry, "en", true, (string) $legalform, (string) $RegistrantCompanyName, (string) $RegistrantFirstName . " " . $RegistrantLastName, (string) $legalnumber, (string) $vat);
        }

        $AdminFirstName = $params["adminfirstname"];
        $AdminLastName = $params["adminlastname"];
        $AdminCompanyName = $params["admincompanyname"];
        $AdminAddress1 = $params["adminaddress1"];
        $AdminAddress2 = $params["adminaddress2"];
        $AdminCity = $params["admincity"];
        $AdminStateProvince = $params["adminstate"];
        $AdminPostalCode = $params["adminpostcode"];
        $AdminCountry = $params["admincountry"];
        $AdminEmailAddress = $params["adminemail"];
        $AdminPhone = $params["adminfullphonenumber"];
        $legalform = ($params["additionalfields"]["Legal Form"] ? $params["additionalfields"]["Legal Form"] : ($AdminCompanyName ? "corporation" : "individual"));
        $admin = $soap->nicCreate($session, (string) $AdminLastName, (string) $AdminFirstName, md5($sld), $AdminEmailAddress, (string) $AdminPhone, "", (string) $AdminAddress1, (string) $AdminCity, (string) $AdminStateProvince, (string) $AdminPostalCode, (string) $AdminCountry, "en", false, (string) $legalform, (string) $AdminCompanyName, (string) $AdminFirstName . " " . $AdminLastName, "", "");
        $owo = "no";
        $owoexts = array( ".com", ".net", ".org", ".info", ".biz" );
        if( $params["idprotection"] && in_array("{." . $tld . "}", $owoexts) ) 
        {
            $owo = "yes";
        }

        $method = $legalNumber = $legalName = $afnicIdent = "";
        $birthDate = $birthCity = $birthDepartement = $birthCountry = "";
        if( $tld == "fr" ) 
        {
            if( !empty($params["additionalfields"]["SIRET Number"]) ) 
            {
                $method = "siren";
                $legalNumber = $params["additionalfields"]["SIRET Number"];
            }
            else
            {
                $method = "inpi";
                $legalNumber = $params["additionalfields"]["DUNS Number"];
            }

            $legalName = (empty($RegistrantCompanyName) ? $RegistrantFirstName . " " . $RegistrantLastName : $RegistrantCompanyName);
            $afnicIdent = $params["additionalfields"]["Trademark Number"];
            $birthDate = $params["additionalfields"]["Birthdate"];
            $birthCity = $params["additionalfields"]["Birthplace City"];
            $birthDepartement = $params["additionalfields"]["Birthplace Postcode"];
            $birthCountry = $params["additionalfields"]["Birthplace Country"];
        }

        $values = array(  );
        if( $tld == "asia" ) 
        {
            $cedcea = $params["additionalfields"]["CEDCEA"];
            $localitycity = $params["additionalfields"]["localityCity"];
            $localitysp = $params["additionalfields"]["localitysp"];
            $cclocality = $params["additionalfields"]["ccLocality"];
            $legalentitytype = $params["additionalfields"]["legalEntityType"];
            $otherletype = $params["additionalfields"]["otherLEType"];
            $identform = $params["additionalfields"]["identForm"];
            $otheridentform = $params["additionalfields"]["otherIdentForm"];
            $identno = $params["additionalfields"]["identNumber"];
            $soap->resellerDomainCreateASIA($session, (string) $domain, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, "", (string) $cedcea, (string) $owner, (string) $localitycity, (string) $localitysp, (string) $cclocality, (string) $legalentitytype, (string) $otherletype, (string) $identform, (string) $otheridentform, (string) $identno, $testmode);
        }
        else
        {
            if( $tld == "cat" ) 
            {
                $reason = $params["additionalfields"]["Reason"];
                $soap->resellerDomainCreateCAT($session, (string) $domain, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, "", (string) $reason, $testmode);
            }
            else
            {
                if( $tld == "it" ) 
                {
                    $legalRepresentantFirstName = $params["additionalfields"]["legalRepresentantFirstName"];
                    $legalRepresentantLastName = $params["additionalfields"]["legalRepresentantLastName"];
                    $legalNumber = $params["additionalfields"]["legalNumber"];
                    $vat = $params["additionalfields"]["vat"];
                    $birthDate = $params["additionalfields"]["birthDate"];
                    $birthCity = $params["additionalfields"]["birthCity"];
                    $birthDepartement = $params["additionalfields"]["birthDepartement"];
                    $birthCountry = $params["additionalfields"]["birthCountry"];
                    $nationality = $params["additionalfields"]["nationality"];
                    $soap->resellerDomainCreateIT($session, (string) $domain, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, "", (string) $legalRepresentantFirstName, (string) $legalRepresentantLastName, (string) $legalNumber, (string) $vat, (string) $birthDate, (string) $birthCity, (string) $birthDepartement, (string) $birthCountry, (string) $nationality, $testmode);
                }
                else
                {
                    $soap->resellerDomainCreate($session, (string) $domain, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, "", (string) $method, (string) $legalName, (string) $legalNumber, (string) $afnicIdent, (string) $birthDate, (string) $birthCity, (string) $birthDepartement, (string) $birthCountry, $testmode);
                }

            }

        }

        return $values;
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Register Domain", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
}

function ovh_TransferDomain($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $transfersecret = $params["transfersecret"];
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $regperiod = $params["regperiod"];
        $nameserver1 = $params["ns1"];
        $nameserver2 = $params["ns2"];
        $nameserver3 = $params["ns3"];
        $nameserver4 = $params["ns4"];
        $nameserver5 = $params["ns5"];
        $RegistrantFirstName = $params["firstname"];
        $RegistrantLastName = $params["lastname"];
        $RegistrantCompanyName = $params["companyname"];
        $RegistrantAddress1 = $params["address1"];
        $RegistrantAddress2 = $params["address2"];
        $RegistrantCity = $params["city"];
        $RegistrantStateProvince = $params["state"];
        $RegistrantPostalCode = $params["postcode"];
        $RegistrantCountry = $params["country"];
        $RegistrantEmailAddress = $params["email"];
        $RegistrantPhone = $params["fullphonenumber"];
        $legalform = ($params["additionalfields"]["Legal Form"] ? $params["additionalfields"]["Legal Form"] : ($RegistrantCompanyName ? "corporation" : "individual"));
        $legalnumber = ($params["additionalfields"]["Legal Number"] ? $params["additionalfields"]["Legal Number"] : "");
        $vat = ($params["additionalfields"]["VAT Number"] ? $params["additionalfields"]["VAT Number"] : "");
        $sex = ($params["additionalfields"]["Sex"] ? $params["additionalfields"]["Sex"] : "Male");
        $birthday = ($params["additionalfields"]["Birth Day"] ? $params["additionalfields"]["Birth Day"] : "");
        $birthcity = ($params["additionalfields"]["Birth City"] ? $params["additionalfields"]["Birth City"] : (string) $RegistrantCity);
        $nin = ($params["additionalfields"]["National Identification Number"] ? $params["additionalfields"]["National Identification Number"] : "");
        $cnin = ($params["additionalfields"]["Company National Identification Number"] ? $params["additionalfields"]["Company National Identification Number"] : "Male");
        $corptype = ($params["additionalfields"]["Corporation Type"] ? $params["additionalfields"]["Corporation Type"] : "individuale");
        if( $tld == "it" ) 
        {
            $owner = $soap->nicCreateIT($session, (string) $sld . $tld . "Owner", (string) $RegistrantFirstName, (string) $sex, md5($sld), $RegistrantEmailAddress, (string) $RegistrantPhone, "", (string) $RegistrantAddress1, (string) $RegistrantCity, (string) $RegistrantStateProvince, (string) $RegistrantPostalCode, (string) $RegistrantCountry, "en", true, (string) $legalform, (string) $RegistrantCompanyName, (string) $RegistrantFirstName . " " . $RegistrantLastName, (string) $legalnumber, (string) $vat, (string) $birthday, (string) $birthcity, (string) $nin, (string) $cnin, (string) $corptype);
        }
        else
        {
            $owner = $soap->nicCreate($session, (string) $sld . $tld . "Owner", (string) $RegistrantFirstName, md5($sld), $RegistrantEmailAddress, (string) $RegistrantPhone, "", (string) $RegistrantAddress1, (string) $RegistrantCity, (string) $RegistrantStateProvince, (string) $RegistrantPostalCode, (string) $RegistrantCountry, "en", true, (string) $legalform, (string) $RegistrantCompanyName, (string) $RegistrantFirstName . " " . $RegistrantLastName, (string) $legalnumber, (string) $vat);
        }

        $AdminFirstName = $params["adminfirstname"];
        $AdminLastName = $params["adminlastname"];
        $AdminCompanyName = $params["admincompanyname"];
        $AdminAddress1 = $params["adminaddress1"];
        $AdminAddress2 = $params["adminaddress2"];
        $AdminCity = $params["admincity"];
        $AdminStateProvince = $params["adminstate"];
        $AdminPostalCode = $params["adminpostcode"];
        $AdminCountry = $params["admincountry"];
        $AdminEmailAddress = $params["adminemail"];
        $AdminPhone = $params["adminfullphonenumber"];
        $legalform = ($params["additionalfields"]["Legal Form"] ? $params["additionalfields"]["Legal Form"] : ($AdminCompanyName ? "corporation" : "individual"));
        $admin = $soap->nicCreate($session, (string) $sld . $tld, (string) $AdminFirstName, md5($sld), $AdminEmailAddress, (string) $AdminPhone, "", (string) $AdminAddress1, (string) $AdminCity, (string) $AdminStateProvince, (string) $AdminPostalCode, (string) $AdminCountry, "en", false, (string) $legalform, (string) $AdminCompanyName, (string) $AdminFirstName . " " . $AdminLastName, "", "");
        $owo = "no";
        $owoexts = array( ".com", ".net", ".org", ".info", ".biz" );
        if( $params["idprotection"] && in_array("{." . $tld . "}", $owoexts) ) 
        {
            $owo = "yes";
        }

        $method = $legalNumber = $legalName = $afnicIdent = "";
        $birthDate = $birthCity = $birthDepartement = $birthCountry = "";
        if( $tld == "fr" ) 
        {
            if( !empty($params["additionalfields"]["SIRET Number"]) ) 
            {
                $method = "siren";
                $legalNumber = $params["additionalfields"]["SIRET Number"];
            }
            else
            {
                $method = "inpi";
                $legalNumber = $params["additionalfields"]["DUNS Number"];
            }

            $legalName = (empty($RegistrantCompanyName) ? $RegistrantFirstName . " " . $RegistrantLastName : $RegistrantCompanyName);
            $afnicIdent = $params["additionalfields"]["Trademark Number"];
            $birthDate = $params["additionalfields"]["Birthdate"];
            $birthCity = $params["additionalfields"]["Birthplace City"];
            $birthDepartement = $params["additionalfields"]["Birthplace Postcode"];
            $birthCountry = $params["additionalfields"]["Birthplace Country"];
        }

        if( $tld == "asia" ) 
        {
            $cedcea = $params["additionalfields"]["CEDCEA"];
            $localitycity = $params["additionalfields"]["localityCity"];
            $localitysp = $params["additionalfields"]["localitysp"];
            $cclocality = $params["additionalfields"]["ccLocality"];
            $legalentitytype = $params["additionalfields"]["legalEntityType"];
            $otherletype = $params["additionalfields"]["otherLEType"];
            $identform = $params["additionalfields"]["identForm"];
            $otheridentform = $params["additionalfields"]["otherIdentForm"];
            $identno = $params["additionalfields"]["identNumber"];
            $soap->resellerDomainTransferASIA($session, (string) $domain, (string) $transfersecret, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, "", (string) $cedcea, (string) $owner, (string) $localitycity, (string) $localitysp, (string) $cclocality, (string) $legalentitytype, (string) $otherletype, (string) $identform, (string) $otheridentform, (string) $identno, $testmode);
        }
        else
        {
            if( $tld == "it" ) 
            {
                $legalRepresentantFirstName = $params["additionalfields"]["legalRepresentantFirstName"];
                $legalRepresentantLastName = $params["additionalfields"]["legalRepresentantLastName"];
                $legalNumber = $params["additionalfields"]["legalNumber"];
                $vat = $params["additionalfields"]["vat"];
                $birthDate = $params["additionalfields"]["birthDate"];
                $birthCity = $params["additionalfields"]["birthCity"];
                $birthDepartement = $params["additionalfields"]["birthDepartement"];
                $birthCountry = $params["additionalfields"]["birthCountry"];
                $nationality = $params["additionalfields"]["nationality"];
                $soap->resellerDomainTransferIT($session, (string) $domain, (string) $transfersecret, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, (string) $nameserver5, (string) $legalRepresentantFirstName, (string) $legalRepresentantLastName, (string) $legalNumber, (string) $vat, (string) $birthDate, (string) $birthCity, (string) $birthDepartement, (string) $birthCountry, (string) $nationality, $testmode);
            }
            else
            {
                $soap->resellerDomainTransfer($session, (string) $domain, (string) $transfersecret, "none", "gold", "whiteLabel", (string) $owo, (string) $owner, (string) $username, (string) $admin, (string) $username, (string) $nameserver1, (string) $nameserver2, (string) $nameserver3, (string) $nameserver4, (string) $nameserver5, (string) $method, (string) $legalName, (string) $legalNumber, (string) $afnicIdent, (string) $birthDate, (string) $birthCity, (string) $birthDepartement, (string) $birthCountry, $testmode);
            }

        }

    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Transfer Domain", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
}

function ovh_RenewDomain($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $soap->resellerDomainRenew($session, (string) $domain, $testmode);
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Renew Domain", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
}

function ovh_GetContactDetails($params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $information = $soap->domainInfo($session, (string) $domain);
        $tech = $information->nictech;
        $information = $soap->nicInfo($session, (string) $tech);
        $values["Tech"]["Last Name"] = $information->name;
        $values["Tech"]["First Name"] = $information->firstname;
        $values["Tech"]["Email"] = $information->email;
        $values["Tech"]["Legal Form"] = $information->legalform;
        $values["Tech"]["Organisation"] = $information->organisation;
        $values["Tech"]["Legal Name"] = $information->legalName;
        $values["Tech"]["Legal Number"] = $information->legalNumber;
        $values["Tech"]["VAT"] = $information->vat;
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Get Contact Details", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}

function ovh_SaveContactDetails($params)
{
    ini_set("display_errors", "off");
    error_reporting(0);
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $information = $soap->domainInfo($session, (string) $domain);
        $tech = $information->nictech;
        $techname = $params["contactdetails"]["Tech"]["Last Name"];
        $techfirstname = $params["contactdetails"]["Tech"]["First Name"];
        $techemail = $params["contactdetails"]["Tech"]["Email"];
        $techlegalform = $params["contactdetails"]["Tech"]["Legal Form"];
        $techorganisation = $params["contactdetails"]["Tech"]["Organisation"];
        $techlegalName = $params["contactdetails"]["Tech"]["Legal Name"];
        $techlegalNumber = $params["contactdetails"]["Tech"]["Legal Number"];
        $techvat = $params["contactdetails"]["Tech"]["VAT"];
        $soap->nicUpdate($session, $tech, $techname, $techfirstname, $techlegalform, $techorganisation, $techlegalName, $techlegalNumber, $techvat);
        $soap->nicModifyEmail($session, $tech, $techemail);
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Save Contact Details", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}

function ovh_GetEPPCode($params)
{
    ini_set("display_errors", "off");
    error_reporting(0);
    if( !class_exists("SoapClient") ) 
    {
        return array( "error" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build." );
    }

    try
    {
        $url = "https://www.ovh.com/soapi/soapi-re-1.14.wsdl";
        $soap = new SoapClient($url, array( "trace" => 1 ));
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = ($params["TestMode"] ? true : false);
        $session = $soap->login((string) $username, (string) $password, "en", false);
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = (string) $sld . "." . $tld;
        $information = $soap->domainInfo($session, (string) $domain);
        $values["eppcode"] = $information->authinfo;
    }
    catch( Exception $e ) 
    {
        logModuleCall("ovh", "Get EPP Code", $soap->__getLastRequest(), $e . $url, NULL, $session);
        if( $e->faultstring ) 
        {
            return array( "error" => $e->faultstring );
        }

        return array( "error" => "An unhandled error occurred" );
    }
    $soap->logout($session);
    return $values;
}


