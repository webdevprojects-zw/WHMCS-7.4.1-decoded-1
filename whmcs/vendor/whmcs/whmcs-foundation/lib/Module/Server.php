<?php 
namespace WHMCS\Module;


class Server extends AbstractModule
{
    protected $type = "servers";
    protected $serviceID = "";
    protected $addonId = 0;
    protected $serviceModule = "";
    protected $addonModule = "";
    protected $modelData = NULL;

    public function setServiceId($serviceId = 0, $populateModel = true)
    {
        $this->serviceID = (int) $serviceId;
        if( $populateModel ) 
        {
            $this->modelData = \WHMCS\Service\Service::with("product", "client")->find($serviceId);
        }

        return $this;
    }

    public function setAddonId($addonId = 0)
    {
        $this->addonId = $addonId;
        if( $addonId ) 
        {
            $this->modelData = \WHMCS\Service\Addon::with("productAddon", "productAddon.moduleConfiguration", "productAddon.customFields", "service", "service.product", "service.product.customFields", "client")->find($addonId);
            $this->setServiceId($this->modelData->serviceId, false);
        }

        return $this;
    }

    public static function factoryFromModel($model)
    {
        $self = new self();
        if( $model instanceof \WHMCS\Service\Addon ) 
        {
            $self->addonId = $model->id;
            $self->modelData = $model->load("productAddon", "productAddon.moduleConfiguration", "productAddon.customFields", "service", "service.product", "service.product.customFields", "client");
            $self->serviceID = $model->serviceId;
            $self->load($self->getModuleByAddonId());
        }
        else
        {
            $self->serviceID = $model->id;
            $self->modelData = $model->load("product", "client");
            $self->load($self->getModuleByServiceID());
        }

        return $self;
    }

    public function getModuleByServiceID($serviceID = "")
    {
        if( (int) $serviceID ) 
        {
            $this->setServiceId((int) $serviceID);
        }

        if( !$serviceID ) 
        {
            $serviceID = $this->serviceID;
        }

        if( !$serviceID ) 
        {
            return "";
        }

        $this->serviceModule = $this->modelData->product->module;
        return $this->serviceModule;
    }

    public function getModuleByAddonId($addonId = 0)
    {
        if( $addonId ) 
        {
            $this->setAddonId($addonId);
        }

        if( !$addonId ) 
        {
            $addonId = $this->addonId;
        }

        if( !$addonId ) 
        {
            return "";
        }

        $this->addonModule = $this->modelData->productAddon->module;
        return $this->addonModule;
    }

    public function getServiceModule()
    {
        return $this->serviceModule;
    }

    public function getAddonModule()
    {
        return $this->addonModule;
    }

    public function loadByServiceID($serviceID)
    {
        $this->setServiceId($serviceID);
        $moduleName = $this->getModuleByServiceID();
        return $this->load($moduleName);
    }

    public function loadByAddonId($addonId)
    {
        $this->setAddonId($addonId);
        $moduleName = $this->getModuleByAddonId();
        return $this->load($moduleName);
    }

    public function buildParams()
    {
        $serviceId = (int) $this->serviceID;
        $addonId = $this->addonId;
        if( !$addonId ) 
        {
            try
            {
                $modelData = $this->modelData;
                if( !$modelData ) 
                {
                    $modelData = \WHMCS\Service\Service::with("product", "client")->findOrFail($serviceId);
                }

            }
            catch( \Exception $e ) 
            {
                return array(  );
            }
            $params = $this->buildServiceParams($modelData);
            $params = array_merge($params, $this->getServerParams($modelData->serverId));
        }
        else
        {
            try
            {
                $modelData = $this->modelData;
                if( !$modelData || !$modelData instanceof \WHMCS\Service\Addon ) 
                {
                    $modelData = \WHMCS\Service\Addon::with("productAddon", "productAddon.moduleConfiguration", "service", "client")->findOrFail($addonId);
                }

            }
            catch( \Exception $e ) 
            {
                return array(  );
            }
            $params = $this->buildAddonParams($modelData);
            $params = array_merge($params, $this->getServerParams($modelData->serverId));
            $params["service"] = $this->buildServiceParams($modelData->service);
            $params["service"] = array_merge($params, $this->getServerParams($modelData->service->serverId));
        }

        $client = new \WHMCS\Client($modelData->client);
        $clientsdetails = $client->getDetails();
        $clientsdetails["state"] = $clientsdetails["statecode"];
        if( !array_key_exists("original", $params) ) 
        {
            $clientsdetails = foreignChrReplace($clientsdetails);
            $params["clientsdetails"] = $clientsdetails;
        }

        $GLOBALS["moduleparams"] = $params;
        return $params;
    }

    protected function buildServiceParams(\WHMCS\Service\Service $serviceData)
    {
        $serviceId = $serviceData->id;
        $userId = $serviceData->clientId;
        $domain = $serviceData->domain;
        $username = $serviceData->username;
        $password = $serviceData->password;
        $pid = $serviceData->packageId;
        $serverId = $serviceData->serverId;
        $status = $serviceData->domainStatus;
        $params = array(  );
        $params["accountid"] = $serviceId;
        $params["serviceid"] = $serviceId;
        $params["addonId"] = 0;
        $params["userid"] = $userId;
        $params["domain"] = $domain;
        $params["username"] = $username;
        $params["password"] = \WHMCS\Input\Sanitize::decode(decrypt($password));
        $params["packageid"] = $pid;
        $params["pid"] = $pid;
        $params["serverid"] = $serverId;
        $params["status"] = $status;
        $params["type"] = $serviceData->product->type;
        $params["producttype"] = $serviceData->product->type;
        $params["moduletype"] = $serviceData->product->module;
        if( !$params["moduletype"] ) 
        {
            return array(  );
        }

        if( !isValidforPath($params["moduletype"]) ) 
        {
            exit( "Invalid Server Module Name" );
        }

        $counter = 1;
        while( $counter <= 24 ) 
        {
            $variable = "moduleConfigOption" . $counter;
            $params["configoption" . $counter] = $serviceData->product->$variable;
            $counter += 1;
        }
        $customFields = \WHMCS\Database\Capsule::table("tblcustomfields")->join("tblcustomfieldsvalues", "tblcustomfieldsvalues.fieldid", "=", "tblcustomfields.id")->where("tblcustomfieldsvalues.relid", "=", $serviceId)->where("tblcustomfields.relid", "=", $pid)->where("tblcustomfields.type", "=", "product")->pluck("tblcustomfieldsvalues.value", "tblcustomfields.fieldname");
        foreach( $customFields as $fieldName => $fieldValue ) 
        {
            unset($customFields[$fieldName]);
            if( strpos($fieldName, "|") ) 
            {
                $parts = explode("|", $fieldName);
                $fieldName = trim($parts[0]);
            }

            if( strpos($fieldValue, "|") ) 
            {
                $parts = explode("|", $fieldValue);
                $fieldValue = trim($parts[0]);
            }

            $customFields[$fieldName] = $fieldValue;
        }
        $params["customfields"] = $customFields;
        $configOptions = array(  );
        $configOptionsData = \WHMCS\Database\Capsule::table("tblproductconfigoptions")->join("tblhostingconfigoptions", "tblproductconfigoptions.id", "=", "tblhostingconfigoptions.configid")->join("tblproductconfigoptionssub", "tblproductconfigoptionssub.id", "=", "tblhostingconfigoptions.optionid")->join("tblproductconfiglinks", "tblproductconfiglinks.gid", "=", "tblproductconfigoptions.gid")->where("tblhostingconfigoptions.relid", "=", $serviceId)->where("tblproductconfiglinks.pid", "=", $pid)->get(array( "tblproductconfigoptions.optionname as productConfigOptionName", "tblproductconfigoptions.optiontype", "tblproductconfigoptionssub.optionname", "tblhostingconfigoptions.qty" ));
        foreach( $configOptionsData as $configOptionData ) 
        {
            $configOptionName = $configOptionData->productConfigOptionName;
            $configOptionType = (int) $configOptionData->optiontype;
            $configOptionValue = $configOptionData->optionname;
            $configOptionQuantity = $configOptionData->qty;
            if( strpos($configOptionName, "|") ) 
            {
                $configOptionName = explode("|", $configOptionName);
                $configOptionName = trim($configOptionName[0]);
            }

            if( strpos($configOptionValue, "|") ) 
            {
                $configOptionValue = explode("|", $configOptionValue);
                $configOptionValue = trim($configOptionValue[0]);
            }

            if( in_array($configOptionType, array( 3, 4 )) ) 
            {
                $configOptionValue = $configOptionQuantity;
            }

            $configOptions[$configOptionName] = $configOptionValue;
        }
        $params["configoptions"] = $configOptions;
        $params["model"] = $serviceData;
        return $params;
    }

    protected function buildAddonParams(\WHMCS\Service\Addon $addonData)
    {
        $addonId = $addonData->id;
        $serviceId = $addonData->serviceId;
        $userId = $addonData->clientId;
        $domain = $addonData->service->domain;
        $username = $addonData->service->username;
        $password = $addonData->service->password;
        $pid = $addonData->addonId;
        $serverId = $addonData->serverId;
        $status = $addonData->status;
        $params = array(  );
        $params["accountid"] = $addonId;
        $params["addonId"] = $addonId;
        $params["serviceid"] = $serviceId;
        $params["userid"] = $userId;
        $params["domain"] = $domain;
        $params["username"] = $username;
        $params["password"] = \WHMCS\Input\Sanitize::decode(decrypt($password));
        $params["packageid"] = $pid;
        $params["pid"] = $pid;
        $params["serverid"] = $serverId;
        $params["status"] = $status;
        $params["type"] = $addonData->productAddon->type;
        $params["producttype"] = $addonData->productAddon->type;
        $params["moduletype"] = $addonData->productAddon->module;
        if( !$params["moduletype"] ) 
        {
            return array(  );
        }

        if( !isValidforPath($params["moduletype"]) ) 
        {
            exit( "Invalid Server Module Name" );
        }

        foreach( $addonData->productAddon->moduleConfiguration as $moduleConfiguration ) 
        {
            $params[$moduleConfiguration->settingName] = $moduleConfiguration->value;
            $params[$moduleConfiguration->friendlyName] = $moduleConfiguration->value;
        }
        $customFields = \WHMCS\Database\Capsule::table("tblcustomfields")->join("tblcustomfieldsvalues", "tblcustomfieldsvalues.fieldid", "=", "tblcustomfields.id")->where("tblcustomfieldsvalues.relid", "=", $addonId)->where("tblcustomfields.relid", "=", $pid)->where("tblcustomfields.type", "=", "addon")->pluck("tblcustomfieldsvalues.value", "tblcustomfields.fieldname");
        foreach( $customFields as $fieldName => $fieldValue ) 
        {
            unset($customFields[$fieldName]);
            if( strpos($fieldName, "|") ) 
            {
                $parts = explode("|", $fieldName);
                $fieldName = trim($parts[0]);
            }

            if( strpos($fieldValue, "|") ) 
            {
                $parts = explode("|", $fieldValue);
                $fieldValue = trim($parts[0]);
            }

            $customFields[$fieldName] = $fieldValue;
        }
        if( array_key_exists("Username", $customFields) ) 
        {
            $params["username"] = $customFields["Username"];
            unset($customFields["Username"]);
        }

        if( array_key_exists("Password", $customFields) ) 
        {
            $params["password"] = $customFields["Password"];
            unset($customFields["Password"]);
        }

        if( array_key_exists("Domain", $customFields) ) 
        {
            $params["domain"] = $customFields["Domain"];
            unset($customFields["Domain"]);
        }

        $params["customfields"] = $customFields;
        $params["model"] = $addonData;
        return $params;
    }

    public function getServerParams($serverId, $serverData = array(  ))
    {
        if( !is_array($serverData) ) 
        {
            $serverData = array(  );
        }

        if( empty($serverData) && $serverId ) 
        {
            $serverData = get_query_vals("tblservers", "", array( "id" => (int) $serverId ));
        }

        if( !empty($serverData) ) 
        {
            $params = array( "server" => true, "serverid" => (int) $serverId, "serverip" => $serverData["ipaddress"], "serverhostname" => $serverData["hostname"], "serverusername" => \WHMCS\Input\Sanitize::decode($serverData["username"]), "serverpassword" => \WHMCS\Input\Sanitize::decode(decrypt($serverData["password"])), "serveraccesshash" => \WHMCS\Input\Sanitize::decode($serverData["accesshash"]), "serversecure" => $serverData["secure"], "serverhttpprefix" => "http" . (($serverData["secure"] ? "s" : "")) );
            $portNum = $serverData["port"];
            if( !$portNum ) 
            {
                $portNum = $this->getMetaDataValue("Default" . (($serverData["secure"] ? "" : "Non")) . "SSLPort");
            }

            $params["serverport"] = $portNum;
        }
        else
        {
            $params = array( "server" => false, "serverip" => "", "serverhostname" => "", "serverusername" => "", "serverpassword" => "", "serveraccesshash" => "", "serversecure" => "", "serverhttpprefix" => "", "serverport" => "" );
        }

        return $params;
    }

    public function call($function, $params = array(  ))
    {
        $serviceID = (int) $this->serviceID;
        $addonId = (int) $this->addonId;
        if( $serviceID || $addonId ) 
        {
            $builtParams = $this->buildParams();
        }
        else
        {
            $builtParams = array(  );
        }

        if( !is_array($params) ) 
        {
            $params = array(  );
        }

        switch( $function ) 
        {
            case "CreateAccount":
                $action = "create";
                break;
            case "SuspendAccount":
                $action = "suspend";
                break;
            case "UnsuspendAccount":
                $action = "unsuspend";
                break;
            case "TerminateAccount":
                $action = "terminate";
                break;
            case "ChangePassword":
                $action = "changepw";
                break;
            case "ChangePackage":
                $action = "upgrade";
                break;
            default:
                $action = $function;
                break;
        }
        $params["action"] = $action;
        $builtParams = array_merge($builtParams, $params);
        return parent::call($function, $builtParams);
    }

    public function getListWithDisplayNames()
    {
        $serverList = array(  );
        foreach( $this->getList() as $moduleName ) 
        {
            $this->load($moduleName);
            if( $this->isMetaDataValueSet("NoEditModuleSettings") && $this->getMetaDataValue("NoEditModuleSettings") ) 
            {
                continue;
            }

            $serverList[$moduleName] = $this->getDisplayName();
        }
        return $serverList;
    }

    protected function getSingleSignOnUrl($serverId, $admin)
    {
        $functionToCall = ($admin ? "AdminSingleSignOn" : "ServiceSingleSignOn");
        $data = get_query_vals("tblservers", "", array( "id" => $serverId ));
        if( !$data["id"] ) 
        {
            throw new \WHMCS\Exception\Module\SingleSignOnError("Server ID not found.");
        }

        if( $this->load($data["type"]) ) 
        {
            if( !$this->functionExists($functionToCall) ) 
            {
                throw new \WHMCS\Exception\Module\SingleSignOnError("Invalid Server ID for Auto Login.");
            }

            $params = $this->getServerParams($data["id"], $data);
            $results = $this->call($functionToCall, $params);
            if( is_array($results) && isset($results["success"]) && $results["success"] == true ) 
            {
                return $results["redirectTo"];
            }

            if( is_array($results) && isset($results["errorMsg"]) ) 
            {
                throw new \WHMCS\Exception\Module\SingleSignOnError($results["errorMsg"]);
            }

            throw new \WHMCS\Exception\Module\SingleSignOnError("Unable to auto-login.");
        }

        throw new \WHMCS\Exception\Module\SingleSignOnError("Invalid Server Module.");
    }

    public function getSingleSignOnUrlForService()
    {
        $serverId = $this->modelData->serverId;
        return $this->getSingleSignOnURL($serverId, false);
    }

    public function getSingleSignOnUrlForAdmin($serverId)
    {
        return $this->getSingleSignOnURL($serverId, true);
    }

    protected function isClientFitForApplicationLinks($client, array &$errors = NULL)
    {
        $errorList = array(  );
        if( $client->service->username == "" ) 
        {
            $errorList[] = "Username cannot be empty";
        }

        if( !is_null($errors) ) 
        {
            $errors = $errorList;
        }

        return count($errorList) == 0;
    }

    public function enableApplicationLinks($performingSync = false)
    {
        if( !$this->isApplicationLinkSupported() ) 
        {
            return false;
        }

        $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array( "module_type" => $this->getType(), "module_name" => $this->getLoadedModule() ));
        if( !$performingSync ) 
        {
            $appLink->log()->delete();
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Attempting to Enable Application Links for " . $this->getLoadedModule());
        }

        $supportedServerList = $this->determineServersWithApplicationLinksSupport($appLink->id, $performingSync);
        if( count($supportedServerList) == 0 ) 
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::NOTICE, "No " . $this->getLoadedModule() . " Servers found with support for Application Linking");
            return false;
        }

        $appLinksToProvision = array(  );
        $permittedClientAreaScopes = array(  );
        if( $this->functionExists("GetSupportedApplicationLinks") ) 
        {
            $appLinksToProvision = $this->call("GetSupportedApplicationLinks");
            foreach( $appLink->links()->get() as $link ) 
            {
                if( array_key_exists($link->scope, $appLinksToProvision) ) 
                {
                    if( $link->isEnabled ) 
                    {
                        $appLinksToProvision[$link->scope]["label"] = $link->display_label;
                        $appLinksToProvision[$link->scope]["order"] = $link->order;
                    }
                    else
                    {
                        unset($appLinksToProvision[$link->scope]);
                    }

                }

            }
            $permittedClientAreaScopeNames = implode(" ", array_keys($appLinksToProvision));
        }
        else
        {
            $validScopes = \WHMCS\ApplicationLink\Scope::where("scope", "LIKE", "clientarea:%")->pluck("scope")->all();
            $permittedClientAreaScopeNames = implode(" ", $validScopes);
        }

        if( strpos($permittedClientAreaScopeNames, "clientarea:sso") === false ) 
        {
            $permittedClientAreaScopeNames .= " clientarea:sso";
        }

        $server = \DI::make("oauth2_sso");
        $storage = $server->getStorage("client_credentials");
        $clientCredentialsToProvision = array(  );
        $hostingServices = \WHMCS\Database\Capsule::table("tblhosting")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->join("tblclients", "tblclients.id", "=", "tblhosting.userid")->where("tblproducts.servertype", "=", $this->getLoadedModule())->whereIn("tblhosting.domainstatus", array( "Active", "Suspended" ))->whereIn("tblhosting.server", $supportedServerList)->select("tblhosting.id", "tblclients.uuid", "tblhosting.server", "tblhosting.domain");
        foreach( $hostingServices->get() as $hostingService ) 
        {
            $clientIdentifier = \WHMCS\ApplicationLink\Client::generateClientId();
            $secret = \WHMCS\ApplicationLink\Client::generateSecret();
            $storage->setClientDetails($clientIdentifier, $secret, "", "single_sign_on", $permittedClientAreaScopeNames, $hostingService->uuid, $hostingService->id);
            $clientCredential = \WHMCS\ApplicationLink\Client::where("identifier", "=", $clientIdentifier)->first();
            $errors = array(  );
            if( $this->isClientFitForApplicationLinks($clientCredential, $errors) ) 
            {
                $clientCredentialsToProvision[$hostingService->server][] = $clientCredential;
            }
            else
            {
                $errorMsg = $hostingService->domain . ": " . strtolower(implode(", ", $errors)) . ".";
                $this->addToAppLinkLog($appLink->id, \Monolog\Logger::WARNING, $errorMsg);
            }

        }
        $systemUrl = \App::getSystemURL();
        foreach( $clientCredentialsToProvision as $serverId => $clientCredentialCollection ) 
        {
            $params = $this->getServerParams($serverId) + array( "systemUrl" => $systemUrl, "tokenEndpoint" => $systemUrl . "oauth/token.php", "clientCredentialCollection" => $clientCredentialCollection, "appLinks" => $appLinksToProvision );
            $errors = $this->call("CreateApplicationLink", $params);
            foreach( $errors as $errorMsg ) 
            {
                $this->addToAppLinkLog($appLink->id, \Monolog\Logger::WARNING, $errorMsg);
            }
        }
        $appLink->isEnabled = true;
        $appLink->save();
        $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Process Completed Successfully!");
        return true;
    }

    public function disableApplicationLinks($performingSync = false)
    {
        if( !$this->isApplicationLinkSupported() ) 
        {
            return false;
        }

        $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array( "module_type" => $this->getType(), "module_name" => $this->getLoadedModule() ));
        $appLink->log()->delete();
        if( $performingSync ) 
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Attempting to Sync Application Links Configuration Changes for " . $this->getLoadedModule());
        }
        else
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Attempting to Disable Application Links for " . $this->getLoadedModule());
        }

        $supportedServerList = $this->determineServersWithApplicationLinksSupport($appLink->id);
        if( count($supportedServerList) == 0 ) 
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::NOTICE, "No " . $this->getLoadedModule() . " Servers found with support for Application Linking");
            return false;
        }

        $appLinks = array(  );
        if( $this->functionExists("GetSupportedApplicationLinks") ) 
        {
            $appLinks = $this->call("GetSupportedApplicationLinks");
        }

        $clientCredentialsToDeprovision = array(  );
        foreach( \WHMCS\Database\Capsule::table("tblhosting")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("tblproducts.servertype", "=", $this->getLoadedModule())->whereIn("tblhosting.domainstatus", array( "Active", "Suspended" ))->whereIn("tblhosting.server", $supportedServerList)->select("tblhosting.id", "tblhosting.server", "tblhosting.domain")->get() as $hostingService ) 
        {
            $clientCredential = \WHMCS\ApplicationLink\Client::where("service_id", "=", $hostingService->id)->first();
            if( !is_null($clientCredential) ) 
            {
                $errors = array(  );
                if( $this->isClientFitForApplicationLinks($clientCredential, $errors) ) 
                {
                    $clientCredentialsToDeprovision[$hostingService->server][] = $clientCredential;
                    $clientCredential->delete();
                }
                else
                {
                    $errorMsg = $hostingService->domain . ": " . strtolower(implode(", ", $errors)) . ".";
                    $this->addToAppLinkLog($appLink->id, \Monolog\Logger::WARNING, $errorMsg);
                }

            }

        }
        foreach( $clientCredentialsToDeprovision as $serverId => $clientCredentialCollection ) 
        {
            $params = $this->getServerParams($serverId) + array( "clientCredentialCollection" => $clientCredentialCollection, "appLinks" => $appLinks );
            $errors = $this->call("DeleteApplicationLink", $params);
            foreach( $errors as $errorMsg ) 
            {
                $this->addToAppLinkLog($appLink->id, \Monolog\Logger::WARNING, $errorMsg);
            }
        }
        $appLink->isEnabled = false;
        $appLink->save();
        if( !$performingSync ) 
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Process Completed Successfully!");
        }

        return true;
    }

    public function cleanupOldApplicationLinks()
    {
        if( !$this->isApplicationLinkSupported() ) 
        {
            return false;
        }

        $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array( "module_type" => $this->getType(), "module_name" => $this->getLoadedModule() ));
        $appLink->log()->delete();
        $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Attempting to Remove Orphaned Application Links for " . $this->getLoadedModule());
        $supportedServerList = $this->determineServersWithApplicationLinksSupport($appLink->id);
        if( count($supportedServerList) == 0 ) 
        {
            $this->addToAppLinkLog($appLink->id, \Monolog\Logger::NOTICE, "No " . $this->getLoadedModule() . " Servers found with support for Application Linking");
            return false;
        }

        $appLinks = array(  );
        if( $this->functionExists("GetRemovedApplicationLinks") ) 
        {
            $appLinks = $this->call("GetRemovedApplicationLinks");
        }

        $clientCredentialsToDeprovision = array(  );
        foreach( \WHMCS\Database\Capsule::table("tblhosting")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("tblproducts.servertype", "=", $this->getLoadedModule())->whereIn("tblhosting.domainstatus", array( "Active", "Suspended" ))->whereIn("tblhosting.server", $supportedServerList)->select("tblhosting.id", "tblhosting.server", "tblhosting.domain")->get() as $hostingService ) 
        {
            $clientCredential = \WHMCS\ApplicationLink\Client::where("service_id", "=", $hostingService->id)->first();
            if( !is_null($clientCredential) ) 
            {
                $errors = array(  );
                if( $this->isClientFitForApplicationLinks($clientCredential, $errors) ) 
                {
                    $clientCredentialsToDeprovision[$hostingService->server][] = $clientCredential;
                    $clientCredential->delete();
                }
                else
                {
                    $errorMsg = $hostingService->domain . ": " . strtolower(implode(", ", $errors)) . ".";
                    $this->addToAppLinkLog($appLink->id, \Monolog\Logger::WARNING, $errorMsg);
                }

            }

        }
        foreach( $clientCredentialsToDeprovision as $serverId => $clientCredentialCollection ) 
        {
            $params = $this->getServerParams($serverId) + array( "clientCredentialCollection" => $clientCredentialCollection, "appLinks" => $appLinks );
            $this->call("DeleteApplicationLink", $params);
        }
        $this->addToAppLinkLog($appLink->id, \Monolog\Logger::DEBUG, "Process Completed Successfully!");
        return true;
    }

    protected function addToAppLinkLog($appLinkId, $level, $message)
    {
        $logEntry = new \WHMCS\ApplicationLink\Log();
        $logEntry->applink_id = $appLinkId;
        $logEntry->message = $message;
        $logEntry->level = $level;
        $logEntry->save();
    }

    protected function determineServersWithApplicationLinksSupport($appLinkId, $skipLogging = false)
    {
        $supportedServerList = array(  );
        foreach( \WHMCS\Database\Capsule::table("tblservers")->where("tblservers.type", "=", $this->getLoadedModule())->whereDisabled("0")->pluck("tblservers.id") as $serverId ) 
        {
            if( $this->functionExists("IsApplicationLinkingSupportedByServer") ) 
            {
                $response = $this->call("IsApplicationLinkingSupportedByServer", $this->getServerParams($serverId));
                if( isset($response["isSupported"]) && $response["isSupported"] ) 
                {
                    $isSupported = true;
                }
                else
                {
                    $isSupported = false;
                    $errorMsg = (isset($response["errorMsg"]) ? $response["errorMsg"] : "An unknown error occurred");
                }

            }
            else
            {
                $isSupported = true;
            }

            if( $isSupported ) 
            {
                $supportedServerList[] = $serverId;
            }
            else
            {
                if( !$skipLogging ) 
                {
                    $message = "Server ID " . $serverId . " ";
                    if( $errorMsg ) 
                    {
                        $level = \Monolog\Logger::WARNING;
                        $message .= "Connection Error: " . $errorMsg;
                    }
                    else
                    {
                        $level = \Monolog\Logger::INFO;
                        $message .= "does not support application links functionality";
                    }

                    $logEntry = new \WHMCS\ApplicationLink\Log();
                    $logEntry->applink_id = $appLinkId;
                    $logEntry->message = $message;
                    $logEntry->level = $level;
                    $logEntry->save();
                }

            }

        }
        return $supportedServerList;
    }

    public function syncApplicationLinksConfigChange()
    {
        if( $this->disableApplicationLinks(true) ) 
        {
            return $this->enableApplicationLinks(true);
        }

        return false;
    }

    public function doSingleApplicationLinkCall($action)
    {
        if( !$this->isApplicationLinkSupported() ) 
        {
            throw new \WHMCS\Exception("Application linking not supported by module");
        }

        if( !$this->isApplicationLinkingEnabled() ) 
        {
            throw new \WHMCS\Exception("Application linking not enabled for module");
        }

        if( !in_array($action, array( "Create", "Update", "Delete" )) ) 
        {
            throw new \WHMCS\Exception("Invalid action requested");
        }

        $serviceId = $this->serviceID;
        if( !$serviceId ) 
        {
            throw new \WHMCS\Exception("Service ID is required");
        }

        $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array( "module_type" => $this->getType(), "module_name" => $this->getLoadedModule() ));
        $params = $this->buildParams();
        $serverId = $params["serverid"];
        if( $this->functionExists("IsApplicationLinkingSupportedByServer") ) 
        {
            $isSupported = $this->call("IsApplicationLinkingSupportedByServer", $this->getServerParams($serverId));
        }
        else
        {
            $isSupported = true;
        }

        if( !$isSupported ) 
        {
            throw new \WHMCS\Exception("Application linking not supported by server this service is assigned to");
        }

        $appLinksToProvision = array(  );
        $permittedClientAreaScopes = array(  );
        if( $this->functionExists("GetSupportedApplicationLinks") ) 
        {
            $appLinksToProvision = $this->call("GetSupportedApplicationLinks");
            foreach( $appLink->links()->get() as $link ) 
            {
                if( $link->isEnabled ) 
                {
                    $appLinksToProvision[$link->scope]["label"] = $link->display_label;
                    $appLinksToProvision[$link->scope]["order"] = $link->order;
                }
                else
                {
                    unset($appLinksToProvision[$link->scope]);
                }

            }
            $permittedClientAreaScopeNames = implode(" ", array_keys($appLinksToProvision));
        }
        else
        {
            $validScopes = \WHMCS\ApplicationLink\Scope::where("scope", "LIKE", "clientarea:%")->pluck("scope")->all();
            $permittedClientAreaScopeNames = implode(" ", $validScopes);
        }

        if( strpos($permittedClientAreaScopeNames, "clientarea:sso") === false ) 
        {
            $permittedClientAreaScopeNames .= " clientarea:sso";
        }

        $clientCredentialCollection = array(  );
        if( $action == "Create" ) 
        {
            $server = \DI::make("oauth2_sso");
            $storage = $server->getStorage("client_credentials");
            $clientIdentifier = \WHMCS\ApplicationLink\Client::generateClientId();
            $secret = \WHMCS\ApplicationLink\Client::generateSecret();
            $storage->setClientDetails($clientIdentifier, $secret, "", "single_sign_on", $permittedClientAreaScopeNames, get_query_val("tblclients", "uuid", array( "id" => $params["userid"] )), $serviceId);
            $clientCredentialCollection[] = \WHMCS\ApplicationLink\Client::where("identifier", "=", $clientIdentifier)->first();
        }
        else
        {
            if( $action == "Update" ) 
            {
                $clientCredential = \WHMCS\ApplicationLink\Client::where("service_id", "=", $serviceId)->first();
                if( is_null($clientCredential) ) 
                {
                    throw new \WHMCS\Exception("Existing application link credential not found");
                }

                $clientCredential->secret = base64_encode(\phpseclib\Crypt\Random::string(64));
                $clientCredential->save();
                $clientCredentialCollection[] = $clientCredential;
            }
            else
            {
                if( $action == "Delete" ) 
                {
                    $clientCredential = \WHMCS\ApplicationLink\Client::where("service_id", "=", $serviceId)->first();
                    if( is_null($clientCredential) ) 
                    {
                        throw new \WHMCS\Exception("Existing application link credential not found");
                    }

                    $clientCredentialCollection[] = $clientCredential;
                    $clientCredential->delete();
                }

            }

        }

        $systemUrl = \App::getSystemURL();
        $params = $this->getServerParams($serverId) + array( "systemUrl" => $systemUrl, "tokenEndpoint" => $systemUrl . "oauth/token.php", "clientCredentialCollection" => $clientCredentialCollection, "appLinks" => $appLinksToProvision );
        if( !$this->functionExists($action . "ApplicationLink") ) 
        {
            throw new \WHMCS\Exception("Module does not supported requested Application Link Action: \"" . $action . "\"");
        }

        $errors = $this->call($action . "ApplicationLink", $params);
        if( is_array($errors) && 0 < count($errors) ) 
        {
            return $errors;
        }

    }

    public function getServerListForModule()
    {
        $servers = array(  );
        $disabledServers = array(  );
        $serverCollection = \WHMCS\Database\Capsule::table("tblservers")->where("type", "=", ($this->addonModule ?: $this->serviceModule))->orderBy("name")->get();
        if( $serverCollection ) 
        {
            $hostingCounts = \WHMCS\Database\Capsule::table("tblhosting")->whereIn("domainstatus", array( "Active", "Suspended" ))->groupBy("server")->pluck(\WHMCS\Database\Capsule::raw("count(id)"), "server");
            $hostingAddonsCounts = \WHMCS\Database\Capsule::table("tblhostingaddons")->whereIn("status", array( "Active", "Suspended" ))->groupBy("server")->pluck(\WHMCS\Database\Capsule::raw("count(id)"), "server");
            foreach( $serverCollection as $server ) 
            {
                $serverId = $server->id;
                $serverName = $server->name;
                $serverMaxAccounts = $server->maxaccounts;
                $disabled = $server->disabled;
                if( $disabled ) 
                {
                    $serverName .= " (" . \AdminLang::trans("emailtpls.disabled") . ")";
                }

                $serverNumberAccounts = $hostingCounts[$serverId] + $hostingAddonsCounts[$serverId];
                $label = $serverName . " (" . $serverNumberAccounts . "/" . $serverMaxAccounts . " " . \AdminLang::trans("fields.accounts") . ")";
                if( $disabled ) 
                {
                    $disabledServers[$serverId] = $label;
                }
                else
                {
                    $servers[$serverId] = $label;
                }

            }
        }

        return array_replace($servers, $disabledServers);
    }

}


