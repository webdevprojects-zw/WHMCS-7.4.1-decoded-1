<?php 
namespace WHMCS\MarketConnect\Promotion\Service;


class SpamExperts extends ServicePromotion
{
    protected $productKeys = array( "spamexperts_incoming", "spamexperts_outgoing", "spamexperts_incomingoutgoing", "spamexperts_incomingoutgoingarchiving" );
    protected $upsells = array( "spamexperts_incoming" => array( "spamexperts_incomingoutgoing" ), "spamexperts_outgoing" => array( "spamexperts_incomingoutgoing" ), "spamexperts_incomingoutgoing" => array( "spamexperts_incomingoutgoingarchiving" ) );
    protected $upsellPromoContent = array( "spamexperts_incomingoutgoing" => array( "imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Upgrade to Incoming & Outgoing Protection", "tagline" => "Protect your network reputation and stop spam ever leaving", "highlights" => array( "Complete protection for both inbound & outbound email", "Protect the reputation of your brand and IT-systems", "Increase outbound email continuity and delivery" ), "cta" => "Upgrade to" ), "spamexperts_incomingoutgoingarchiving" => array( "imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Upgrade to Full Suite Protection", "tagline" => "Get Incoming/Outgoing Protection & Email Archiving for one combined price", "highlights" => array( "Complete protection for both inbound & outbound email", "Increase outbound email continuity and delivery", "Become legally compliant with compressed, encrypted and secure backups of all your important email" ), "cta" => "Upgrade to" ) );
    protected $promotionalContent = array( "generic-promo" => array( "imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Add Security to your Email and say goodbye to spam", "tagline" => "With near 100% filtering accuracy and increased email continuity", "features" => array( "Near 100% filtering accuracy", "Increased email continuity & redundancy", "Easy setup and configuration", "Supports up to 1000 email boxes" ), "learnMoreRoute" => "store-emailservices-index", "cta" => "Add", "ctaRoute" => "store-emailservices-index", "class" => "spamexperts spamexperts-generic-promo" ), "upsell-combined" => array( "imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Add Outgoing Email Protection to protect your brand", "tagline" => "Secure outgoing email for complete protection", "features" => array( "Protection for inbound and outbound email", "Avoid blacklisting and de-listing", "Supports up to 1000 email boxes" ), "learnMoreRoute" => "store-emailservices-index", "class" => "spamexperts spamexperts-upsell-combined" ) );

    public function clientAreaHomeOutput()
    {
        $client = new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid"));
        $productKeys = $client->getProductAndAddonProductKeys();
        if( $this->collectionContains($productKeys, $this->productKeys) ) 
        {
            $accounts = $client->getServices();
            return $this->renderLogin("spamexperts", $accounts["spamexperts"]);
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.client-home") ) 
        {
            return NULL;
        }

        $firstSeProduct = \WHMCS\MarketConnect\Product::spamexperts()->visible()->orderBy("order")->first();
        if( is_null($firstSeProduct) ) 
        {
            return NULL;
        }

        return $this->renderPromotion("upsell", "generic-promo", $firstSeProduct);
    }

    public function productDetailsOutput($vars)
    {
        if( in_array($vars[0]["service"]->status, $this->noPromotionStatuses) ) 
        {
            return false;
        }

        $currentService = $vars[0]["service"];
        $currentServiceId = $currentService->id;
        $serviceInterface = new \WHMCS\MarketConnect\Promotion\Helper\Service($currentService);
        if( in_array($currentService->product->configoption1, $this->productKeys) ) 
        {
            return $this->renderLogin("spamexperts", array( array( "type" => "service", "id" => $currentServiceId ) ));
        }

        if( $this->collectionContains($serviceInterface->getAddonProductKeys(), $this->productKeys) ) 
        {
            $addon = $serviceInterface->getActiveAddonByProductKeys($this->productKeys);
            return $this->renderLogin("spamexperts", array( array( "type" => "addon", "id" => $addon->id ) ));
        }

        if( !in_array($currentService->product->type, array( "hostingaccount", "reselleraccount" )) ) 
        {
            return false;
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.product-details") ) 
        {
            return NULL;
        }

        $firstSeProduct = \WHMCS\MarketConnect\Product::spamexperts()->visible()->orderBy("order")->first();
        if( !is_null($firstSeProduct) ) 
        {
            return $this->renderPromotion("slimupsell", "generic-promo", $firstSeProduct, $currentServiceId);
        }

    }

    public function cartConfigureProductAddon($addonsByGroup, $addons, $billingCycle)
    {
        if( 0 < count($addonsByGroup["spamexperts"]) ) 
        {
            $firstCycle = null;
            $addonOptions = array(  );
            foreach( $addonsByGroup["spamexperts"] as $addonId ) 
            {
                $addonInfo = $addons->where("id", $addonId);
                if( !is_null($addonInfo) ) 
                {
                    $addonInfo = $addonInfo->first();
                    $name = $addonInfo["name"];
                    $name = explode("-", $name, 2);
                    $name = $name[1];
                    if( isset($addonInfo["billingCycles"][$billingCycle]) ) 
                    {
                        $cycle = $billingCycle;
                        $pricing = $addonInfo["billingCycles"][$billingCycle];
                    }
                    else
                    {
                        $cycle = $addonInfo["minCycle"];
                        $pricing = $addonInfo["minPrice"];
                    }

                    if( is_null($firstCycle) ) 
                    {
                        $firstCycle = $cycle;
                    }

                    if( empty($pricing["price"]) ) 
                    {
                        continue;
                    }

                    $addonOptions[] = "<label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[spamexperts]\" value=\"" . $addonId . "\" class=\"addon-selector\"" . (($addonInfo["status"] ? " checked" : "")) . "> &nbsp; " . $name . "<span class=\"pull-right\">" . $pricing["price"]->toFull() . "</span></label>";
                }

            }
            if( 0 < count($addonOptions) ) 
            {
                return "\n                    <div class=\"addon-promo-container\">\n                        <div class=\"description\">\n                            <div class=\"logo\">\n                                <img src=\"assets/img/marketconnect/spamexperts/logo.png\" width=\"60\">\n                            </div>\n                            <h3>SpamExperts Email Security</h3>\n                            <p>Add professional email security and archiving to your domain to protect and secure your email against attacks and malware.<br><a href=\"" . routePath("store-emailservices-index") . "\" target=\"_blank\">Learn more...</a></p>\n                        </div>\n                        <div class=\"clearfix\"></div>\n                        <div class=\"pull-right\"><strong>" . \Lang::trans("orderpaymentterm" . $firstCycle) . "</strong></div>\n                        <label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[spamexperts]\" class=\"addon-selector\" checked> &nbsp; None<span class=\"pull-right\">-</span></label><br>\n                        " . implode("<br>", $addonOptions) . "\n                    </div>\n                ";
            }

        }

    }

    public function cartViewPromotion()
    {
        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.cart-view") ) 
        {
            return NULL;
        }

        $cart = new \WHMCS\MarketConnect\Promotion\Helper\Cart();
        if( $cart->hasSharedHosting() ) 
        {
            if( $this->collectionContains($cart->getMarketConnectProductKeys(), array( "spamexperts_incoming", "spamexperts_outgoing" )) ) 
            {
                $product = \WHMCS\MarketConnect\Product::spamexperts()->visible()->where("configoption1", "spamexperts_incomingoutgoing")->orderBy("order")->first();
                if( !is_null($product) ) 
                {
                    return $this->renderPromotion("cart", "upsell-combined", $product);
                }

            }
            else
            {
                if( $this->collectionContains($cart->getMarketConnectProductKeys(), $this->productKeys) ) 
                {
                    return NULL;
                }

            }

            $product = \WHMCS\MarketConnect\Product::spamexperts()->visible()->orderBy("order")->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("cart", "generic-promo", $product);
            }

        }

    }

    public function cartCheckoutPromotion()
    {
        return $this->cartViewPromotion();
    }

    public function clientAreaSidebars()
    {
    }

}


