<?php 
namespace WHMCS\Billing\Payment;


class Transaction extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaccounts";
    protected $dates = array( "date" );
    protected $columnMap = array( "clientId" => "userid", "currencyId" => "currency", "paymentGateway" => "gateway", "exchangeRate" => "rate", "transactionId" => "transid" );
    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice", "invoiceid");
    }

}


