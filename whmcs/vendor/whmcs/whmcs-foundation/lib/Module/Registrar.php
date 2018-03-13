<?php 
namespace WHMCS\Module;


class Registrar extends AbstractModule
{
    protected $type = "registrars";
    protected $domainID = "";

    public function __construct()
    {
        if( !function_exists("injectDomainObjectIfNecessary") ) 
        {
            include_once(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "registrarfunctions.php");
        }

    }

    public function getDisplayName()
    {
        $DisplayName = $this->getMetaDataValue("DisplayName");
        if( !$DisplayName ) 
        {
            $configData = $this->call("getConfigArray");
            if( isset($configData["FriendlyName"]["Value"]) ) 
            {
                $DisplayName = $configData["FriendlyName"]["Value"];
            }
            else
            {
                $DisplayName = ucfirst($this->getLoadedModule());
            }

        }

        return \WHMCS\Input\Sanitize::makeSafeForOutput($DisplayName);
    }

    public function getSettings()
    {
        $settings = array(  );
        $dbSettings = \Illuminate\Database\Capsule\Manager::table("tblregistrars")->select("setting", "value")->where("registrar", $this->getLoadedModule())->get();
        foreach( $dbSettings as $dbSetting ) 
        {
            $settings[$dbSetting->setting] = decrypt($dbSetting->value);
        }
        return $settings;
    }

    public function setDomainID($domainID)
    {
        $this->domainID = $domainID;
    }

    protected function getDomainID()
    {
        return (int) $this->domainID;
    }

    protected function buildParams()
    {
        $data = get_query_vals("tbldomains", "id,type,domain,registrationperiod,registrar", array( "id" => $this->getDomainID() ));
        $domainID = $data["id"];
        $type = $data["type"];
        $domainname = $data["domain"];
        $regperiod = $data["registrationperiod"];
        $registrar = $data["registrar"];
        $params = $this->getSettings();
        $domainObj = new \WHMCS\Domains\Domain($domainname);
        $params["domainObj"] = $domainObj;
        $params["domainid"] = $domainID;
        $params["domainname"] = $domainname;
        $params["sld"] = $domainObj->getSLD();
        $params["tld"] = $domainObj->getTLD();
        $params["regtype"] = $type;
        $params["regperiod"] = $regperiod;
        $params["registrar"] = $registrar;
        $additflds = new \WHMCS\Domains\AdditionalFields();
        $params["additionalfields"] = $additflds->getFieldValuesFromDatabase($domainID);
        return $params;
    }

    public function call($function, $additionalParams = "")
    {
        $noDomainIdRequirement = array( "getConfigArray", "CheckAvailability", "GetDomainSuggestions", "DomainSuggestionOptions" );
        if( !in_array($function, $noDomainIdRequirement) && !$this->getDomainID() ) 
        {
            return array( "error" => "Domain ID is required" );
        }

        $params = $this->buildParams();
        if( is_array($additionalParams) ) 
        {
            $params = array_merge($params, $additionalParams);
        }

        $originalDetails = $params;
        if( !array_key_exists("original", $params) ) 
        {
            $params = foreignChrReplace($params);
            $params["original"] = $originalDetails;
        }

        return parent::call($function, $params);
    }

    public function isActivated()
    {
        return (bool) RegistrarSetting::registrar($this->getLoadedModule())->first();
    }

    public function activate(array $parameters = array(  ))
    {
        $this->deactivate();
        $registrarSetting = new RegistrarSetting();
        $registrarSetting->registrar = $this->getLoadedModule();
        $registrarSetting->setting = "Username";
        $registrarSetting->value = "";
        $registrarSetting->save();
        $moduleSettings = $this->call("getConfigArray");
        $settingsToSave = array( "Username" => "" );
        foreach( $moduleSettings as $key => $values ) 
        {
            if( $values["Type"] == "yesno" && !empty($values["Default"]) && $values["Default"] !== "off" && $values["Default"] !== "disabled" ) 
            {
                $settingsToSave[$key] = $values["Default"];
            }

        }
        $logChanges = false;
        if( 0 < count($parameters) ) 
        {
            foreach( $parameters as $key => $value ) 
            {
                if( array_key_exists($key, $moduleSettings) ) 
                {
                    $settingsToSave[$key] = $value;
                    $logChanges = true;
                }

            }
        }

        logAdminActivity("Registrar Activated: '" . $this->getDisplayName() . "'");
        $this->saveSettings($settingsToSave, $logChanges);
        return $this;
    }

    public function deactivate()
    {
        RegistrarSetting::registrar($this->getLoadedModule())->delete();
        return $this;
    }

    public function saveSettings(array $newSettings = array(  ), $logChanges = true)
    {
        $moduleName = $this->getLoadedModule();
        $moduleSettings = $this->call("getConfigArray");
        $previousSettings = $this->getSettings();
        $settingsToSave = array(  );
        $changes = array(  );
        foreach( $moduleSettings as $key => $values ) 
        {
            if( $values["Type"] == "System" ) 
            {
                continue;
            }

            if( isset($newSettings[$key]) ) 
            {
                $settingsToSave[$key] = $newSettings[$key];
            }
            else
            {
                if( $values["Type"] == "yesno" ) 
                {
                    $settingsToSave[$key] = "";
                }
                else
                {
                    if( isset($values["Default"]) ) 
                    {
                        $settingsToSave[$key] = $values["Default"];
                    }

                }

            }

            if( $values["Type"] == "password" && isset($newSettings[$key]) && isset($previousSettings[$key]) ) 
            {
                $updatedPassword = interpretMaskedPasswordChangeForStorage($newSettings[$key], $previousSettings[$key]);
                if( $updatedPassword === false ) 
                {
                    $settingsToSave[$key] = $previousSettings[$key];
                }
                else
                {
                    $changes[] = "'" . $key . "' value modified";
                }

            }

            if( $values["Type"] == "yesno" ) 
            {
                if( !empty($settingsToSave[$key]) && $settingsToSave[$key] !== "off" && $settingsToSave[$key] !== "disabled" ) 
                {
                    $settingsToSave[$key] = "on";
                }
                else
                {
                    $settingsToSave[$key] = "";
                }

                if( empty($previousSettings[$key]) ) 
                {
                    $previousSettings[$key] = "";
                }

                if( $previousSettings[$key] != $settingsToSave[$key] ) 
                {
                    $newSetting = ($settingsToSave[$key] ?: "off");
                    $oldSetting = ($previousSettings[$key] ?: "off");
                    $changes[] = "'" . $key . "' changed from '" . $oldSetting . "' to '" . $newSetting . "'";
                }

            }
            else
            {
                if( empty($settingsToSave[$key]) ) 
                {
                    $settingsToSave[$key] = "";
                }

                if( empty($previousSettings[$key]) ) 
                {
                    $previousSettings[$key] = "";
                }

                if( $values["Type"] != "password" ) 
                {
                    if( !$previousSettings[$key] && $settingsToSave[$key] ) 
                    {
                        $changes[] = "'" . $key . "' set to '" . $settingsToSave[$key] . "'";
                    }
                    else
                    {
                        if( $previousSettings[$key] != $settingsToSave[$key] ) 
                        {
                            $changes[] = "'" . $key . "' changed from '" . $previousSettings[$key] . "' to '" . $settingsToSave[$key] . "'";
                        }

                    }

                }

            }

        }
        foreach( $settingsToSave as $setting => $value ) 
        {
            $model = RegistrarSetting::registrar($moduleName)->setting($setting)->first();
            if( $model ) 
            {
                $model->value = $value;
            }
            else
            {
                $model = new RegistrarSetting();
                $model->registrar = $moduleName;
                $model->setting = $setting;
                $model->value = \WHMCS\Input\Sanitize::decode(trim($value));
            }

            $model->save();
        }
        if( $changes && $logChanges ) 
        {
            logAdminActivity("Domain Registrar Modified: '" . $this->getDisplayName() . "' - " . implode(". ", $changes) . ".");
        }

        return $this;
    }

    public function getConfiguration()
    {
        return $this->call("getConfigArray");
    }

    public function updateConfiguration(array $parameters = array(  ))
    {
        if( !$this->isActivated() ) 
        {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }

        $moduleSettings = $this->call("getConfigArray");
        $settingsToSave = array(  );
        $logChanges = false;
        if( 0 < count($parameters) ) 
        {
            foreach( $parameters as $key => $value ) 
            {
                if( array_key_exists($key, $moduleSettings) ) 
                {
                    $settingsToSave[$key] = $value;
                    $logChanges = true;
                }

            }
        }

        if( 0 < count($settingsToSave) ) 
        {
            $this->saveSettings($settingsToSave, $logChanges);
        }

    }

}


