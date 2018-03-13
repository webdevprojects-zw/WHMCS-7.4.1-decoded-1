<?php 
namespace WHMCS\Product;


class Product extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblproducts";
    protected $columnMap = array( "productGroupId" => "gid", "isHidden" => "hidden", "welcomeEmailTemplateId" => "welcomeemail", "stockControlEnabled" => "stockcontrol", "quantityInStock" => "qty", "proRataChargeDayOfCurrentMonth" => "proratadate", "proRataChargeNextMonthAfterDay" => "proratachargenextmonth", "paymentType" => "paytype", "allowMultipleQuantities" => "allowqty", "freeSubDomains" => "subdomain", "module" => "servertype", "serverGroupId" => "servergroup", "moduleConfigOption1" => "configoption1", "moduleConfigOption2" => "configoption2", "moduleConfigOption3" => "configoption3", "moduleConfigOption4" => "configoption4", "moduleConfigOption5" => "configoption5", "moduleConfigOption6" => "configoption6", "moduleConfigOption7" => "configoption7", "moduleConfigOption8" => "configoption8", "moduleConfigOption9" => "configoption9", "moduleConfigOption10" => "configoption10", "moduleConfigOption11" => "configoption11", "moduleConfigOption12" => "configoption12", "moduleConfigOption13" => "configoption13", "moduleConfigOption14" => "configoption14", "moduleConfigOption15" => "configoption15", "moduleConfigOption16" => "configoption16", "moduleConfigOption17" => "configoption17", "moduleConfigOption18" => "configoption18", "moduleConfigOption19" => "configoption19", "moduleConfigOption20" => "configoption20", "moduleConfigOption21" => "configoption21", "moduleConfigOption22" => "configoption22", "moduleConfigOption23" => "configoption23", "moduleConfigOption24" => "configoption24", "recurringCycleLimit" => "recurringcycles", "daysAfterSignUpUntilAutoTermination" => "autoterminatedays", "autoTerminationEmailTemplateId" => "autoterminateemail", "allowConfigOptionUpgradeDowngrade" => "configoptionsupgrade", "upgradeEmailTemplateId" => "upgradeemail", "enableOverageBillingAndUnits" => "overagesenabled", "overageDiskLimit" => "overagesdisklimit", "overageBandwidthLimit" => "overagesbwlimit", "overageDiskPrice" => "overagesdiskprice", "overageBandwidthPrice" => "overagesbwprice", "applyTax" => "tax", "affiliatePayoutOnceOnly" => "affiliateonetime", "affiliatePaymentType" => "affiliatepaytype", "affiliatePaymentAmount" => "affiliatepayamount", "isRetired" => "retired", "displayOrder" => "order" );
    protected $booleans = array( "isHidden", "showDomainOptions", "stockControlEnabled", "proRataBilling", "allowConfigOptionUpgradeDowngrade", "applyTax", "affiliatePayoutOnceOnly", "isRetired", "isFeatured" );
    protected $strings = array( "description", "autoSetup", "module", "moduleConfigOption1", "moduleConfigOption2", "moduleConfigOption3", "moduleConfigOption4", "moduleConfigOption5", "moduleConfigOption6", "moduleConfigOption7", "moduleConfigOption8", "moduleConfigOption9", "moduleConfigOption10", "moduleConfigOption11", "moduleConfigOption12", "moduleConfigOption13", "moduleConfigOption14", "moduleConfigOption15", "moduleConfigOption16", "moduleConfigOption17", "moduleConfigOption18", "moduleConfigOption19", "moduleConfigOption20", "moduleConfigOption21", "moduleConfigOption22", "moduleConfigOption23", "moduleConfigOption24" );
    protected $ints = array( "welcomeEmailTemplateId", "quantityInStock", "proRataChargeDayOfCurrentMonth", "proRataChargeNextMonthAfterDay", "serverGroupId", "displayOrder" );
    protected $commaSeparated = array( "freeSubDomains", "freeDomainPaymentTerms", "freeDomainTlds", "enableOverageBillingAndUnits" );

    public static function boot()
    {
        parent::boot();
        Product::saved(function(Product $product)
{
    if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
    {
        $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array( "related_type" => "product.{id}.description", "related_id" => $product->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "textarea" ));
        $translation->translation = ($product->getRawAttribute("description") ?: "");
        $translation->save();
        $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array( "related_type" => "product.{id}.name", "related_id" => $product->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text" ));
        $translation->translation = ($product->getRawAttribute("name") ?: "");
        $translation->save();
    }

}

);
        Product::deleted(function(Product $product)
{
    if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
    {
        \WHMCS\Language\DynamicTranslation::whereIn("related_type", array( "product.{id}.description", "product.{id}.name" ))->where("related_id", "=", $product->id)->delete();
    }

}

);
        Product::created(function(Product $product)
{
    $product->assignMatchingMarketConnectAddons(\WHMCS\MarketConnect\Service::getAutoAssignableAddons());
}

);
    }

    public function productGroup()
    {
        return $this->belongsTo("WHMCS\\Product\\Group", "gid");
    }

    public function welcomeEmailTemplate()
    {
        return $this->hasOne("WHMCS\\Mail\\Template", "id", "welcomeemail");
    }

    public function autoTerminationEmailTemplate()
    {
        return $this->hasOne("WHMCS\\Mail\\Template", "id", "autoterminateemail");
    }

    public function upgradeEmailTemplate()
    {
        return $this->hasOne("WHMCS\\Mail\\Template", "id", "upgradeemail");
    }

    public function productDownloads()
    {
        return $this->belongsToMany("WHMCS\\Download\\Download", "tblproduct_downloads");
    }

    public function upgradeProducts()
    {
        return $this->belongsToMany("WHMCS\\Product\\Product", "tblproduct_upgrade_products", "product_id", "upgrade_product_id");
    }

    public function services()
    {
        return $this->hasMany("WHMCS\\Service\\Service", "packageid");
    }

    public function customFields()
    {
        return $this->hasMany("WHMCS\\CustomField", "relid")->where("type", "=", "product")->orderBy("sortorder");
    }

    public function scopeSorted($query)
    {
        return $query->orderBy("order");
    }

    public function getDownloadIds()
    {
        return array_map(function($download)
{
    return $download["id"];
}

, $this->productDownloads->toArray());
    }

    public function getUpgradeProductIds()
    {
        return array_map(function($product)
{
    return $product["id"];
}

, $this->upgradeProducts->toArray());
    }

    public function getAvailableBillingCycles()
    {
        switch( $this->paymentType ) 
        {
            case "free":
                return array( "free" );
            case "onetime":
                return array( "onetime" );
            case "recurring":
                $validCycles = array(  );
                $productPricing = new \WHMCS\Pricing();
                $productPricing->loadPricing("product", $this->id);
                return $productPricing->getAvailableBillingCycles();
        }
        return array(  );
    }

    public function pricing($currency = NULL)
    {
        if( is_null($this->pricingCache) ) 
        {
            $this->pricingCache = new Pricing($this, $currency);
        }

        return $this->pricingCache;
    }

    public function getNameAttribute($name)
    {
        $translatedName = "";
        if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
        {
            $translatedName = \Lang::trans("product." . $this->id . ".name", array(  ), "dynamicMessages");
        }

        return (strlen($translatedName) && $translatedName != "product." . $this->id . ".name" ? $translatedName : $name);
    }

    public function getDescriptionAttribute($description)
    {
        $translatedDescription = "";
        if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
        {
            $translatedDescription = \Lang::trans("product." . $this->id . ".description", array(  ), "dynamicMessages");
        }

        return (strlen($translatedDescription) && $translatedDescription != "product." . $this->id . ".description" ? $translatedDescription : $description);
    }

    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product.{id}.name")->select(array( "language", "translation" ));
    }

    public function translatedDescriptions()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product.{id}.description")->select(array( "language", "translation" ));
    }

    public static function getProductName($productId, $fallback = "", $language = NULL)
    {
        $name = \Lang::trans("product." . $productId . ".name", array(  ), "dynamicMessages", $language);
        if( $name == "product." . $productId . ".name" ) 
        {
            if( $fallback ) 
            {
                return $fallback;
            }

            return Product::find($productId, array( "name" ))->name;
        }

        return $name;
    }

    public static function getProductDescription($productId, $fallback = "", $language = NULL)
    {
        $description = \Lang::trans("product." . $productId . ".description", array(  ), "dynamicMessages", $language);
        if( $description == "product." . $productId . ".description" ) 
        {
            if( $fallback ) 
            {
                return $fallback;
            }

            return Product::find($productId, array( "description" ))->description;
        }

        return $description;
    }

    public function assignMatchingMarketConnectAddons(array $addons)
    {
        if( !$this->exists ) 
        {
            throw new \WHMCS\Exception("Product must be saved before being auto-assigned");
        }

        foreach( $addons as $addon ) 
        {
            $myself = self::where("id", "=", $this->id);
            foreach( $addon->autoLinkCriteria as $field => $value ) 
            {
                if( is_array($value) ) 
                {
                    $myself->whereIn($field, $value);
                }
                else
                {
                    $myself->where($field, $value);
                }

            }
            if( 0 < $myself->count() ) 
            {
                if( !in_array($this->id, $addon->packages) ) 
                {
                    $addon->packages = array_merge($addon->packages, array( $this->id ));
                }

                $addon->save();
            }

        }
    }

}


