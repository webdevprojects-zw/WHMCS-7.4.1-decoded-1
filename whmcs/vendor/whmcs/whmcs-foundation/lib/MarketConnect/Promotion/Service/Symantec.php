<?php 
namespace WHMCS\MarketConnect\Promotion\Service;


class Symantec extends ServicePromotion
{
    protected $sslTypes = array( "dv" => array( "rapidssl_rapidssl", "geotrust_quickssl", "geotrust_quicksslpremium" ), "ov" => array( "geotrust_truebizid", "symantec_securesite", "symantec_securesitepro" ), "ev" => array( "geotrust_truebizidev", "symantec_securesiteev", "symantec_securesiteproev" ), "wildcard" => array( "rapidssl_wildcard", "geotrust_quicksslpremiumwildcard", "geotrust_truebizidwildcard" ) );
    protected $certificateFeatures = array( "rapidssl_rapidssl" => array( "displayName" => "RapidSSL", "validation" => "Domain", "issuance" => "Minutes", "for" => "Personal Websites", "retail" => "59.00", "warranty" => "10,000", "seal" => "Static", "ev" => false ), "rapidssl_wildcard" => array( "displayName" => "RapidSSL Wildcard", "validation" => "Domain", "issuance" => "Minutes", "for" => "Personal Websites", "retail" => "249.00", "warranty" => "10,000", "seal" => "Static", "ev" => false ), "geotrust_quicksslpremium" => array( "displayName" => "Geotrust QuickSSL Premium", "validation" => "Domain", "issuance" => "Minutes", "for" => "Small Business", "retail" => "149.00", "warranty" => "500,000", "seal" => "Dynamic", "ev" => false ), "geotrust_quicksslpremiumwildcard" => array( "displayName" => "Geotrust QuickSSL Premium Wildcard", "validation" => "Domain", "issuance" => "Minutes", "for" => "Small Business", "retail" => "279.00", "warranty" => "500,000", "seal" => "Dynamic", "ev" => false ), "geotrust_truebizid" => array( "displayName" => "Geotrust True BusinessID", "validation" => "Domain + Business", "issuance" => "1-3 Days", "for" => "Business", "retail" => "199.00", "warranty" => "1,250,000", "seal" => "Dynamic", "ev" => false ), "geotrust_truebizidwildcard" => array( "displayName" => "Geotrust True BusinessID Wildcard", "validation" => "Domain + Business", "issuance" => "1-3 Days", "for" => "Business & Ecommerce", "retail" => "599.00", "warranty" => "1,250,000", "seal" => "Dynamic", "ev" => false ), "geotrust_truebizidev" => array( "displayName" => "Geotrust True BusinessID with EV", "validation" => "Highest Business Validation", "issuance" => "1-5 Days", "for" => "Business & Ecommerce", "retail" => "299.00", "warranty" => "1,500,000", "seal" => "Dynamic", "ev" => true ), "symantec_securesite" => array( "displayName" => "Symantec Secure Site", "validation" => "Domain + Business", "issuance" => "1-3 Days", "for" => "Business", "retail" => "399.00", "warranty" => "1,500,000", "seal" => "Dynamic Norton Secured", "ev" => false ), "symantec_securesitepro" => array( "displayName" => "Symantec Secure Site Pro", "validation" => "Domain + Business", "issuance" => "1-3 Days", "for" => "Business + Ecommerce", "retail" => "995.00", "warranty" => "1,500,000", "seal" => "Dynamic Norton Secured", "ev" => false ), "symantec_securesiteev" => array( "displayName" => "Symantec Secure Site with EV", "validation" => "Highest Business Validation", "issuance" => "1-5 Days", "for" => "Business & Ecommerce", "retail" => "995.00", "warranty" => "1,500,000", "seal" => "Dynamic Norton Secured", "ev" => true ), "symantec_securesiteproev" => array( "displayName" => "Symantec Secure Site Pro with EV", "validation" => "Highest Business Validation", "issuance" => "1-5 Days", "for" => "Business & Ecommerce", "retail" => "1499.00", "warranty" => "1,750,000", "seal" => "Dynamic Norton Secured", "ev" => true ) );
    protected $upsells = array( "rapidssl_rapidssl" => array( "rapidssl_wildcard", "geotrust_truebizidev", "geotrust_truebizidwildcard", "geotrust_truebizid", "geotrust_quicksslpremium" ), "rapidssl_wildcard" => array( "geotrust_quicksslpremiumwildcard", "geotrust_truebizidwildcard", "geotrust_truebizidev", "symantec_securesiteev" ), "geotrust_quicksslpremium" => array( "geotrust_truebizid", "geotrust_truebizidev", "geotrust_quicksslpremiumwildcard", "geotrust_truebizidwildcard" ), "geotrust_truebizid" => array( "geotrust_truebizidev", "geotrust_truebizidwildcard", "symantec_securesite" ), "geotrust_truebizidev" => array( "symantec_securesiteev", "symantec_securesiteproev" ), "geotrust_truebizidwildcard" => array( "geotrust_truebizidev", "symantec_securesiteev", "symantec_securesiteproev" ), "symantec_securesite" => array( "symantec_securesiteev", "symantec_securesiteproev" ), "symantec_securesitepro" => array( "symantec_securesiteproev", "symantec_securesiteev" ), "symantec_securesiteev" => array( "symantec_securesiteproev" ) );
    protected $promotionalContent = array( "generic-promo" => array( "imagePath" => "assets/img/marketconnect/symantec/ssl.png", "headline" => "Protect your website and boost your search rankings with an SSL certificate", "tagline" => "Browsers are changing, don't get left behind", "learnMoreRoute" => "store-ssl-certificates-index", "cta" => "Add", "class" => "symantec-ssl symantec-generic-promo" ), "upsell-ssl" => array( "imagePath" => "assets/img/marketconnect/symantec/ssl.png", "headline" => "Protect your website and boost your search rankings with an SSL certificate", "tagline" => "Included with your SSL certificate", "features" => array( "Data protection up to 256-bit encryption", "Unlimited free reissues", "Compatible with all major browsers", "Display a security seal on your site" ), "learnMoreRoute" => "store-ssl-certificates-index", "cta" => "Add", "class" => "symantec-ssl symantec-upsell-ssl" ), "upsell-ov" => array( "imagePath" => "assets/img/marketconnect/symantec/ssl-multi.png", "headline" => "Company identity included in certificate, ideal for business websites", "tagline" => "Increase trust with a certificate that validates your organization", "features" => array( "Full business authentication", "Unlimited server licenses", "\$1.25 million warranty/loss coverage", "Trusted Site Seal" ), "cta" => "Upgrade to", "learnMoreRoute" => "store-ssl-certificates-ov", "class" => "symantec-ssl symantec-upsell-ov" ), "upsell-ev" => array( "imagePath" => "assets/img/marketconnect/symantec/green-bar-ev-promo.png", "headline" => "Establish trust and security at a glance where privacy and transactions are most critical", "tagline" => "Improve conversion rates and customer confidence with green address bar", "features" => array( "Your company name in address bar", "Green address bar" ), "cta" => "Upgrade to", "learnMoreRoute" => "store-ssl-certificates-ev", "class" => "symantec-ssl symantec-upsell-ev" ), "upsell-wildcard" => array( "imagePath" => "assets/img/marketconnect/symantec/ssl-subs.png", "headline" => "Secure multiple websites for less", "tagline" => "Manage security across every subdomain with a single Wildcard SSL certificate", "description" => "*.example.com protects these...and more", "features" => array( "www.example.com", "login.example.com", "mail.example.com", "anything.example.com" ), "cta" => "Upgrade to", "learnMoreRoute" => "store-ssl-certificates-wildcard", "class" => "symantec-ssl symantec-upsell-wildcard" ) );

    public function __construct()
    {
        $this->upsellPromoContent = array(  );
        foreach( $this->certificateFeatures as $certificate => $feature ) 
        {
            $this->upsellPromoContent[$certificate] = $this->promotionalContent["upsell-" . $this->getSslType($certificate)];
        }
    }

    public function getSslTypes()
    {
        return $this->sslTypes;
    }

    public function getCertificateFeatures()
    {
        return $this->certificateFeatures;
    }

    protected function getSslType($certificate)
    {
        foreach( $this->sslTypes as $type => $certificates ) 
        {
            if( in_array($certificate, $certificates) ) 
            {
                return $type;
            }

        }
    }

    protected function hasSslType($type, $addonKeys)
    {
        foreach( $this->sslTypes[$type] as $key ) 
        {
            if( $addonKeys->contains($key) ) 
            {
                return true;
            }

        }
        return false;
    }

    public function clientAreaHomeOutput()
    {
        $client = new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid"));
        if( !$client->hasSharedOrResellerProduct() ) 
        {
            return NULL;
        }

        $productKeys = $client->getProductAndAddonProductKeys();
        $hasDv = $this->hasSslType("dv", $productKeys);
        $hasOv = $this->hasSslType("ov", $productKeys);
        $hasEv = $this->hasSslType("ev", $productKeys);
        $hasWildcard = $this->hasSslType("wildcard", $productKeys);
        $activeSslProducts = \WHMCS\MarketConnect\Product::ssl()->visible()->orderBy("order")->get();
        if( $activeSslProducts->count() == 0 ) 
        {
            return NULL;
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.client-home") ) 
        {
            return NULL;
        }

        $upsell = null;
        foreach( $productKeys as $productKey ) 
        {
            $upsell = $this->getBestUpsell($productKey);
            if( $upsell ) 
            {
                break;
            }

        }
        if( !is_null($upsell) ) 
        {
            return $this->renderPromotion("upsell", "upsell-" . $this->getSslType($upsell->productKey), $upsell);
        }

        if( !$hasDv && !$hasOv && !$hasEv && !$hasWildcard ) 
        {
            $product = $activeSslProducts->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("upsell", "upsell-ssl", $product);
            }

        }

        if( ($hasDv || $hasOv) && !$hasEv && $this->hasSslType("ev", $activeSslProducts->pluck("configoption1")) ) 
        {
            $product = $activeSslProducts->whereIn("configoption1", $this->sslTypes["ev"])->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("upsell", "upsell-ev", $product);
            }

        }

        if( $hasDv && !$hasWildcard && $this->hasSslType("wildcard", $activeSslProducts->pluck("configoption1")) ) 
        {
            $product = $activeSslProducts->whereIn("configoption1", $this->sslTypes["wildcard"])->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("upsell", "upsell-wildcard", $product);
            }

        }

    }

    public function productDetailsOutput($vars)
    {
        if( in_array($vars[0]["service"]->status, $this->noPromotionStatuses) ) 
        {
            return false;
        }

        $currentServiceId = $vars[0]["service"]->id;
        $serviceInterface = new \WHMCS\MarketConnect\Promotion\Helper\Service($vars[0]["service"]);
        $addonKeys = $serviceInterface->getProductAndAddonProductKeys();
        $hasDv = $this->hasSslType("dv", $addonKeys);
        $hasOv = $this->hasSslType("ov", $addonKeys);
        $hasEv = $this->hasSslType("ev", $addonKeys);
        $hasWildcard = $this->hasSslType("wildcard", $addonKeys);
        $activeSslProducts = \WHMCS\MarketConnect\Product::ssl()->visible()->orderBy("order")->get();
        if( $activeSslProducts->count() == 0 ) 
        {
            return NULL;
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.product-details") ) 
        {
            return NULL;
        }

        $upsell = null;
        foreach( $addonKeys as $productKey ) 
        {
            $upsell = $this->getBestUpsell($productKey);
            if( $upsell ) 
            {
                break;
            }

        }
        if( !is_null($upsell) ) 
        {
            return $this->renderPromotion("slimupsell", "upsell-" . $this->getSslType($upsell->productKey), $upsell);
        }

        if( !in_array($vars[0]["service"]->product->type, array( "hostingaccount", "reselleraccount", "server" )) ) 
        {
            return false;
        }

        if( !$hasDv && !$hasOv && !$hasEv && !$hasWildcard ) 
        {
            $product = $activeSslProducts->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("slimupsell", "generic-promo", $product, $currentServiceId);
            }

        }

        if( ($hasDv || $hasOv) && !$hasEv && $this->hasSslType("ev", $activeSslProducts->pluck("configoption1")) ) 
        {
            $product = $activeSslProducts->whereIn("configoption1", $this->sslTypes["ev"])->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("slimupsell", "upsell-ev", $product, $currentServiceId);
            }

        }

        if( $hasDv && !$hasWildcard && $this->hasSslType("wildcard", $activeSslProducts->pluck("configoption1")) ) 
        {
            $product = $activeSslProducts->whereIn("configoption1", $this->sslTypes["wildcard"])->first();
            if( !is_null($product) ) 
            {
                return $this->renderPromotion("slimupsell", "upsell-wildcard", $product, $currentServiceId);
            }

        }

    }

    public function cartConfigureProductAddon($addonsByGroup, $addons, $billingCycle)
    {
        $firstCycle = null;
        $addonOptions = array(  );
        foreach( array( "rapidssl", "geotrust", "symantec" ) as $type ) 
        {
            foreach( $addonsByGroup[$type] as $addonId ) 
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

                    $addonOptions[] = "<label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[ssl]\" value=\"" . $addonId . "\" class=\"addon-selector\"" . (($addonInfo["status"] ? " checked" : "")) . "> &nbsp; " . $name . "<span class=\"pull-right\">" . $pricing["price"]->toFull() . "</span></label>";
                }

            }
        }
        if( 0 < count($addonOptions) ) 
        {
            return "\n                <div class=\"addon-promo-container\">\n                    <div class=\"description\">\n                        <div class=\"logo\">\n                            <img src=\"assets/img/marketconnect/symantec/ssl.png\" width=\"80\">\n                        </div>\n                        <h3>Protect your site with SSL</h3>\n                        <p>Add SSL to your web hosting to give visitors confidence that your website is safe and secure and help build trust.<br><a href=\"" . routePath("store-ssl-certificates-index") . "\" target=\"_blank\">Learn more...</a></p>\n                    </div>\n                    <div class=\"clearfix\"></div>\n                    <div class=\"pull-right\"><strong>" . \Lang::trans("orderpaymentterm" . $firstCycle) . "</strong></div>\n                    <label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[ssl]\" class=\"addon-selector\" checked> &nbsp; None<span class=\"pull-right\">-</span></label><br>\n                    " . implode("<br>", $addonOptions) . "\n                </div>\n            ";
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
        if( $cart->hasSharedHosting() || $cart->hasResellerHosting() ) 
        {
            $cartProductKeys = $cart->getMarketConnectProductKeys();
            if( ($this->hasSslType("dv", $cartProductKeys) || $this->hasSslType("ov", $cartProductKeys)) && !$this->hasSslType("ev", $cartProductKeys) ) 
            {
                $product = \WHMCS\MarketConnect\Product::ssl()->visible()->whereIn("configoption1", $this->sslTypes["ev"])->orderBy("order")->first();
                if( !is_null($product) ) 
                {
                    return $this->renderPromotion("cart", "upsell-ev", $product);
                }

            }

            if( $this->hasSslType("ev", $cartProductKeys) && !$this->hasSslType("wildcard", $cartProductKeys) ) 
            {
                $product = \WHMCS\MarketConnect\Product::ssl()->visible()->whereIn("configoption1", $this->sslTypes["wildcard"])->orderBy("order")->first();
                if( !is_null($product) ) 
                {
                    return $this->renderPromotion("cart", "upsell-wildcard", $product);
                }

            }

            if( !($this->hasSslType("dv", $cartProductKeys) || $this->hasSslType("ov", $cartProductKeys) || $this->hasSslType("ev", $cartProductKeys) || $this->hasSslType("wildcard", $cartProductKeys)) ) 
            {
                $product = \WHMCS\MarketConnect\Product::ssl()->visible()->orderBy("order")->first();
                if( !is_null($product) ) 
                {
                    return $this->renderPromotion("cart", "upsell-ssl", $product);
                }

            }

        }

        return "";
    }

    public function cartCheckoutPromotion()
    {
        return $this->cartViewPromotion();
    }

    public function clientAreaSidebars()
    {
    }

    public static function getUpsellPromotionalInformation($productKey)
    {
        switch( $productKey ) 
        {
            case "rapidssl_wildcard":
                return array( "imagePath" => "assets/img/marketconnect/symantec/ssl-subs.png", "headline" => "Secure unlimited subdomains while saving time and money", "tagline" => "With a wildcard certificate, you can secure your domain and all your subdomains", "highlights" => array( "Secure an unlimited number of subdomains", "Automatic SSL protection for any new subdomains you add", "Ideal for websites with lots of subdomains" ), "cta" => "Upgrade to Wildcard from" );
            case "geotrust_truebizidev":
            case "geotrust_quicksslpremium":
                return array( "imagePath" => "assets/img/marketconnect/symantec/green-bar-ev-promo.png", "headline" => "Add visual assurance to increase trust and customer conversions", "tagline" => "Highest available levels of trust and authentication for your website.", "highlights" => array( "Boosted consumer confidence with Green Address Bar", "Reduced shopping cart abandonment", "Protect your customers from phishing attacks" ), "cta" => "Upgrade to EV from" );
            case "spamexperts_incomingoutgoing":
                return array( "imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Add Outgoing Scanning to your order", "tagline" => "Protect your network and your reputation.", "highlights" => array( "Prevent Spam & Viruses from ever unknowingly leaving your network", "Avoid de-listing related costs and blacklisting", "Increase outbound email continuity and delivery" ), "cta" => "Upgrade to Full Scanning from" );
            case "weebly_pro":
                return array( "imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Pro", "tagline" => "Get more power for building your site.", "highlights" => array( "Extra features including site search & video backgrounds", "Increase eCommerce Store products up to 25", "Adds site membership functionality" ), "cta" => "Upgrade to Pro from" );
            case "weebly_business":
                return array( "imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Business", "tagline" => "The ideal website builder package for creating an eCommerce store.", "highlights" => array( "No limit on number of products you can offer", "Checkout on your domain for a seamless user experience", "0% Transaction Fees taken by Weebly" ), "cta" => "Upgrade to Business from" );
        }
    }

}


