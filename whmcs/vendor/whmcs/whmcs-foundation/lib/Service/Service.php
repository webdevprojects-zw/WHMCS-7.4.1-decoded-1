<?php 
namespace WHMCS\Service;


class Service extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblhosting";
    protected $columnMap = array( "clientId" => "userid", "serverId" => "server", "registrationDate" => "regdate", "paymentGateway" => "paymentmethod", "status" => "domainstatus", "promotionId" => "promoid", "overrideAutoSuspend" => "overideautosuspend", "overrideSuspendUntilDate" => "overidesuspenduntil", "bandwidthUsage" => "bwusage", "bandwidthLimit" => "bwlimit", "lastUpdateDate" => "lastupdate", "recurringFee" => "amount" );
    protected $dates = array( "registrationDate", "nextDueDate", "nextInvoiceDate", "terminationDate", "completedDate", "overrideSuspendUntilDate", "lastUpdateDate" );
    protected $booleans = array( "overideautosuspend" );
    protected $appends = array( "serviceProperties" );

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function product()
    {
        return $this->belongsTo("WHMCS\\Product\\Product", "packageid");
    }

    public function addons()
    {
        return $this->hasMany("WHMCS\\Service\\Addon", "hostingid");
    }

    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "orderid");
    }

    public function cancellationRequests()
    {
        return $this->hasMany("WHMCS\\Service\\CancellationRequest", "relid");
    }

    public function ssl()
    {
        return $this->hasMany("WHMCS\\Service\\Ssl", "serviceid")->where("addon_id", "=", 0);
    }

    public function hasAvailableUpgrades()
    {
        return 0 < $this->product->upgradeProducts->count();
    }

    public function failedActions()
    {
        return $this->hasMany("WHMCS\\Module\\Queue", "service_id")->where("service_type", "=", "service");
    }

    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "relid");
    }

    public function getServicePropertiesAttribute()
    {
        return new Properties($this);
    }

}


