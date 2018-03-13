<?php 
namespace WHMCS;


class Domains
{
    private $id = "";
    private $data = array(  );
    private $moduleresults = array(  );

    const ACTIVE_STATUS = "Active";
    const PENDING_STATUS = "Pending";

    public function __construct()
    {
    }

    public function splitAndCleanDomainInput($domain)
    {
        $domain = trim($domain);
        if( substr($domain, -1, 1) == "/" ) 
        {
            $domain = substr($domain, 0, -1);
        }

        if( substr($domain, 0, 8) == "https://" ) 
        {
            $domain = substr($domain, 8);
        }

        if( substr($domain, 0, 7) == "http://" ) 
        {
            $domain = substr($domain, 7);
        }

        if( strpos($domain, ".") !== false ) 
        {
            $domain = $this->stripOutSubdomains($domain);
            $domainparts = explode(".", $domain, 2);
            $sld = $domainparts[0];
            $tld = (isset($domainparts[1]) ? "." . $domainparts[1] : "");
        }
        else
        {
            $sld = $domain;
            $tld = "";
        }

        $sld = $this->clean($sld);
        $tld = $this->clean($tld);
        return array( "sld" => $sld, "tld" => $tld );
    }

    protected function stripOutSubdomains($domain)
    {
        $domain = preg_replace("/^www\\./", "", $domain);
        return $domain;
    }

    public function clean($val)
    {
        global $whmcs;
        $val = trim($val);
        if( !$whmcs->get_config("AllowIDNDomains") ) 
        {
            $val = strtolower($val);
        }
        else
        {
            if( function_exists("mb_strtolower") ) 
            {
                $val = mb_strtolower($val);
            }

        }

        return $val;
    }

    public function checkDomainisValid($parts)
    {
        global $CONFIG;
        $sld = $parts["sld"];
        $tld = $parts["tld"];
        if( $sld[0] == "-" || $sld[strlen($sld) - 1] == "-" ) 
        {
            return 0;
        }

        $isIdn = $isIdnTld = $skipAllowIDNDomains = false;
        if( $CONFIG["AllowIDNDomains"] ) 
        {
            $idnConvert = new Domains\Idna();
            $idnConvert->encode($sld);
            if( $idnConvert->get_last_error() && $idnConvert->get_last_error() != "The given string does not contain encodable chars" ) 
            {
                return 0;
            }

            if( $idnConvert->get_last_error() && $idnConvert->get_last_error() == "The given string does not contain encodable chars" ) 
            {
                $skipAllowIDNDomains = true;
            }
            else
            {
                $isIdn = true;
            }

        }

        if( $isIdn === false ) 
        {
            if( preg_replace("/[^.%\$^'#~@&*(),_£?!+=:{}[]()|\\/ \\\\ ]/", "", $sld) ) 
            {
                return 0;
            }

            if( (!$CONFIG["AllowIDNDomains"] || $skipAllowIDNDomains === true) && preg_replace("/[^a-z0-9-.]/i", "", $sld . $tld) != $sld . $tld ) 
            {
                return 0;
            }

            if( preg_replace("/[^a-z0-9-.]/", "", $tld) != $tld ) 
            {
                return 0;
            }

            $validMask = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-";
            if( strspn($sld, $validMask) != strlen($sld) ) 
            {
                return 0;
            }

        }

        run_hook("DomainValidation", array( "sld" => $sld, "tld" => $tld ));
        if( $sld === false && $sld !== 0 || !$tld ) 
        {
            return 0;
        }

        $coreTLDs = array( ".com", ".net", ".org", ".info", "biz", ".mobi", ".name", ".asia", ".tel", ".in", ".mn", ".bz", ".cc", ".tv", ".us", ".me", ".co.uk", ".me.uk", ".org.uk", ".net.uk", ".ch", ".li", ".de", ".jp" );
        $DomainMinLengthRestrictions = $DomainMaxLengthRestrictions = array(  );
        require(ROOTDIR . "/configuration.php");
        foreach( $coreTLDs as $cTLD ) 
        {
            if( !array_key_exists($cTLD, $DomainMinLengthRestrictions) ) 
            {
                $DomainMinLengthRestrictions[$cTLD] = 3;
            }

            if( !array_key_exists($cTLD, $DomainMaxLengthRestrictions) ) 
            {
                $DomainMaxLengthRestrictions[$cTLD] = 63;
            }

        }
        if( array_key_exists($tld, $DomainMinLengthRestrictions) && strlen($sld) < $DomainMinLengthRestrictions[$tld] ) 
        {
            return 0;
        }

        if( array_key_exists($tld, $DomainMaxLengthRestrictions) && $DomainMaxLengthRestrictions[$tld] < strlen($sld) ) 
        {
            return 0;
        }

        return 1;
    }

    public function getDomainsDatabyID($domainid)
    {
        $where = array( "id" => (int) $domainid );
        if( defined("CLIENTAREA") ) 
        {
            if( !isset($_SESSION["uid"]) ) 
            {
                return false;
            }

            $where["userid"] = $_SESSION["uid"];
        }

        return $this->getDomainsData($where);
    }

    private function getDomainsData($where = "")
    {
        $result = select_query("tbldomains", "", $where);
        $data = mysql_fetch_array($result);
        if( $data["id"] ) 
        {
            $this->id = $data["id"];
            $this->data = $data;
            return $data;
        }

        return false;
    }

    public function isActive()
    {
        if( is_array($this->data) && $this->data["status"] == self::ACTIVE_STATUS ) 
        {
            return true;
        }

        return false;
    }

    public function isPending()
    {
        if( is_array($this->data) && $this->data["status"] == self::PENDING_STATUS ) 
        {
            return true;
        }

        return false;
    }

    public function getData($var)
    {
        return (isset($this->data[$var]) ? $this->data[$var] : "");
    }

    public function getModule()
    {
        $whmcs = \App::self();
        return $whmcs->sanitize("0-9a-z_-", $this->getData("registrar"));
    }

    public function hasFunction($function)
    {
        $mod = new Module\Registrar();
        $mod->load($this->getModule());
        return $mod->functionExists($function);
    }

    public function moduleCall($function, $additionalVars = "")
    {
        $mod = new Module\Registrar();
        $module = $this->getModule();
        if( !$module ) 
        {
            $this->moduleresults = array( "error" => "Domain not assigned to a registrar module" );
            return false;
        }

        $loaded = $mod->load($module);
        if( !$loaded ) 
        {
            $this->moduleresults = array( "error" => "Registrar module not found" );
            return false;
        }

        $mod->setDomainID($this->getData("id"));
        $results = $mod->call($function, $additionalVars);
        if( $results === Module\Registrar::FUNCTIONDOESNTEXIST ) 
        {
            $this->moduleresults = array( "error" => "Function not found" );
            return false;
        }

        $this->moduleresults = $results;
        return (is_array($results) && array_key_exists("error", $results) && $results["error"] ? false : true);
    }

    public function getModuleReturn($var = "")
    {
        if( !$var ) 
        {
            return $this->moduleresults;
        }

        return (isset($this->moduleresults[$var]) ? $this->moduleresults[$var] : "");
    }

    public function getLastError()
    {
        return $this->getModuleReturn("error");
    }

    public function getDefaultNameservers()
    {
        global $whmcs;
        $vars = array(  );
        $serverid = get_query_val("tblhosting", "server", array( "domain" => $this->getData("domain") ));
        if( $serverid ) 
        {
            $result = select_query("tblservers", "nameserver1,nameserver2,nameserver3,nameserver4,nameserver5", array( "id" => $serverid ));
            $data = mysql_fetch_array($result);
            for( $i = 1; $i <= 5; $i++ ) 
            {
                $vars["ns" . $i] = trim($data["nameserver" . $i]);
            }
        }
        else
        {
            for( $i = 1; $i <= 5; $i++ ) 
            {
                $vars["ns" . $i] = trim($whmcs->get_config("DefaultNameserver" . $i));
            }
        }

        return $vars;
    }

    public function getSLD()
    {
        $domain = $this->getData("domain");
        $domainparts = explode(".", $this->getData("domain"), 2);
        return $domainparts[0];
    }

    public function getTLD()
    {
        $domain = $this->getData("domain");
        $domainparts = explode(".", $this->getData("domain"), 2);
        return $domainparts[1];
    }

    public function buildWHOISSaveArray($data)
    {
        $arr = array( "First Name" => "firstname", "Last Name" => "lastname", "Full Name" => "fullname", "Contact Name" => "fullname", "Email" => "email", "Email Address" => "email", "Job Title" => "", "Company Name" => "companyname", "Organisation Name" => "companyname", "Address" => "address1", "Address 1" => "address1", "Street" => "address1", "Address 2" => "address2", "City" => "city", "State" => "state", "County" => "state", "Region" => "state", "Postcode" => "postcode", "ZIP Code" => "postcode", "ZIP" => "postcode", "Country" => "country", "Phone" => "phonenumberformatted", "Phone Number" => "phonenumberformatted", "Phone Country Code" => "phonecc" );
        $retarr = array(  );
        foreach( $arr as $k => $v ) 
        {
            $retarr[$k] = $data[$v];
        }
        return $retarr;
    }

    public function getManagementOptions()
    {
        $domainName = new Domains\Domain($this->getData("domain"));
        $managementOptions = array( "nameservers" => false, "contacts" => false, "privatens" => false, "locking" => false, "dnsmanagement" => false, "emailforwarding" => false, "idprotection" => false, "eppcode" => false, "release" => false, "addons" => false );
        if( $this->isActive() ) 
        {
            $managementOptions["nameservers"] = $this->hasFunction("GetNameservers");
            $managementOptions["contacts"] = $this->hasFunction("GetContactDetails");
        }
        else
        {
            if( $this->isPending() ) 
            {
                $managementOptions["nameservers"] = true;
                $managementOptions["contacts"] = true;
            }

        }

        $managementOptions["privatens"] = $this->hasFunction("RegisterNameserver");
        $managementOptions["locking"] = $domainName->getLastTLDSegment() != "uk" && $this->hasFunction("GetRegistrarLock");
        $managementOptions["release"] = $domainName->getLastTLDSegment() == "uk" && $this->hasFunction("ReleaseDomain");
        $tldPricing = \Illuminate\Database\Capsule\Manager::table("tbldomainpricing")->where("extension", "=", "." . $domainName->getTopLevel())->get();
        $tldPricing = $tldPricing[0];
        $managementOptions["eppcode"] = $tldPricing->eppcode && $this->hasFunction("GetEPPCode");
        $managementOptions["dnsmanagement"] = $this->getData("dnsmanagement") && $this->hasFunction("GetDNS");
        $managementOptions["emailforwarding"] = $this->getData("emailforwarding") && $this->hasFunction("GetEmailForwarding");
        $managementOptions["idprotection"] = ($this->getData("idprotection") ? true : false);
        $managementOptions["addons"] = $tldPricing->dnsmanagement || $tldPricing->emailforwarding || $tldPricing->idprotection;
        return $managementOptions;
    }

    public static function getRenewableDomains($userID = 0)
    {
        $whmcs = \App::self();
        if( $userID == 0 ) 
        {
            $userID = (int) Session::get("uid");
        }

        $renewals = array(  );
        $domainRenewalPriceOptions = array(  );
        $appConfig = \DI::make("config");
        $domainRenewalGracePeriods = $appConfig->DomainRenewalGracePeriods;
        $domainRenewalMinimums = $appConfig->DomainRenewalMinimums;
        $domainRenewalGracePeriods = array_merge(array( ".com" => "30", ".net" => "30", ".org" => "30", ".info" => "15", ".biz" => "30", ".mobi" => "30", ".name" => "30", ".asia" => "30", ".tel" => "30", ".in" => "15", ".mn" => "30", ".bz" => "30", ".cc" => "30", ".tv" => "30", ".eu" => "0", ".co.uk" => "97", ".org.uk" => "97", ".me.uk" => "97", ".us" => "30", ".ws" => "0", ".me" => "30", ".cn" => "30", ".nz" => "0", ".ca" => "30" ), (is_array($domainRenewalGracePeriods) ? $domainRenewalGracePeriods : array(  )));
        $domainRenewalMinimums = array_merge(array( ".co.uk" => "180", ".org.uk" => "180", ".me.uk" => "180", ".com.au" => "90", ".net.au" => "90", ".org.au" => "90" ), (is_array($domainRenewalMinimums) ? $domainRenewalMinimums : array(  )));
        $domainData = \Illuminate\Database\Capsule\Manager::table("tbldomains")->where("userid", "=", $userID)->whereIn("status", array( "Active", "Expired" ))->orderBy("expirydate", "ASC")->get(array( "id", "domain", "expirydate", "nextduedate", "status" ));
        foreach( $domainData as $singleDomain ) 
        {
            $id = $singleDomain->id;
            $domain = $singleDomain->domain;
            $expiryDate = $singleDomain->expirydate;
            $normalisedExpiryDate = $expiryDate;
            $status = $singleDomain->status;
            if( $expiryDate == "0000-00-00" ) 
            {
                $expiryDate = $singleDomain->nextduedate;
            }

            $today = new \DateTime(date("Y-m-d"));
            $expiry = new \DateTime($expiryDate);
            $todayExpiryDifference = $today->diff($expiry);
            $daysUntilExpiry = (($todayExpiryDifference->invert == 1 ? "-" : "")) . $todayExpiryDifference->days;
            $domainParts = explode(".", $domain, 2);
            $tld = "." . $domainParts[1];
            $beforeRenewLimit = $inGracePeriod = $pastGracePeriod = false;
            $earlyRenewalRestriction = 0;
            if( array_key_exists($tld, $domainRenewalMinimums) ) 
            {
                $earlyRenewalRestriction = $domainRenewalMinimums[$tld];
                if( $earlyRenewalRestriction < $daysUntilExpiry ) 
                {
                    $beforeRenewLimit = true;
                }

            }

            $renewalGracePeriod = 0;
            if( array_key_exists($tld, $domainRenewalGracePeriods) ) 
            {
                $renewalGracePeriod = $domainRenewalGracePeriods[$tld];
                if( $renewalGracePeriod < $daysUntilExpiry * -1 ) 
                {
                    $pastGracePeriod = true;
                }

            }
            else
            {
                if( $daysUntilExpiry < 0 ) 
                {
                    $pastGracePeriod = true;
                }

            }

            if( !$pastGracePeriod && $daysUntilExpiry < 0 ) 
            {
                $inGracePeriod = true;
            }

            if( !array_key_exists($tld, $domainRenewalPriceOptions) ) 
            {
                $tempPriceList = getTLDPriceList($tld, true, true);
                $renewalOptions = array(  );
                foreach( $tempPriceList as $regPeriod => $options ) 
                {
                    if( $options["renew"] ) 
                    {
                        $renewalOptions[] = array( "period" => $regPeriod, "price" => $options["renew"] );
                    }

                }
                $domainRenewalPriceOptions[$tld] = $renewalOptions;
            }
            else
            {
                $renewalOptions = $domainRenewalPriceOptions[$tld];
            }

            $next30 = $next90 = $next180 = $after180 = false;
            if( in_array($daysUntilExpiry, range(0, 180)) ) 
            {
                $next180 = true;
                if( in_array($daysUntilExpiry, range(0, 30)) ) 
                {
                    $next30 = true;
                }

                if( in_array($daysUntilExpiry, range(0, 90)) ) 
                {
                    $next90 = true;
                }

            }
            else
            {
                if( 0 <= $daysUntilExpiry ) 
                {
                    $after180 = true;
                }

            }

            $rawStatus = ClientArea::getRawStatus($status);
            if( count($renewalOptions) ) 
            {
                $renewals[] = array( "id" => $id, "domain" => $domain, "tld" => $tld, "status" => $whmcs->get_lang("clientarea" . $rawStatus), "expiryDate" => fromMySQLDate($expiryDate), "normalisedExpiryDate" => $normalisedExpiryDate, "daysUntilExpiry" => $daysUntilExpiry, "beforeRenewLimit" => $beforeRenewLimit, "beforeRenewLimitDays" => $earlyRenewalRestriction, "inGracePeriod" => $inGracePeriod, "pastGracePeriod" => $pastGracePeriod, "gracePeriodDays" => $renewalGracePeriod, "renewalOptions" => $renewalOptions, "statusClass" => View\Helper::generateCssFriendlyClassName($status), "next30" => $next30, "next90" => $next90, "next180" => $next180, "after180" => $after180 );
            }

        }
        return $renewals;
    }

    public function obtainEmailReminders()
    {
        $reminderData = array(  );
        $reminders = select_query("tbldomainreminders", "", array( "domain_id" => $this->id ), "id", "DESC");
        while( $data = mysql_fetch_assoc($reminders) ) 
        {
            $reminderData[] = $data;
        }
        return $reminderData;
    }

}


