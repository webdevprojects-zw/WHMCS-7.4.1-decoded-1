<?php 
namespace WHMCS\Service;


class Addon extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblhostingaddons";
    protected $columnMap = array( "serviceId" => "hostingid", "clientId" => "userid", "recurringFee" => "recurring", "registrationDate" => "regdate", "applyTax" => "tax", "terminationDate" => "termination_date", "paymentGateway" => "paymentmethod", "serverId" => "server" );
    protected $dates = array( "regDate", "nextdueDate", "nextinvoiceDate", "terminationDate" );
    protected $appends = array( "serviceProperties" );

    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "hostingid");
    }

    public function productAddon()
    {
        return $this->hasOne("WHMCS\\Product\\Addon", "id", "addonid");
    }

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "relid");
    }

    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "orderid");
    }

    public function getServicePropertiesAttribute()
    {
        return new Properties($this);
    }

    public function ssl()
    {
        return $this->hasMany("WHMCS\\Service\\Ssl");
    }

}


