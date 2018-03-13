<?php 
namespace WHMCS\CustomField;


class CustomFieldValue extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcustomfieldsvalues";
    protected $columnMap = array( "relatedId" => "relid" );
    protected $fillable = array( "fieldid", "relid" );

    public function customField()
    {
        return $this->belongsTo("WHMCS\\CustomField", "fieldid");
    }

    public function addon()
    {
        return $this->belongsTo("WHMCS\\Service\\Addon", "relid");
    }

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "relid");
    }

    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "relid");
    }

}


