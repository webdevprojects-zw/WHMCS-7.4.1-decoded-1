<?php 
namespace WHMCS\MarketConnect\Promotion\Helper;


class Cart
{
    protected $productTypes = NULL;
    protected $marketConnectProductKeys = NULL;
    protected $sslTypes = array( "rapidssl", "geotrust", "symantec" );

    public function getProductTypes()
    {
        if( is_null($this->productTypes) ) 
        {
            $productTypesMap = \WHMCS\Product\Product::pluck("type", "id");
            $orderFrm = new \WHMCS\OrderForm();
            $cartProducts = collect($orderFrm->getCartDataByKey("products"));
            $cartProducts = $cartProducts->pluck("pid");
            $this->productTypes = array(  );
            foreach( $cartProducts as $pid ) 
            {
                $this->productTypes[] = $productTypesMap[$pid];
            }
        }

        return $this->productTypes;
    }

    public function hasSharedHosting()
    {
        return in_array("hostingaccount", $this->getProductTypes());
    }

    public function hasResellerHosting()
    {
        return in_array("reselleraccount", $this->getProductTypes());
    }

    public function hasServerProduct()
    {
        return in_array("server", $this->getProductTypes());
    }

    public function hasOtherProduct()
    {
        return in_array("other", $this->getProductTypes());
    }

    public function getMarketConnectProductKeys()
    {
        if( is_null($this->marketConnectProductKeys) ) 
        {
            $orderFrm = new \WHMCS\OrderForm();
            $cartProducts = collect($orderFrm->getCartDataByKey("products"));
            $cartProductAddons = $cartProducts->pluck("addons")->flatten();
            $cartProducts = $cartProducts->pluck("pid");
            $productProductKeys = collect();
            if( 0 < $cartProducts->count() ) 
            {
                $productProductKeys = \WHMCS\Product\Product::where("servertype", "marketconnect")->whereIn("id", $cartProducts)->pluck("configoption1");
            }

            $cartAddons = collect($orderFrm->getCartDataByKey("addons"));
            $cartAddons = $cartAddons->pluck("id");
            $cartAddons = $cartAddons->merge($cartProductAddons);
            $addonProductKeys = collect();
            if( 0 < $cartAddons->count() ) 
            {
                $addonProductKeys = \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $cartAddons)->where("setting_name", "configoption1")->pluck("value");
            }

            $this->marketConnectProductKeys = $productProductKeys->merge($addonProductKeys);
        }

        return $this->marketConnectProductKeys;
    }

    public function isUpSellForAddon($addonId, $newAddonId)
    {
        $addonIds = $addonProductKeys = \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", array( $addonId, $newAddonId ))->where("setting_name", "configoption1")->pluck("value");
        $firstType = explode("_", $addonIds[0]);
        $secondType = explode("_", $addonIds[1]);
        if( $firstType[0] == $secondType[0] || in_array($firstType[0], $this->sslTypes) && in_array($secondType[0], $this->sslTypes) ) 
        {
            return true;
        }

        return false;
    }

}


