<?php 
namespace WHMCS\Cron\Task;


class AutoTerminations extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1590;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Overdue Terminations";
    protected $defaultName = "Overdue Terminations";
    protected $systemName = "AutoTerminations";
    protected $outputs = array( "terminations" => array( "defaultValue" => 0, "identifier" => "terminations", "name" => "Terminations" ), "manual" => array( "defaultValue" => 0, "identifier" => "manual", "name" => "Manual Termination Required" ) );
    protected $icon = "fa-calendar-times-o";
    protected $successCountIdentifier = "terminations";
    protected $failureCountIdentifier = "manual";
    protected $successKeyword = "Terminated";

    public function __invoke()
    {
        if( !\WHMCS\Config\Setting::getValue("AutoTermination") ) 
        {
            return $this;
        }

        $processedTerminations = 0;
        $manualTerminationRequired = 0;
        $terminatedate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - \WHMCS\Config\Setting::getValue("AutoTerminationDays"), date("Y")));
        $query = "SELECT * FROM tblhosting" . " WHERE (domainstatus='Active' OR domainstatus='Suspended')" . " AND billingcycle!='Free Account'" . " AND billingcycle!='One Time'" . " AND nextduedate<='" . $terminatedate . "'" . " AND tblhosting.nextduedate != '0000-00-00'" . " AND overideautosuspend!='1'" . " ORDER BY domain ASC";
        $result = full_query($query);
        while( $data = mysql_fetch_array($result) ) 
        {
            $serviceid = $data["id"];
            $userid = $data["userid"];
            $domain = $data["domain"];
            $packageid = $data["packageid"];
            $result2 = select_query("tblclients", "", array( "id" => $userid ));
            $data2 = mysql_fetch_array($result2);
            $firstname = $data2["firstname"];
            $lastname = $data2["lastname"];
            $groupid = $data2["groupid"];
            $result2 = select_query("tblproducts", "name,servertype", array( "id" => $packageid ));
            $data2 = mysql_fetch_array($result2);
            $prodname = $data2["name"];
            $module = $data2["servertype"];
            $susptermexempt = 0;
            if( $groupid ) 
            {
                $result2 = select_query("tblclientgroups", "susptermexempt", array( "id" => $groupid ));
                $data2 = mysql_fetch_array($result2);
                $susptermexempt = $data2["susptermexempt"];
            }

            if( !$susptermexempt ) 
            {
                $serverresult = "No Module";
                logActivity("Cron Job: Terminating Service - Service ID: " . $serviceid);
                if( $module ) 
                {
                    $serverresult = ServerTerminateAccount($serviceid);
                }

                if( $domain ) 
                {
                    $domain = " - " . $domain;
                }

                $loginfo = sprintf("%s%s - %s %s (Service ID: %s - User ID: %s)", $prodname, $domain, $firstname, $lastname, $serviceid, $userid);
                if( $serverresult == "success" ) 
                {
                    $processedTerminations++;
                }
                else
                {
                    $manualTerminationRequired++;
                    logActivity(sprintf("ERROR: Manual Terminate Required - %s - %s", $serverresult, $loginfo));
                }

            }

        }
        $addons = \WHMCS\Service\Addon::whereHas("service", function($query)
{
    $query->where("overideautosuspend", "!=", 1);
}

)->with("client", "productAddon", "service")->whereIn("status", array( "Active", "Terminated" ))->whereNotIn("billingcycle", array( "Free", "Free Account", "One Time" ))->where("nextduedate", "<=", $terminatedate)->where("nextduedate", "!=", "0000-00-00")->get();
        foreach( $addons as $addon ) 
        {
            if( !$addon->service ) 
            {
                continue;
            }

            if( $addon->productAddon->module ) 
            {
                $automation = \WHMCS\Service\Automation\AddonAutomation::factory($addon);
                $automationResult = $automation->runAction("TerminateAccount");
                if( $automationResult ) 
                {
                    $processedTerminations++;
                }
                else
                {
                    $manualTerminationRequired++;
                    $logInfo = sprintf("%s - %s %s (Service ID: %d - Addon ID: %d - User ID: %d)", ($addon->name ? $addon->name : $addon->productAddon->name), $addon->client->firstName, $addon->client->lastName, $addon->serviceId, $addon->id, $addon->clientId);
                    logActivity(sprintf("ERROR: Manual Terminate Required - %s - %s", $automation->getError(), $logInfo));
                }

            }
            else
            {
                $addon->status = "Terminated";
                $addon->save();
                run_hook("AddonTerminated", array( "id" => $addon->id, "userid" => $addon->clientId, "serviceid" => $addon->serviceId, "addonid" => $addon->addonId ));
            }

        }
        $this->output("terminations")->write($processedTerminations);
        $this->output("manual")->write($manualTerminationRequired);
        return true;
    }

}


