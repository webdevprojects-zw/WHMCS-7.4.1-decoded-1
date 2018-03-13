<?php 
namespace WHMCS\Billing\Invoice;


class Item extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblinvoiceitems";
    public $timestamps = false;
    protected $booleans = array( "taxed" );
    protected $dates = array( "dueDate" );
    protected $columnMap = array( "relatedEntityId" => "relid" );

    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice", "invoiceid");
    }

}


