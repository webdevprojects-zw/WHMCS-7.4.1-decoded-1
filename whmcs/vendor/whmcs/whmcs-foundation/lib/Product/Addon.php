<?php 
namespace WHMCS\Product;


class Addon extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladdons";
    protected $columnMap = array( "applyTax" => "tax", "showOnOrderForm" => "showorder", "welcomeEmailTemplateId" => "welcomeemail", "autoLinkCriteria" => "autolinkby" );
    protected $booleans = array( "applyTax", "showOnOrderForm", "suspendProduct" );
    protected $commaSeparated = array( "packages", "downloads" );
    protected $casts = array( "autolinkby" => "array" );

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("ordered", function(\Illuminate\Database\Eloquent\Builder $builder)
{
    $builder->orderBy("weight")->orderBy("name");
}

);
        Addon::saved(function(Addon $addon)
{
    if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
    {
        $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array( "related_type" => "product_addon.{id}.description", "related_id" => $addon->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "textarea" ));
        $translation->translation = ($addon->getRawAttribute("description") ?: "");
        $translation->save();
        $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array( "related_type" => "product_addon.{id}.name", "related_id" => $addon->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text" ));
        $translation->translation = ($addon->getRawAttribute("name") ?: "");
        $translation->save();
    }

}

);
        Addon::deleted(function(Addon $addon)
{
    if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
    {
        \WHMCS\Language\DynamicTranslation::whereIn("related_type", array( "product_addon.{id}.description", "product_addon.{id}.name" ))->where("related_id", "=", $addon->id)->delete();
    }

}

);
    }

    public function scopeShowOnOrderForm(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("showorder", "=", 1);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy("weight");
    }

    public function welcomeEmailTemplate()
    {
        return $this->hasOne("WHMCS\\Mail\\Template", "id", "welcomeemail");
    }

    public function getNameAttribute($name)
    {
        $translatedName = "";
        if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
        {
            $translatedName = \Lang::trans("product_addon." . $this->id . ".name", array(  ), "dynamicMessages");
        }

        return (strlen($translatedName) && $translatedName != "product_addon." . $this->id . ".name" ? $translatedName : $name);
    }

    public function getDescriptionAttribute($description)
    {
        $translatedDescription = "";
        if( \WHMCS\Config\Setting::getValue("EnableTranslations") ) 
        {
            $translatedDescription = \Lang::trans("product_addon." . $this->id . ".description", array(  ), "dynamicMessages");
        }

        return (strlen($translatedDescription) && $translatedDescription != "product_addon." . $this->id . ".description" ? $translatedDescription : $description);
    }

    public function customFields()
    {
        return $this->hasMany("WHMCS\\CustomField", "relid")->where("type", "=", "addon")->orderBy("sortorder");
    }

    public function serviceAddons()
    {
        return $this->hasMany("WHMCS\\Service\\Addon", "addonid");
    }

    public function moduleConfiguration()
    {
        return $this->hasMany("WHMCS\\Config\\Module\\ModuleConfiguration", "entity_id")->where("entity_type", "=", "addon");
    }

    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_addon.{id}.name")->select(array( "language", "translation" ));
    }

    public function translatedDescriptions()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_addon.{id}.description")->select(array( "language", "translation" ));
    }

    public static function getAddonName($addonId, $fallback = "", $language = NULL)
    {
        $name = \Lang::trans("product_addon." . $addonId . ".name", array(  ), "dynamicMessages", $language);
        if( $name == "product_addon." . $addonId . ".name" ) 
        {
            if( $fallback ) 
            {
                return $fallback;
            }

            return Addon::find($addonId, array( "name" ))->name;
        }

        return $name;
    }

    public static function getAddonDescription($addonId, $fallback = "", $language = NULL)
    {
        $description = \Lang::trans("product_addon." . $addonId . ".description", array(  ), "dynamicMessages", $language);
        if( $description == "product_addon." . $addonId . ".description" ) 
        {
            if( $fallback ) 
            {
                return $fallback;
            }

            return Product::find($addonId, array( "description" ))->description;
        }

        return $description;
    }

    public function pricing($currency)
    {
        if( is_null($this->pricingCache) ) 
        {
            $this->pricingCache = new Pricing($this, $currency);
        }

        return $this->pricingCache;
    }

}


