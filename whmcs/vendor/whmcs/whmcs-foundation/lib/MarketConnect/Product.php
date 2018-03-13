<?php 
namespace WHMCS\MarketConnect;


class Product extends \WHMCS\Product\Product
{
    public function __construct()
    {
        parent::__construct();
    }

    public function scopeMarketConnect(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("servertype", "marketconnect");
    }

    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where(function($query)
{
    $query->where("hidden", "0")->orWhere("hidden", "");
}

);
    }

    public function scopeSsl(\Illuminate\Database\Eloquent\Builder $query)
    {
        $query = $this->scopeMarketConnect($query);
        return $query->where(function($query)
{
    $query->where("configoption1", "like", "rapidssl_%")->orWhere("configoption1", "like", "geotrust_%")->orWhere("configoption1", "like", "symantec_%");
}

);
    }

    public function scopeSymantec(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $this->scopeSsl($query);
    }

    public function scopeWeebly(\Illuminate\Database\Eloquent\Builder $query)
    {
        $query = $this->scopeMarketConnect($query);
        return $query->where("configoption1", "like", "weebly_%");
    }

    public function scopeSpamexperts(\Illuminate\Database\Eloquent\Builder $query)
    {
        $query = $this->scopeMarketConnect($query);
        return $query->where("configoption1", "like", "spamexperts_%");
    }

    public function scopeProductKey($query, $productKey)
    {
        $query = $this->scopeMarketConnect($query);
        return $query->where("configoption1", $productKey);
    }

    public function getProductKeyAttribute($value)
    {
        return $this->moduleConfigOption1;
    }

}


