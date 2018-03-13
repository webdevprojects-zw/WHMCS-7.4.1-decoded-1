<?php 
namespace WHMCS;


class OrderForm
{
    private $pid = "";
    private $productinfo = array(  );
    private $validbillingcycles = array( "free", "onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "triennially" );

    public function getCartData()
    {
        return (array) Session::get("cart");
    }

    public function getCartDataByKey($key, $keyNotFoundValue = "")
    {
        $cartSession = $this->getCartData();
        return (array_key_exists($key, $cartSession) ? $cartSession[$key] : $keyNotFoundValue);
    }

    public function getProductGroups($asCollection = false)
    {
        if( $asCollection ) 
        {
            return Product\Group::where("hidden", false)->orderBy("order")->get();
        }

        $groups = array(  );
        $groupIds = Product\Group::where("hidden", "=", false)->orderBy("order")->pluck("name", "id");
        foreach( $groupIds as $id => $name ) 
        {
            $groups[] = array( "gid" => $id, "name" => $name );
        }
        return $groups;
    }

    public function getProducts($productGroup, $includeConfigOptions = false, $includeBundles = false)
    {
        global $currency;
        $products = array(  );
        $unsortedProducts = array(  );
        $pricing = new Pricing();
        try
        {
            if( !$productGroup instanceof Product\Group ) 
            {
                $productGroup = Product\Group::findOrFail($productGroup);
            }

            if( !$productGroup instanceof Product\Group ) 
            {
                $productGroup = Product\Group::orderBy("order")->where("hidden", false)->firstOrFail();
            }

        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) 
        {
            throw new Exception("NoProductGroup");
        }
        $productsCollection = $productGroup->products()->where("hidden", false)->orderBy("order")->orderBy("name")->get();
        if( !$productsCollection ) 
        {
            $productsCollection = array(  );
        }

        foreach( $productsCollection as $product ) 
        {
            $pricingInfo = getPricingInfo($product->id, $includeConfigOptions);
            $pricing->loadPricing("product", $product->id);
            $description = $this->formatProductDescription(Product\Product::getProductDescription($product->id, $product->description));
            if( $pricing->hasBillingCyclesAvailable() || $product->paymentType == "free" ) 
            {
                $unsortedProducts[$product->displayOrder][] = array( "pid" => $product->id, "bid" => 0, "type" => $product->type, "name" => Product\Product::getProductName($product->id, $product->name), "description" => $description["original"], "features" => $description["features"], "featuresdesc" => $description["featuresdesc"], "paytype" => $product->paymentType, "pricing" => $pricingInfo, "freedomain" => $product->freeDomain, "freedomainpaymentterms" => $product->freeDomainPaymentTerms, "qty" => ($product->stockControlEnabled ? $product->quantityInStock : ""), "isFeatured" => $product->isFeatured );
            }

        }
        if( $includeBundles ) 
        {
            foreach( \Illuminate\Database\Capsule\Manager::table("tblbundles")->where("showgroup", "1")->where("gid", $productGroup->id)->get() as $bundle ) 
            {
                $description = $this->formatProductDescription($bundle->description);
                $convertedCurrency = convertCurrency($bundle->displayprice, 1, $currency["id"]);
                $price = new View\Formatter\Price($convertedCurrency, $currency);
                $displayPrice = (0 < $bundle->displayprice ? $price : "");
                $displayPriceSimple = (0 < $bundle->displayprice ? $price->toPrefixed() : "");
                $unsortedProducts[$bundle->sortorder][] = array( "bid" => $bundle->id, "name" => $bundle->name, "description" => $description["original"], "features" => $description["features"], "featuresdesc" => $description["featuresdesc"], "displayprice" => $displayPrice, "displayPriceSimple" => $displayPriceSimple, "isFeatured" => (bool) $bundle->is_featured );
            }
        }

        if( empty($unsortedProducts) ) 
        {
            throw new Exception("NoProducts");
        }

        ksort($unsortedProducts);
        foreach( $unsortedProducts as $items ) 
        {
            foreach( $items as $item ) 
            {
                $products[] = $item;
            }
        }
        return $products;
    }

    public function formatProductDescription($desc)
    {
        $features = array(  );
        $featuresdesc = "";
        $descriptionlines = explode("\n", $desc);
        foreach( $descriptionlines as $line ) 
        {
            if( strpos($line, ":") ) 
            {
                $line = explode(":", $line, 2);
                $features[trim($line[0])] = trim($line[1]);
            }
            else
            {
                if( trim($line) ) 
                {
                    $featuresdesc .= $line . "\n";
                }

            }

        }
        return array( "original" => nl2br($desc), "features" => $features, "featuresdesc" => nl2br($featuresdesc) );
    }

    public function getProductGroupInfo($gid)
    {
        $result = select_query("tblproductgroups", "", array( "id" => $gid ));
        $data = mysql_fetch_assoc($result);
        if( !$data["id"] ) 
        {
            return false;
        }

        return $data;
    }

    public function setPid($pid)
    {
        $this->pid = $pid;
        $result = select_query("tblproducts", "tblproducts.id AS pid,tblproducts.gid,tblproducts.type,tblproducts.name AS name," . "tblproductgroups.id AS group_id,tblproductgroups.name as group_name,tblproducts.description," . "tblproducts.showdomainoptions,tblproducts.freedomain,tblproducts.freedomainpaymentterms," . "tblproducts.freedomaintlds,tblproducts.subdomain,tblproducts.stockcontrol,tblproducts.qty," . "tblproducts.allowqty,tblproducts.paytype,tblproductgroups.orderfrmtpl", array( "tblproducts.id" => $pid ), "", "", "", "tblproductgroups ON tblproductgroups.id=tblproducts.gid");
        $data = mysql_fetch_assoc($result);
        if( !$data["pid"] ) 
        {
            return false;
        }

        if( !$data["stockcontrol"] ) 
        {
            $data["qty"] = 0;
        }

        $data["name"] = Product\Product::getProductName($pid, $data["name"]);
        $data["description"] = $this->formatProductDescription(Product\Product::getProductDescription($pid, $data["description"]))["original"];
        $data["groupname"] = Product\Group::getGroupName($data["group_id"], $data["group_name"]);
        $this->productinfo = $data;
        return $this->productinfo;
    }

    public function getProductInfo($var = "")
    {
        return ($var ? $this->productinfo[$var] : $this->productinfo);
    }

    public function validateBillingCycle($billingcycle)
    {
        global $currency;
        if( empty($currency) ) 
        {
            $currency = getCurrency();
        }

        if( $billingcycle && in_array($billingcycle, $this->validbillingcycles) ) 
        {
            return $billingcycle;
        }

        $paytype = $this->productinfo["paytype"];
        $result = select_query("tblpricing", "", array( "type" => "product", "currency" => $currency["id"], "relid" => $this->productinfo["pid"] ));
        $data = mysql_fetch_array($result);
        $monthly = $data["monthly"];
        $quarterly = $data["quarterly"];
        $semiannually = $data["semiannually"];
        $annually = $data["annually"];
        $biennially = $data["biennially"];
        $triennially = $data["triennially"];
        if( $paytype == "free" ) 
        {
            $billingcycle = "free";
        }
        else
        {
            if( $paytype == "onetime" ) 
            {
                $billingcycle = "onetime";
            }
            else
            {
                if( $paytype == "recurring" ) 
                {
                    if( 0 <= $monthly ) 
                    {
                        $billingcycle = "monthly";
                    }
                    else
                    {
                        if( 0 <= $quarterly ) 
                        {
                            $billingcycle = "quarterly";
                        }
                        else
                        {
                            if( 0 <= $semiannually ) 
                            {
                                $billingcycle = "semiannually";
                            }
                            else
                            {
                                if( 0 <= $annually ) 
                                {
                                    $billingcycle = "annually";
                                }
                                else
                                {
                                    if( 0 <= $biennially ) 
                                    {
                                        $billingcycle = "biennially";
                                    }
                                    else
                                    {
                                        if( 0 <= $triennially ) 
                                        {
                                            $billingcycle = "triennially";
                                        }

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

        return $billingcycle;
    }

    public function getNumItemsInCart()
    {
        $numProducts = $this->getCartDataByKey("products", array(  ));
        foreach( $numProducts as $key => $product ) 
        {
            if( isset($product["noconfig"]) && $product["noconfig"] === true ) 
            {
                unset($numProducts[$key]);
            }

        }
        $numAddons = $this->getCartDataByKey("addons", array(  ));
        $numDomains = $this->getCartDataByKey("domains", array(  ));
        $numDomainRenewals = $this->getCartDataByKey("renewals", array(  ));
        $numUpgrades = $this->getCartDataByKey("upgrades", array(  ));
        return count($numProducts) + count($numAddons) + count($numDomains) + count($numDomainRenewals) + count($numUpgrades);
    }

    public static function addToCart($type, $parameters)
    {
        if( !in_array($type, array( "product", "addon", "upgrade" )) ) 
        {
            throw new Exception("Invalid product type.");
        }

        $cart = new self();
        $cartData = $cart->getCartData();
        $cartData[$type . "s"][] = $parameters;
        Session::set("cart", $cartData);
    }

    public static function addProductToCart($productId, $billingCycle, $domain)
    {
        self::addToCart("product", array( "pid" => $productId, "billingcycle" => $billingCycle, "domain" => $domain ));
    }

    public static function addAddonToCart($addonId, $serviceId, $billingCycle)
    {
        self::addToCart("addon", array( "id" => $addonId, "productid" => $serviceId, "billingcycle" => $billingCycle ));
    }

    public static function addUpgradeToCart($upgradeEntityType, $upgradeEntityId, $targetEntityId, $billingCycle)
    {
        self::addToCart("upgrade", array( "upgrade_entity_type" => $upgradeEntityType, "upgrade_entity_id" => $upgradeEntityId, "target_entity_id" => $targetEntityId, "billing_cycle" => $billingCycle ));
    }

}


