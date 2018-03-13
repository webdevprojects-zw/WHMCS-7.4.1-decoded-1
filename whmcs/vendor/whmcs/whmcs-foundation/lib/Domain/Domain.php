<?php 
namespace WHMCS\Domain;


class Domain extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbldomains";
    protected $dates = array( "registrationdate", "expirydate", "nextduedate", "nextinvoicedate" );
    protected $columnMap = array( "clientId" => "userid", "registrarModuleName" => "registrar", "promotionId" => "promoid", "paymentGateway" => "paymentmethod", "hasDnsManagement" => "dnsmanagement", "hasEmailForwarding" => "emailforwarding", "hasIdProtection" => "idprotection", "hasAutoInvoiceOnNextDueDisabled" => "donotrenew", "isSyncedWithRegistrar" => "synced" );
    protected $booleans = array( "hasDnsManagement", "hasEmailForwarding", "hasIdProtection", "isPremium", "hasAutoInvoiceOnNextDueDisabled", "isSyncedWithRegistrar" );
    protected $characterSeparated = array( "|" => array( "reminders" ) );
    protected $appends = array( "tld" );

    public function getTldAttribute()
    {
        $domainParts = explode(".", $this->domain, 2);
        return (isset($domainParts[1]) ? $domainParts[1] : "");
    }

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function additionalFields()
    {
        return $this->hasMany("WHMCS\\Domain\\AdditionalField", "domainid");
    }

    public function extra()
    {
        return $this->hasMany("WHMCS\\Domain\\Extra", "domain_id");
    }

    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "orderid");
    }

    public function setRemindersAttribute($reminders)
    {
        $remindersArray = $this->asArrayFromCharacterSeparatedValue($reminders, "|");
        if( 5 < count($remindersArray) ) 
        {
            throw new \WHMCS\Exception("You may only store the past 5 domain reminders.");
        }

        foreach( $remindersArray as $reminder ) 
        {
            if( !is_numeric($reminder) ) 
            {
                throw new \WHMCS\Exception("Domain reminders must be numeric.");
            }

        }
        $this->attributes["reminders"] = $reminders;
    }

    public function scopeNextDueBefore(\Illuminate\Database\Eloquent\Builder $query, \Carbon\Carbon $date)
    {
        return $query->whereStatus("Active")->where("nextduedate", "<=", $date);
    }

    public function failedActions()
    {
        return $this->hasMany("WHMCS\\Module\\Queue", "service_id")->where("service_type", "=", "domain");
    }

}


