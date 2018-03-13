<?php 
namespace WHMCS\Cron\Task;


class UpdateDomainExpiryStatus extends \WHMCS\Scheduling\Task\AbstractTask
{
    public $description = "Update Domain Expiry Status";
    protected $defaultPriority = 1690;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Update Domain Expiry Status";
    protected $defaultName = "Domain Expiry";
    protected $systemName = "UpdateDomainExpiryStatus";
    protected $outputs = array( "expired" => array( "defaultValue" => 0, "identifier" => "expired", "name" => "Active Domains Set to Expired" ) );
    protected $icon = "fa-link";
    protected $isBooleanStatus = false;
    protected $successCountIdentifier = "expired";
    protected $successKeyword = "Expired";

    public function __invoke()
    {
        $expiredDomains = \WHMCS\Domain\Domain::where("expirydate", "<", date("Y-m-d"))->where("expirydate", "!=", "00000000")->where("status", "=", "Active");
        $affectedDomains = $expiredDomains->count();
        $expiredDomains->update(array( "status" => "Expired" ));
        $this->output("expired")->write($affectedDomains);
        return $this;
    }

}


