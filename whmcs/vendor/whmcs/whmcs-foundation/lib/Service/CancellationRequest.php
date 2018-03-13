<?php 
namespace WHMCS\Service;


class CancellationRequest extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcancelrequests";
    protected $columnMap = array( "serviceId" => "relid", "whenToCancel" => "type" );
    protected $dates = array( "date" );

    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "relid");
    }

}


