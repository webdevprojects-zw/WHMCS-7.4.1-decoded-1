<?php 
namespace WHMCS\Billing;


class Currency extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcurrencies";
    public $timestamps = false;

    public function scopeDefaultCurrency($query)
    {
        return $query->where("default", 1);
    }

}


