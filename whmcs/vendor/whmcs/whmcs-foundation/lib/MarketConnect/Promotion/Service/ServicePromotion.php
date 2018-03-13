<?php 
namespace WHMCS\MarketConnect\Promotion\Service;


class ServicePromotion
{
    protected $noPromotionStatuses = array( "Cancelled", "Terminated", "Fraud" );

    public function getModel()
    {
        $className = get_class($this);
        $className = substr($className, strrpos($className, "\\") + 1);
        return \WHMCS\MarketConnect\Service::where("name", $className)->first();
    }

    public function collectionContains($collection, $contains)
    {
        foreach( $contains as $containedItem ) 
        {
            if( $collection->contains($containedItem) ) 
            {
                return true;
            }

        }
        return false;
    }

    public function getBestUpsell($productKey)
    {
        if( !is_array($this->upsells) ) 
        {
            return NULL;
        }

        if( array_key_exists($productKey, $this->upsells) ) 
        {
            $upsells = $this->upsells[$productKey];
            foreach( $upsells as $upsellProductKey ) 
            {
                $product = \WHMCS\MarketConnect\Product::productKey($upsellProductKey)->visible()->first();
                if( !is_null($product) ) 
                {
                    return $product;
                }

            }
        }

    }

    public function getPromotionalContent($promotionalKey)
    {
        if( isset($this->promotionalContent[$promotionalKey]) ) 
        {
            $promotionalContent = $this->promotionalContent[$promotionalKey];
        }

        return new \WHMCS\MarketConnect\Promotion\PromotionContentWrapper($promotionalContent);
    }

    public function getUpsellPromotionalContent($promotionalKey)
    {
        if( isset($this->upsellPromoContent[$promotionalKey]) ) 
        {
            $promotionalContent = $this->upsellPromoContent[$promotionalKey];
            return new \WHMCS\MarketConnect\Promotion\PromotionContentWrapper($promotionalContent);
        }

        return null;
    }

    public function renderPromotion($template, $promotionalKey, $product, $serviceId = 0)
    {
        if( isset($this->promotionalContent[$promotionalKey]) ) 
        {
            $promotionalContent = $this->promotionalContent[$promotionalKey];
            $promotion = new \WHMCS\MarketConnect\Promotion\PromotionContentWrapper($promotionalContent);
            $activeTemplate = \WHMCS\Config\Setting::getValue("Template");
            if( !$template ) 
            {
                $template = $promotion->getTemplate();
            }

            $smarty = new \WHMCS\Smarty();
            return $smarty->fetch(ROOTDIR . "/templates/" . $activeTemplate . "/store/promos/" . $template . ".tpl", array( "product" => $product, "promotion" => $promotion, "serviceId" => $serviceId ));
        }

        throw new \Exception("Promotional content not defined for: " . $promotionalKey);
    }

    public function renderLogin($service, $accounts)
    {
        $activeTemplate = \WHMCS\Config\Setting::getValue("Template");
        $smarty = new \WHMCS\Smarty();
        return $smarty->fetch(ROOTDIR . "/templates/" . $activeTemplate . "/store/login/" . $service . ".tpl", array( "accounts" => $accounts ));
    }

}


