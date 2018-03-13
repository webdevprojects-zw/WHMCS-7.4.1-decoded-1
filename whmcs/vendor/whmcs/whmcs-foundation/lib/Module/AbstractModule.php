<?php 
namespace WHMCS\Module;


abstract class AbstractModule
{
    protected $type = "";
    protected $loadedmodule = "";
    protected $metaData = array(  );
    protected $moduleParams = array(  );
    protected $usesDirectories = true;

    const FUNCTIONDOESNTEXIST = "!Function not found in module!";

    public function getType()
    {
        return $this->type;
    }

    protected function setLoadedModule($module)
    {
        $this->loadedmodule = $module;
    }

    public function getLoadedModule()
    {
        return $this->loadedmodule;
    }

    public function getList($type = "")
    {
        if( $type ) 
        {
            $this->setType($type);
        }

        $base_dir = $this->getBaseModuleDir();
        if( is_dir($base_dir) ) 
        {
            $modules = array(  );
            $dh = opendir($base_dir);
            while( false !== ($module = readdir($dh)) ) 
            {
                if( !$this->usesDirectories ) 
                {
                    $module = str_replace(".php", "", $module);
                }

                if( is_file($this->getModulePath($module)) ) 
                {
                    $modules[] = $module;
                }

            }
            sort($modules);
            return $modules;
        }

        return false;
    }

    protected function getBaseModulesDir()
    {
        return ROOTDIR . DIRECTORY_SEPARATOR . "modules";
    }

    public function getBaseModuleDir()
    {
        return $this->getBaseModulesDir() . DIRECTORY_SEPARATOR . $this->getType();
    }

    public function getModulePath($module)
    {
        $base_dir = $this->getBaseModuleDir();
        if( $this->usesDirectories ) 
        {
            return $base_dir . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $module . ".php";
        }

        return $base_dir . DIRECTORY_SEPARATOR . $module . ".php";
    }

    public function load($module)
    {
        $whmcs = \App::self();
        $licensing = \DI::make("license");
        $module = $whmcs->sanitize("0-9a-z_-", $module);
        $modpath = $this->getModulePath($module);
        \Log::debug("Attempting to load module", array( "type" => $this->getType(), "module" => $module, "path" => $modpath ));
        if( file_exists($modpath) ) 
        {
            include_once($modpath);
            $this->setLoadedModule($module);
            $this->setMetaData($this->getMetaData());
            return true;
        }

        return false;
    }

    public function call($function, $params = array(  ))
    {
        $whmcs = \App::self();
        $licensing = \DI::make("license");
        if( $this->functionExists($function) ) 
        {
            $params = $this->prepareParams($params);
            $params = array_merge($this->getParams(), $params);
            return call_user_func($this->getLoadedModule() . "_" . $function, $params);
        }

        return self::FUNCTIONDOESNTEXIST;
    }

    public function functionExists($name)
    {
        return function_exists($this->getLoadedModule() . "_" . $name);
    }

    protected function getMetaData()
    {
        $moduleName = $this->getLoadedModule();
        if( $this->functionExists("MetaData") ) 
        {
            return $this->call("MetaData");
        }

    }

    protected function setMetaData($metaData)
    {
        if( is_array($metaData) ) 
        {
            $this->metaData = $metaData;
            return true;
        }

        $this->metaData = array(  );
        return false;
    }

    public function getMetaDataValue($keyName)
    {
        return (array_key_exists($keyName, $this->metaData) ? $this->metaData[$keyName] : "");
    }

    public function isMetaDataValueSet($keyName)
    {
        return array_key_exists($keyName, $this->metaData);
    }

    public function getDisplayName()
    {
        $DisplayName = $this->getMetaDataValue("DisplayName");
        if( !$DisplayName ) 
        {
            $DisplayName = ucfirst($this->getLoadedModule());
        }

        return \WHMCS\Input\Sanitize::makeSafeForOutput($DisplayName);
    }

    public function getAPIVersion()
    {
        $APIVersion = $this->getMetaDataValue("APIVersion");
        if( !$APIVersion ) 
        {
            $APIVersion = $this->getDefaultAPIVersion();
        }

        return $APIVersion;
    }

    public function getApplicationLinkDescription()
    {
        return $this->getMetaDataValue("ApplicationLinkDescription");
    }

    public function getLogoFilename()
    {
        $modulePath = $this->getBaseModuleDir() . DIRECTORY_SEPARATOR . $this->getLoadedModule() . DIRECTORY_SEPARATOR;
        $logoExtensions = array( ".png", ".jpg", ".gif" );
        $assetHelper = \DI::make("asset");
        foreach( $logoExtensions as $extension ) 
        {
            if( file_exists($modulePath . "logo" . $extension) ) 
            {
                return $assetHelper->getWebRoot() . str_replace(ROOTDIR, "", $modulePath) . "logo" . $extension;
            }

        }
        return "";
    }

    public function getSmallLogoFilename()
    {
        $modulePath = $this->getBaseModuleDir() . DIRECTORY_SEPARATOR . $this->getLoadedModule() . DIRECTORY_SEPARATOR;
        $logoExtensions = array( ".png", ".jpg", ".gif" );
        foreach( $logoExtensions as $extension ) 
        {
            if( file_exists($modulePath . "logo_small" . $extension) ) 
            {
                return str_replace(ROOTDIR, "", $modulePath) . "logo_small" . $extension;
            }

        }
        return "";
    }

    protected function getDefaultAPIVersion()
    {
        $moduleType = $this->getType();
        switch( $moduleType ) 
        {
            case "gateways":
                $version = "1.0";
                break;
            default:
                $version = "1.1";
        }
        return $version;
    }

    public function prepareParams($params)
    {
        $whmcs = \App::self();
        $this->addParam("whmcsVersion", $whmcs->getVersion()->getCanonical());
        if( version_compare($this->getAPIVersion(), "1.1", "<") ) 
        {
            $params = \WHMCS\Input\Sanitize::convertToCompatHtml($params);
        }
        else
        {
            if( version_compare($this->getAPIVersion(), "1.1", ">=") ) 
            {
                $params = \WHMCS\Input\Sanitize::decode($params);
            }

        }

        return $params;
    }

    protected function addParam($key, $value)
    {
        $this->moduleParams[$key] = $value;
        return $this;
    }

    public function getParams()
    {
        $moduleParams = $this->moduleParams;
        return $this->prepareParams($moduleParams);
    }

    public function getParam($key)
    {
        $moduleParams = $this->getParams();
        return (isset($moduleParams[$key]) ? $moduleParams[$key] : "");
    }

    public function findTemplate($templateName)
    {
        $templateName = preg_replace("/\\.tpl\$/", "", $templateName);
        $whmcs = \App::self();
        $currentTheme = $whmcs->getClientAreaTemplate()->getName();
        $templatePath = DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $currentTheme;
        $modulePath = DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $this->getType() . DIRECTORY_SEPARATOR . $this->getLoadedModule();
        $moduleTemplateProvidedByTheme = $templatePath . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $this->getType() . DIRECTORY_SEPARATOR . $this->getLoadedModule() . DIRECTORY_SEPARATOR . $templateName . ".tpl";
        $themeSpecificModuleTemplate = $modulePath . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $currentTheme . DIRECTORY_SEPARATOR . $templateName . ".tpl";
        $moduleTemplateInModuleSubdirectory = $modulePath . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $templateName . ".tpl";
        $moduleTemplateInModuleDirectory = $modulePath . DIRECTORY_SEPARATOR . $templateName . ".tpl";
        if( file_exists(ROOTDIR . $moduleTemplateProvidedByTheme) ) 
        {
            return $moduleTemplateProvidedByTheme;
        }

        if( file_exists(ROOTDIR . $themeSpecificModuleTemplate) ) 
        {
            return $themeSpecificModuleTemplate;
        }

        if( file_exists(ROOTDIR . $moduleTemplateInModuleSubdirectory) ) 
        {
            return $moduleTemplateInModuleSubdirectory;
        }

        if( file_exists(ROOTDIR . $moduleTemplateInModuleDirectory) ) 
        {
            return $moduleTemplateInModuleDirectory;
        }

        return "";
    }

    public function isApplicationLinkSupported()
    {
        return $this->functionExists("CreateApplicationLink") && $this->functionExists("DeleteApplicationLink");
    }

    public function isApplicationLinkingEnabled()
    {
        $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrNew(array( "module_type" => $this->getType(), "module_name" => $this->getLoadedModule() ));
        return $appLink->isEnabled;
    }

    public function activate(array $parameters = array(  ))
    {
        throw new \WHMCS\Exception\Module\NotImplemented();
    }

    public function deactivate()
    {
        throw new \WHMCS\Exception\Module\NotImplemented();
    }

    public function updateConfiguration(array $parameters = array(  ))
    {
        throw new \WHMCS\Exception\Module\NotImplemented();
    }

    public function getConfiguration()
    {
        throw new \WHMCS\Exception\Module\NotImplemented();
    }

}


