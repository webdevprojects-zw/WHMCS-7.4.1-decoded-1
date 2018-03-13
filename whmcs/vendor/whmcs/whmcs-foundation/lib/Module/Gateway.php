<?php 
namespace WHMCS\Module;


class Gateway extends AbstractModule
{
    protected $type = "gateways";
    protected $usesDirectories = false;
    protected $activeList = "";

    public function __construct()
    {
        $whmcs = \WHMCS\Application::getInstance();
        $this->addParam("companyname", $whmcs->get_config("CompanyName"));
        $this->addParam("systemurl", $whmcs->getSystemURL());
        $this->addParam("langpaynow", $whmcs->get_lang("invoicespaynow"));
        $whmcs->load_function("gateway");
    }

    public static function factory($name)
    {
        $gateway = new Gateway();
        if( !$gateway->load($name) ) 
        {
            throw new \WHMCS\Exception\Fatal("Module Not Found");
        }

        if( !$gateway->isActive() ) 
        {
            throw new \WHMCS\Exception\Fatal("Module Not Activated");
        }

        return $gateway;
    }

    public function getActiveGateways()
    {
        if( is_array($this->activeList) ) 
        {
            return $this->activeList;
        }

        $this->activeList = array(  );
        $result = select_query("tblpaymentgateways", "DISTINCT gateway", "");
        while( $data = mysql_fetch_array($result) ) 
        {
            $gateway = $data[0];
            if( \WHMCS\Gateways::isNameValid($gateway) ) 
            {
                $this->activeList[] = $gateway;
            }

        }
        return $this->activeList;
    }

    public function isActiveGateway($gateway)
    {
        $gateways = $this->getActiveGateways();
        return in_array($gateway, $gateways);
    }

    public function getDisplayName()
    {
        $paymentGateways = new \WHMCS\Gateways();
        return $paymentGateways->getDisplayName($this->loadedmodule);
    }

    public function getAvailableGateways($invoiceid = "")
    {
        $validgateways = array(  );
        $result = full_query("SELECT DISTINCT gateway, (SELECT value FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `name`, (SELECT `order` FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `order` FROM `tblpaymentgateways` g1 WHERE setting='visible' AND value='on' ORDER BY `order` ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $validgateways[$data[0]] = $data[1];
        }
        if( $invoiceid ) 
        {
            $invoiceid = (int) $invoiceid;
            $invoicegateway = get_query_val("tblinvoices", "paymentmethod", array( "id" => $invoiceid ));
            $disabledgateways = array(  );
            $result = select_query("tblinvoiceitems", "", array( "type" => "Hosting", "invoiceid" => $invoiceid ));
            while( $data = mysql_fetch_assoc($result) ) 
            {
                $relid = $data["relid"];
                if( $relid ) 
                {
                    $result2 = full_query("SELECT pg.disabledgateways AS disabled FROM tblhosting h LEFT JOIN tblproducts p on h.packageid = p.id LEFT JOIN tblproductgroups pg on p.gid = pg.id where h.id = " . (int) $relid);
                    $data2 = mysql_fetch_assoc($result2);
                    $gateways = explode(",", $data2["disabled"]);
                    foreach( $gateways as $gateway ) 
                    {
                        if( array_key_exists($gateway, $validgateways) && $gateway != $invoicegateway ) 
                        {
                            unset($validgateways[$gateway]);
                        }

                    }
                }

            }
        }

        return $validgateways;
    }

    public function getFirstAvailableGateway()
    {
        $gateways = $this->getAvailableGateways();
        return key($gateways);
    }

    public function load($module)
    {
        $loadStatus = parent::load($module);
        if( $loadStatus ) 
        {
            $this->loadSettings();
        }

        return $loadStatus;
    }

    public function loadSettings()
    {
        $gateway = $this->getLoadedModule();
        $settings = array( "paymentmethod" => $gateway );
        $result = select_query("tblpaymentgateways", "", array( "gateway" => $gateway ));
        while( $data = mysql_fetch_array($result) ) 
        {
            $setting = $data["setting"];
            $value = $data["value"];
            $this->addParam($setting, $value);
            $settings[$setting] = $value;
        }
        return $settings;
    }

    public function isActive()
    {
        return ($this->getParam("type") ? true : false);
    }

    public function call($function, $params = array(  ))
    {
        $this->addParam("paymentmethod", $this->getLoadedModule());
        return parent::call($function, $params);
    }

    public function activate(array $parameters = array(  ))
    {
        if( $this->isActive() ) 
        {
            throw new \WHMCS\Exception\Module\NotActivated("Module already active");
        }

        $lastOrder = (int) get_query_val("tblpaymentgateways", "`order`", array( "setting" => "name", "gateway" => $this->getLoadedModule() ), "order", "DESC");
        if( !$lastOrder ) 
        {
            $lastOrder = (int) get_query_val("tblpaymentgateways", "`order`", "", "order", "DESC");
            $lastOrder++;
        }

        $configData = $this->getConfiguration();
        $displayName = $configData["FriendlyName"]["Value"];
        $gatewayType = ($this->functionExists("capture") ? "CC" : "Invoices");
        $this->saveConfigValue("name", $displayName, $lastOrder);
        $this->saveConfigValue("type", $gatewayType);
        $this->saveConfigValue("visible", "on");
        if( $configData["RemoteStorage"] ) 
        {
            $this->saveConfigValue("remotestorage", "1");
        }

        if( !function_exists("logAdminActivity") ) 
        {
            require(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php");
        }

        logAdminActivity("Gateway Module Activated: '" . $displayName . "'");
        $this->load($this->getLoadedModule());
        $this->updateConfiguration($parameters);
        return true;
    }

    public function deactivate()
    {
        if( !$this->isActive() ) 
        {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }

        $configData = $this->getConfiguration();
        $displayName = $configData["FriendlyName"]["Value"];
        delete_query("tblpaymentgateways", array( "gateway" => $this->getLoadedModule() ));
        if( !function_exists("logAdminActivity") ) 
        {
            require(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php");
        }

        logAdminActivity("Gateway Module Deactivated: '" . $displayName . "'");
        return true;
    }

    protected function saveConfigValue($setting, $value, $order = 0)
    {
        delete_query("tblpaymentgateways", array( "gateway" => $this->getLoadedModule(), "setting" => $setting ));
        insert_query("tblpaymentgateways", array( "gateway" => $this->getLoadedModule(), "setting" => $setting, "value" => $value, "order" => $order ));
        $this->addParam($setting, $value);
    }

    public function getConfiguration()
    {
        if( !$this->getLoadedModule() ) 
        {
            throw new \WHMCS\Exception("No module loaded to fetch configuration for");
        }

        if( $this->functionExists("config") ) 
        {
            return $this->call("config");
        }

        if( $this->functionExists("activate") ) 
        {
            $this->call("activate");
            return array_merge(array( "FriendlyName" => array( "Type" => "System", "Value" => ucfirst($this->getLoadedModule()) ) ), defineGatewayFieldStorage(true));
        }

        throw new \WHMCS\Exception\Module\NotImplemented();
    }

    public function updateConfiguration(array $parameters = array(  ))
    {
        if( !$this->isActive() ) 
        {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }

        if( 0 < count($parameters) ) 
        {
            $configData = $this->getConfiguration();
            $displayName = $configData["FriendlyName"]["Value"];
            foreach( $parameters as $key => $value ) 
            {
                if( array_key_exists($key, $configData) ) 
                {
                    $this->saveConfigValue($key, $value);
                }

            }
            if( !function_exists("logAdminActivity") ) 
            {
                require(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php");
            }

            logAdminActivity("Gateway Module Configuration Updated: '" . $displayName . "'");
        }

    }

}


