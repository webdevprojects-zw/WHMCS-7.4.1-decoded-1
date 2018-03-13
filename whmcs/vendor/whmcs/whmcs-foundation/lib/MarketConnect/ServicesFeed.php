<?php 
namespace WHMCS\MarketConnect;


class ServicesFeed
{
    protected $services = NULL;

    public function __construct()
    {
        $transientData = new \WHMCS\TransientData();
        $services = $transientData->retrieve("MarketConnectServices");
        if( $services ) 
        {
            $services = json_decode($services, true);
        }

        if( (is_null($services) || !is_array($services)) && MarketConnect::isAccountConfigured() ) 
        {
            try
            {
                $api = new Api();
                $services = $api->services();
                $transientData->store("MarketConnectServices", json_encode($services), 7 * 24 * 60 * 60);
            }
            catch( \Exception $e ) 
            {
            }
        }

        $this->services = $services;
        $this->convertRecommendedRrpPrices(1);
    }

    public function getEmulationOfConfiguredProducts($serviceGroup)
    {
        $groupMap = array( "symantec" => array( "RapidSSL", "GeoTrust", "Symantec" ), "weebly" => array( "Weebly" ), "spamexperts" => array( "SpamExperts" ) );
        $validGroups = $groupMap[$serviceGroup];
        $productCollection = new \Illuminate\Support\Collection();
        foreach( $this->services as $group ) 
        {
            if( in_array($group["display_name"], $validGroups) ) 
            {
                foreach( $group["services"] as $listing ) 
                {
                    $product = new Product();
                    $product->name = $listing["display_name"];
                    $product->moduleConfigOption1 = $listing["id"];
                    $product->isHidden = false;
                    $productCollection->push($product);
                }
            }

        }
        return $productCollection;
    }

    public function isNotAvailable()
    {
        return is_null($this->services);
    }

    public function getTerms()
    {
        $serviceTerms = array(  );
        foreach( $this->services as $group ) 
        {
            if( isset($group["services"]) ) 
            {
                foreach( $group["services"] as $serviceData ) 
                {
                    $serviceTerms[$serviceData["id"]] = $serviceData["terms"];
                }
            }

        }
        return $serviceTerms;
    }

    public function getPricing($keyToFetch = "price")
    {
        $pricing = array(  );
        foreach( $this->services as $group ) 
        {
            if( isset($group["services"]) ) 
            {
                foreach( $group["services"] as $service ) 
                {
                    foreach( $service["terms"] as $key => $term ) 
                    {
                        $pricing[$service["id"]][$key] = $term[$keyToFetch];
                    }
                }
            }

        }
        return $pricing;
    }

    public function getCostPrice($productKey)
    {
        $pricing = $this->getPricing();
        return (isset($pricing[$productKey][0]) ? "\$" . $pricing[$productKey][0] : "-");
    }

    public function getRecommendedRetailPrice($productKey)
    {
        $pricing = $this->getPricing("recommendedRrp");
        return (isset($pricing[$productKey][0]) ? "\$" . $pricing[$productKey][0] : "-");
    }

    public function convertRecommendedRrpPrices($rate)
    {
        $pricing = array(  );
        foreach( $this->services as $groupKey => $group ) 
        {
            if( isset($group["services"]) ) 
            {
                foreach( $group["services"] as $serviceKey => $service ) 
                {
                    foreach( $service["terms"] as $termKey => $term ) 
                    {
                        $this->services[$groupKey]["services"][$serviceKey]["terms"][$termKey]["recommendedRrpDefaultCurrency"] = (0 < $rate ? format_as_currency($term["recommendedRrp"] / $rate) : 0);
                    }
                }
            }

        }
    }

}


