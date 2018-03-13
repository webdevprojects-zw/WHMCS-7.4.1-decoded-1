<?php 
namespace WHMCS\Billing;


class Quote extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblquotes";
    public $timestamps = false;
    protected $columnMap = array( "status" => "stage", "validUntilDate" => "validuntil", "clientId" => "userid", "lastModifiedDate" => "lastmodified" );
    protected $dates = array( "validuntil", "datecreated", "lastmodified", "datesent", "dateaccepted" );

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function items()
    {
        return $this->hasMany("WHMCS\\Billing\\Quote\\Item", "quoteid");
    }

}


