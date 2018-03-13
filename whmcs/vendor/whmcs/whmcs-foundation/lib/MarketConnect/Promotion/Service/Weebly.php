<?php 
namespace WHMCS\MarketConnect\Promotion\Service;


class Weebly extends ServicePromotion
{
    protected $productKeys = array( "weebly_starter", "weebly_pro", "weebly_business" );
    protected $upsells = array( "weebly_starter" => array( "weebly_pro" ), "weebly_pro" => array( "weebly_business" ) );
    protected $upsellPromoContent = array( "weebly_pro" => array( "imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Weebly Pro", "tagline" => "Everything you need to build a powerful website with design, marketing and commerce tools.", "highlights" => array( "Professional site features including site search, video backgrounds and password protection", "Add up to 25 products to your Online eCommerce Store", "Use rich HD video and audio content to enhance your site" ), "cta" => "Upgrade to" ), "weebly_business" => array( "imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Weebly Business", "tagline" => "With a fully integrated eCommerce solution for small businesses and stores, scale your storefront with ease.", "highlights" => array( "No limit on the number of products you can sell in your Online eCommerce Store", "Powerful E-Commerce features including inventory management, order tracking, shipping, taxes and more", "0% Weebly Transaction fees" ), "cta" => "Upgrade to" ) );
    protected $idealFor = array( "weebly_starter" => "Personal Use", "weebly_pro" => "Groups + Organizations", "weebly_business" => "Businesses + Stores" );
    protected $siteFeatures = array( "weebly_starter" => array( "Drag & Drop Builder", "Unlimited Pages" ), "weebly_pro" => array( "Drag & Drop Builder", "Unlimited Pages", "Site Search", "Password Protection", "Video Backgrounds", "HD Video & Audio", "Up to 100 Members" ), "weebly_business" => array( "Drag & Drop Builder", "Unlimited Pages", "Site Search", "Password Protection", "Video Backgrounds", "HD Video & Audio", "Up to 100 Members", "Membership Registration" ) );
    protected $ecommerceFeatures = array( "weebly_starter" => array( "3% Weebly Transaction Fees", "Up to 10 Products", "Checkout on Weebly.com" ), "weebly_pro" => array( "3% Weebly Transaction Fees", "Up to 25 Products", "Checkout on Weebly.com" ), "weebly_business" => array( "0% Weebly Transaction Fees", "Unlimited Products", "Checkout on your domain", "Digital Goods", "Inventory Management", "Shipping & Tax Calculator", "Coupons" ) );
    protected $promotionalContent = array( "generic-promo" => array( "template" => "signup", "imagePath" => "assets/img/marketconnect/weebly/dragdropeditor.png", "headline" => "Build your website with ease using the Powerful Weebly Site Builder", "tagline" => "Weebly's powerful drag and drop website builder and guided set up get you to the finish line faster, no coding needed.", "features" => array( "Range of stunning themes to choose from", "Drag and drop editor" ), "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Add Weebly", "class" => "weebly weebly-generic-promo" ), "viewcart" => array( "class" => "weebly weebly-viewcart", "imagePath" => "assets/img/marketconnect/weebly/dragdropeditor.png", "headline" => "Add Weebly Site Builder", "tagline" => "Building a website has never been easier", "description" => "Weebly's drag and drop website builder makes it easy to create a powerful, professional website. With lots of themes to choose from, create your site now.", "learnMoreRoute" => "store-websitebuilder-index" ), "add-v2" => array( "template" => "signup", "imagePath" => "assets/img/marketconnect/weebly/dragdropeditor.png", "headline" => "Weebly Website Builder", "tagline" => "Join over 40 million people and small businesses using Weebly", "description" => "With Weebly's powerful drag & drop website building tool, it's never been easier to create a professional site, blog or online store in minutes.", "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Get Started Now from", "class" => "weebly-add-v2" ) );

    public function getIdealFor($key)
    {
        return (isset($this->idealFor[$key]) ? $this->idealFor[$key] : array(  ));
    }

    public function getSiteFeatures($key)
    {
        return (isset($this->siteFeatures[$key]) ? $this->siteFeatures[$key] : array(  ));
    }

    public function getEcommerceFeatures($key)
    {
        return (isset($this->ecommerceFeatures[$key]) ? $this->ecommerceFeatures[$key] : array(  ));
    }

    public function clientAreaHomeOutput()
    {
        $client = new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid"));
        $productKeys = $client->getProductAndAddonProductKeys();
        if( $this->collectionContains($productKeys, $this->productKeys) ) 
        {
            $accounts = $client->getServices();
            return $this->renderLogin("weebly", $accounts["weebly"]);
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.client-home") ) 
        {
            return NULL;
        }

        $firstWeeblyProduct = \WHMCS\MarketConnect\Product::weebly()->visible()->orderBy("order")->first();
        if( is_null($firstWeeblyProduct) ) 
        {
            return NULL;
        }

        return $this->renderPromotion("upsell", "generic-promo", $firstWeeblyProduct);
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
            return $this->renderLogin("weebly", array( array( "type" => "service", "id" => $currentServiceId ) ));
        }

        if( $this->collectionContains($serviceInterface->getAddonProductKeys(), $this->productKeys) ) 
        {
            $addon = $serviceInterface->getActiveAddonByProductKeys($this->productKeys);
            return $this->renderLogin("weebly", array( array( "type" => "addon", "id" => $addon->id ) ));
        }

        if( !in_array($currentService->product->type, array( "hostingaccount" )) ) 
        {
            return false;
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.product-details") ) 
        {
            return NULL;
        }

        $firstWeeblyProduct = \WHMCS\MarketConnect\Product::weebly()->visible()->orderBy("order")->first();
        if( is_null($firstWeeblyProduct) ) 
        {
            return NULL;
        }

        return $this->renderPromotion("slimupsell", "generic-promo", $firstWeeblyProduct, $currentServiceId);
    }

    public function cartConfigureProductAddon($addonsByGroup, $addons, $billingCycle)
    {
        if( 0 < count($addonsByGroup["weebly"]) ) 
        {
            $firstCycle = null;
            $addonOptions = array(  );
            foreach( $addonsByGroup["weebly"] as $addonId ) 
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

                    $addonOptions[] = "<label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[weebly]\" value=\"" . $addonId . "\" class=\"addon-selector\"" . (($addonInfo["status"] ? " checked" : "")) . "> &nbsp; " . $name . "<span class=\"pull-right\">" . $pricing["price"]->toFull() . "</span></label>";
                }

            }
            if( 0 < count($addonOptions) ) 
            {
                return "\n                    <div class=\"addon-promo-container\">\n                        <div class=\"description\">\n                            <div class=\"logo\">\n                                <img src=\"assets/img/marketconnect/weebly/logo.png\">\n                            </div>\n                            <h3>Powerful Website Builder</h3>\n                            <p>Add Weebly's drag and drop website builder to your hosting to allow you to create an awesome looking website, store or blog.<br><a href=\"" . routePath("store-websitebuilder-index") . "\" target=\"_blank\">Learn more...</a></p>\n                        </div>\n                        <div class=\"clearfix\"></div>\n                        <div class=\"pull-right\"><strong>" . \Lang::trans("orderpaymentterm" . $firstCycle) . "</strong></div>\n                        <label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[weebly]\" class=\"addon-selector\" checked> &nbsp; None<span class=\"pull-right\">-</span></label><br>\n                        " . implode("<br>", $addonOptions) . "\n                    </div>\n                ";
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
            if( $this->collectionContains($cart->getMarketConnectProductKeys(), $this->productKeys) ) 
            {
                return NULL;
            }

            $product = \WHMCS\MarketConnect\Product::weebly()->visible()->orderBy("order")->first();
            if( is_null($product) ) 
            {
                return NULL;
            }

            return $this->renderPromotion("cart", "generic-promo", $product);
        }

    }

    public function cartCheckoutPromotion()
    {
        return $this->cartViewPromotion();
    }

    public function clientAreaSidebars()
    {
        $primarySidebar = \Menu::primarySidebar();
        $secondarySidebar = \Menu::secondarySidebar();
        if( is_null($secondarySidebar->getChild("My Services Actions")) && is_null($primarySidebar->getChild("Service Details Actions")) ) 
        {
            return false;
        }

        $service = $this->getModel();
        if( is_null($service) || !$service->setting("promotion.product-list") ) 
        {
            return NULL;
        }

        if( !is_null($primarySidebar->getChild("Service Details Actions")) ) 
        {
            $serviceId = \App::getFromRequest("id");
            $service = \WHMCS\Service\Service::find($serviceId);
            $serviceHelper = new \WHMCS\MarketConnect\Promotion\Helper\Service($service);
            $addons = $serviceHelper->getProductAndAddonProductKeys();
            foreach( $this->productKeys as $productKey ) 
            {
                if( $addons->contains($productKey) ) 
                {
                    return NULL;
                }

            }
        }

        $secondarySidebar->addChild("Website Builder Promo", array( "name" => "Website Builder Promo", "label" => "Add Website Builder", "order" => 100, "icon" => "", "attributes" => array( "class" => "panel-promo panel-promo-weebly" ), "bodyHtml" => "<div class=\"text-center\">\n    <a href=\"" . routePath("store-websitebuilder-index") . "\" style=\"font-weight: 300;\">\n        <img src=\"" . \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/assets/img/marketconnect/weebly/dragdropeditor.png\" style=\"max-width: 100%;\">\n        <span>Create a stunning website faster than ever with Weebly</span>\n    </a>\n</div>", "footerHtml" => "<i class=\"fa fa-arrow-right fa-fw\"></i> <a href=\"" . routePath("store-websitebuilder-index") . "\">Learn more</a>" ));
    }

}


