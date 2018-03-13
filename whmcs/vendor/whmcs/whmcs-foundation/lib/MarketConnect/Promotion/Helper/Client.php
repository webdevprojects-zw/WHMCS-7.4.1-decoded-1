<?php 
namespace WHMCS\MarketConnect\Promotion\Helper;


class Client
{
    protected $clientId = NULL;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    public function hasSharedOrResellerProduct()
    {
        $productIds = \WHMCS\MarketConnect\Product::whereIn("type", array( "hostingaccount", "reselleraccount" ))->pluck("id");
        return 0 < \WHMCS\Service\Service::where("userid", $this->clientId)->whereIn("packageid", $productIds)->where("domainstatus", "Active")->count();
    }

    public function getProductProductKeys()
    {
        $serviceProductIds = \WHMCS\Service\Service::where("userid", $this->clientId)->where("domainstatus", "Active")->pluck("packageid");
        return \WHMCS\MarketConnect\Product::where("servertype", "marketconnect")->whereIn("id", $serviceProductIds)->pluck("configoption1");
    }

    public function getAddonProductKeys()
    {
        $serviceAddonIds = \WHMCS\Service\Addon::where("userid", $this->clientId)->where("status", "Active")->pluck("addonid");
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        return \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $marketConnectAddonIds)->whereIn("entity_id", $serviceAddonIds)->where("setting_name", "configoption1")->pluck("value");
    }

    public function getProductAndAddonProductKeys()
    {
        return $this->getProductProductKeys()->merge($this->getAddonProductKeys());
    }

    public function getServices()
    {
        $services = \WHMCS\Service\Service::with(array( "product", "addons" => function($query)
{
    $query->whereIn("status", array( "Active" ));
}

, "addons.productAddon" => function($query)
{
    $query->where("module", "=", "marketconnect");
}

, "addons.productAddon.moduleConfiguration" => function($query)
{
    $query->where("setting_name", "=", "configoption1")->where(function($query)
{
    $query->where("value", "like", "weebly_%")->orWhere("value", "like", "spamexperts_%");
}

);
}

 ))->where("userid", "=", $this->clientId)->where("domainstatus", "=", "Active")->get();
        $accounts = array(  );
        foreach( $services as $serviceModel ) 
        {
            if( $serviceModel->product->module == "marketconnect" ) 
            {
                $type = explode("_", $serviceModel->product->moduleConfigOption1);
                $accounts[$type[0]][] = array( "type" => "service", "id" => $serviceModel->id, "domain" => $serviceModel->domain );
            }

            foreach( $serviceModel->addons as $addon ) 
            {
                foreach( $addon->productAddon->moduleConfiguration as $moduleConfiguration ) 
                {
                    $type = explode("_", $moduleConfiguration->value);
                    $accounts[$type[0]][] = array( "type" => "addon", "id" => $addon->id, "domain" => $serviceModel->domain );
                }
            }
        }
        return $accounts;
    }

}


