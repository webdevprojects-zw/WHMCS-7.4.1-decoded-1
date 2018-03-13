<?php 
namespace WHMCS\Cron\Task;


class SystemConfiguration extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $accessLevel = \WHMCS\Scheduling\Task\TaskInterface::ACCESS_SYSTEM;
    protected $defaultPriority = 800;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "System Configuration Check";
    protected $defaultName = "System Configuration Check";
    protected $systemName = "SystemConfiguration";
    protected $outputs = array(  );

    public function __invoke()
    {
        $clientStatus = $productModules = $domainModules = $invoiceModules = $addonModules = array(  );
        $result = @mysql_query("SELECT DISTINCT status, COUNT(id) FROM tblclients GROUP BY status ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                if( is_array($data) && count($data) == 4 ) 
                {
                    $clientStatus[$data[0]] = $data[1];
                }

            }
        }

        $result = @mysql_query("SELECT DISTINCT(tblproducts.servertype), COUNT(tblhosting.id) FROM tblhosting" . " INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid" . " WHERE domainstatus='Active' GROUP BY tblproducts.servertype" . " ORDER BY tblproducts.servertype ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                if( is_array($data) && count($data) == 4 ) 
                {
                    $productModules[$data[0]] = $data[1];
                }

            }
        }

        $result = @mysql_query("SELECT registrar, COUNT(id) FROM tbldomains" . " WHERE status='Active' GROUP BY registrar ORDER BY registrar ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                if( is_array($data) && count($data) == 4 ) 
                {
                    $domainModules[$data[0]] = $data[1];
                }

            }
        }

        $result = @mysql_query("SELECT paymentmethod, COUNT(id) FROM tblinvoices" . " WHERE status='Paid' GROUP BY paymentmethod ORDER BY paymentmethod ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                if( is_array($data) && count($data) == 4 ) 
                {
                    $invoiceModules[$data[0]] = $data[1];
                }

            }
        }

        $result = @mysql_query("SELECT module, value FROM tbladdonmodules" . " WHERE setting = 'version' ORDER BY module ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                if( is_array($data) && count($data) == 4 ) 
                {
                    $addonModules[$data[0]] = $data[1];
                }

            }
        }

        $notificationModules = \WHMCS\Notification\Provider::pluck("active", "name");
        $domainLookupProvider = array( \WHMCS\Config\Setting::getValue("domainLookupProvider") => 1 );
        $appLinks = array(  );
        $result = @mysql_query("SELECT module_type, module_name FROM tblapplinks" . " WHERE is_enabled = 1 ORDER BY module_type ASC, module_name ASC");
        if( $result ) 
        {
            while( $data = @mysql_fetch_array($result) ) 
            {
                $moduleType = $data["module_type"];
                $moduleName = $data["module_name"];
                $entityCount = 0;
                if( $moduleType == "servers" ) 
                {
                    $entityCount = get_query_val("tblservers", "COUNT(id)", array( "type" => $moduleName, "disabled" => "0" ));
                }

                $appLinks[$moduleType . "_" . $moduleName] = $entityCount;
            }
        }

        $languages = array(  );
        $defaultLanguage = strtolower(\WHMCS\Config\Setting::getValue("Language"));
        $languages["systemDefault"] = $defaultLanguage;
        $languages["clientUsage"] = \WHMCS\Database\Capsule::table("tblclients")->groupBy("language")->orderBy("language")->pluck(\WHMCS\Database\Capsule::raw("count(language) AS cnt"), \WHMCS\Database\Capsule::raw("IF(language='', 'default', language) AS language"));
        if( !isset($languages["clientUsage"]["default"]) ) 
        {
            $languages["clientUsage"]["default"] = 0;
        }

        if( isset($languages["clientUsage"][$defaultLanguage]) ) 
        {
            $languages["clientUsage"]["default"] += $languages["clientUsage"][$defaultLanguage];
            unset($languages["clientUsage"][$defaultLanguage]);
        }

        $languages["clientUsage"][$defaultLanguage] = $languages["clientUsage"]["default"];
        unset($languages["clientUsage"]["default"]);
        ksort($languages["clientUsage"]);
        $languages["adminUsage"] = \WHMCS\Database\Capsule::table("tbladmins")->groupBy("language")->orderBy("language")->pluck(\WHMCS\Database\Capsule::raw("count(language) AS cnt"), \WHMCS\Database\Capsule::raw("IF(language='', 'default', language) AS language"));
        if( !isset($languages["adminUsage"]["default"]) ) 
        {
            $languages["adminUsage"]["default"] = 0;
        }

        if( isset($languages["adminUsage"][$defaultLanguage]) ) 
        {
            $languages["adminUsage"]["default"] += $languages["adminUsage"][$defaultLanguage];
            unset($languages["adminUsage"][$defaultLanguage]);
        }

        $languages["adminUsage"][$defaultLanguage] = $languages["adminUsage"]["default"];
        unset($languages["adminUsage"]["default"]);
        ksort($languages["adminUsage"]);
        $backupSystems = array_filter(explode(",", \WHMCS\Config\Setting::getValue("ActiveBackupSystems")));
        $twoFactorCurrentSettings = safe_unserialize(\WHMCS\Config\Setting::getValue("2fasettings"));
        $duosecurity = $totp = $yubikey = false;
        if( $twoFactorCurrentSettings ) 
        {
            $twoFactorModules = array( "duosecurity", "totp", "yubikey" );
            foreach( $twoFactorModules as $module ) 
            {
                if( array_key_exists($module, $twoFactorCurrentSettings["modules"]) && (array_key_exists("clientenabled", $twoFactorCurrentSettings["modules"][$module]) && $twoFactorCurrentSettings["modules"][$module]["clientenabled"] || array_key_exists("adminenabled", $twoFactorCurrentSettings["modules"][$module]) && $twoFactorCurrentSettings["modules"][$module]["adminenabled"]) ) 
                {
                    ${$module} = true;
                }

            }
        }

        try
        {
            $remoteAuth = new \WHMCS\Authentication\Remote\RemoteAuth();
            $authProviders = array(  );
            foreach( $remoteAuth->getEnabledProviders() as $provider ) 
            {
                $authProviders[$provider::NAME] = \WHMCS\Authentication\Remote\AccountLink::where("provider", $provider::NAME)->count();
            }
        }
        catch( \Exception $e ) 
        {
        }
        try
        {
            $dbCollationStats = $this->getDbColumnCollationStats();
        }
        catch( \Exception $e ) 
        {
            $dbCollationStats = array(  );
        }
        $systemStats = array( "clientStatus" => $clientStatus, "productModules" => $productModules, "domainModules" => $domainModules, "invoiceModules" => $invoiceModules, "addonModules" => $addonModules, "notificationModules" => $notificationModules, "domainLookupProvider" => $domainLookupProvider, "appLinks" => $appLinks, "authProviders" => $authProviders, "backups" => array( "ftp" => (bool) in_array("ftp", $backupSystems), "cpanel" => (bool) in_array("cpanel", $backupSystems), "email" => in_array("email", $backupSystems) ), "twoFactorAuth" => array( "duo" => $duosecurity, "totp" => $totp, "yubikey" => $yubikey ), "featureShowcase" => json_decode(\WHMCS\Config\Setting::getValue("WhatNewLinks"), true), "autoUpdate" => array( "count" => (int) \WHMCS\Config\Setting::getValue("AutoUpdateCount"), "success" => (int) \WHMCS\Config\Setting::getValue("AutoUpdateCountSuccess") ), "languages" => $languages, "dbCollationStats" => $dbCollationStats, "hasSslAvailable" => \App::isSSLAvailable() );
        \WHMCS\Config\Setting::setValue("SystemStatsCache", json_encode($systemStats));
        return $this;
    }

    private function getDbColumnCollationStats()
    {
        $whmcsEnv = new \WHMCS\Environment\WHMCS();
        $collationInfo = $whmcsEnv->getDbCollations();
        if( !is_array($collationInfo) || !isset($collationInfo["columns"]) || !is_array($collationInfo["columns"]) ) 
        {
            return array(  );
        }

        $collationCounts = array(  );
        foreach( $collationInfo["columns"] as $column ) 
        {
            $collationCounts[$column->collation] = count(explode(",", $column->entity_names));
        }
        arsort($collationCounts);
        $stats = array( "synced" => (bool) count($collationCounts) === 1, "collations" => array_keys($collationCounts), "stats" => $collationCounts );
        return $stats;
    }

}


