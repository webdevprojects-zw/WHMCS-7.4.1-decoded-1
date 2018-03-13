<?php 
namespace WHMCS\MarketConnect;


class Service extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblmarketconnect_services";
    protected $booleans = array( "status" );
    protected $casts = array( "settings" => "array" );
    protected $commaSeparated = array( "productIds" );
    protected $fillable = array( "name" );
    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where("status", 1);
    }

    public function setting($key)
    {
        $settings = $this->settings;
        $parts = explode(".", $key);
        foreach( $parts as $part ) 
        {
            $settings = (isset($settings[$part]) ? $settings[$part] : null);
        }
        return $settings;
    }

    public function factoryPromoter()
    {
        $key = strtolower($this->name);
        $className = "WHMCS\\MarketConnect\\Promotion\\Service\\" . MarketConnect::getClassByService($key);
        return new $className();
    }

    public static function getAutoAssignableAddons()
    {
        $mcServices = self::active()->get()->filter(function($mcService)
{
    return $mcService->setting("general.auto-assign-addons");
}

);
        $addons = array(  );
        foreach( $mcServices as $mcService ) 
        {
            $addonModuleConfigs = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->whereIn("value", $mcService->productIds)->get();
            foreach( $addonModuleConfigs as $addonModuleConfig ) 
            {
                if( $addonModuleConfig->productAddon ) 
                {
                    $addons[$addonModuleConfig->productAddon->id] = $addonModuleConfig->productAddon;
                }

            }
        }
        return $addons;
    }

}


