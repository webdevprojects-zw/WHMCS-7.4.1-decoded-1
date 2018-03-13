<?php 
namespace WHMCS\User\Client;


class Affiliate extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaffiliates";
    protected $columnMap = array( "visitorCount" => "visitors", "commissionType" => "paytype", "paymentAmount" => "payamount", "isPaidOneTimeCommission" => "onetime", "amountWithdrawn" => "withdrawn" );
    protected $dates = array( "date" );

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "clientid");
    }

}


