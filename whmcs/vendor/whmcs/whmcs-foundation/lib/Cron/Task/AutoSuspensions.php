<?php 
namespace WHMCS\Cron\Task;


class AutoSuspensions extends \WHMCS\Scheduling\Task\AbstractTask
{
    public $description = "Processing Overdue Suspensions";
    protected $defaultPriority = 1580;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Overdue Suspensions";
    protected $defaultName = "Overdue Suspensions";
    protected $systemName = "AutoSuspensions";
    protected $outputs = array( "suspended" => array( "defaultValue" => 0, "identifier" => "unpaid", "name" => "Overdue Suspended" ), "manual" => array( "defaultValue" => 0, "identifier" => "manual", "name" => "Manual Suspension Required" ) );
    protected $icon = "fa-bell";
    protected $successCountIdentifier = "suspended";
    protected $failureCountIdentifier = "manual";
    protected $successKeyword = "Suspended";

    public function __invoke()
    {
        $successfulSuspensions = 0;
        $manualSuspensionRequired = 0;
        if( !\WHMCS\Config\Setting::getValue("AutoSuspension") ) 
        {
            return true;
        }

        update_query("tblhosting", array( "overideautosuspend" => "" ), "overideautosuspend='1' AND overidesuspenduntil<'" . date("Y-m-d") . "' AND overidesuspenduntil!='0000-00-00'");
        $i = 0;
        $suspenddate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - \WHMCS\Config\Setting::getValue("AutoSuspensionDays"), date("Y")));
        $query3 = "SELECT * FROM tblhosting" . " WHERE domainstatus = 'Active'" . " AND billingcycle != 'Free Account'" . " AND billingcycle != 'Free'" . " AND billingcycle != 'One Time'" . " AND overideautosuspend != '1'" . " AND nextduedate <= '" . $suspenddate . "'" . " ORDER BY domain ASC";
        $result3 = full_query($query3);
        while( $data = mysql_fetch_array($result3) ) 
        {
            $id = $data["id"];
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
                logActivity("Cron Job: Suspending Service - Service ID: " . $id);
                if( $module ) 
                {
                    $serverresult = ServerSuspendAccount($id);
                }

                if( $domain ) 
                {
                    $domain = " - " . $domain;
                }

                $loginfo = sprintf("%s%s - %s %s (Service ID: %s - User ID: %s)", $prodname, $domain, $firstname, $lastname, $id, $userid);
                if( $serverresult == "success" ) 
                {
                    sendMessage("Service Suspension Notification", $id);
                    $msg = "SUCCESS: " . $loginfo;
                    $successfulSuspensions++;
                    $i++;
                }
                else
                {
                    $msg = sprintf("ERROR: Manual Suspension Required - %s - %s", $serverresult, $loginfo);
                    $manualSuspensionRequired++;
                }

                logActivity("Cron Job: " . $msg);
            }

        }
        $addons = \WHMCS\Service\Addon::whereHas("service", function($query)
{
    $query->where("overideautosuspend", "!=", 1);
}

)->with("client", "productAddon", "service", "service.product")->where("status", "=", "Active")->whereNotIn("billingcycle", array( "Free", "Free Account", "One Time" ))->where("nextduedate", "<=", $suspenddate)->get();
        foreach( $addons as $addon ) 
        {
            if( !$addon->service ) 
            {
                continue;
            }

            $id = $addon->id;
            $serviceId = $addon->serviceId;
            $addonId = $addon->addonId;
            $name = $addon->name;
            $userId = $addon->clientId;
            $domain = $addon->service->domain;
            $firstName = $addon->client->firstName;
            $lastName = $addon->client->lastName;
            $groupId = $addon->client->groupId;
            if( $groupId ) 
            {
                $suspendTerminateExempt = get_query_val("tblclientgroups", "susptermexempt", array( "id" => $groupId ));
                if( $suspendTerminateExempt ) 
                {
                    continue;
                }

            }

            if( !$name && $addonId ) 
            {
                $name = $addon->productAddon->name;
            }

            $noModule = true;
            $automationResult = false;
            $automation = null;
            if( $addon->productAddon->module ) 
            {
                $automation = \WHMCS\Service\Automation\AddonAutomation::factory($addon);
                $automationResult = $automation->runAction("SuspendAccount", "");
                $noModule = false;
            }
            else
            {
                $addon->status = "Suspended";
                $addon->save();
            }

            if( $noModule || $automationResult ) 
            {
                $logInfo = sprintf("%s - %s %s (Service ID: %d - Addon ID: %d)", $name, $firstName, $lastName, $serviceId, $id);
                $msg = "SUCCESS: " . $logInfo;
                logActivity("Cron Job: " . $msg);
                $successfulSuspensions++;
                if( !$noModule ) 
                {
                    run_hook("AddonSuspended", array( "id" => $id, "userid" => $userId, "serviceid" => $serviceId, "addonid" => $addonId ));
                }

                if( $addonId && $addon->productAddon->suspendProduct ) 
                {
                    $productName = $addon->productAddon->name;
                    $module = $addon->productAddon->module;
                    $serverResult = "No Module";
                    logActivity("Cron Job: Suspending Parent Service - Service ID: " . $serviceId);
                    if( $module ) 
                    {
                        $serverResult = ServerSuspendAccount($serviceId, "Parent Service Suspended due to Overdue Addon");
                    }

                    if( $domain ) 
                    {
                        $domain = " - " . $domain;
                    }

                    $logInfo = sprintf("%s %s - %s%s (Service ID: %d - User ID: %d)", $firstName, $lastName, $productName, $domain, $serviceId, $userId);
                    if( $serverResult == "success" ) 
                    {
                        sendMessage("Service Suspension Notification", $serviceId);
                        $msg = "SUCCESS: " . $logInfo;
                        $successfulSuspensions++;
                    }
                    else
                    {
                        $msg = sprintf("ERROR: Manual Parent Service Suspension Required - %s - %s", $serverResult, $logInfo);
                        $manualSuspensionRequired++;
                    }

                    logActivity("Cron Job: " . $msg);
                }

            }

            $i++;
        }
        $this->output("suspended")->write($successfulSuspensions);
        $this->output("manual")->write($manualSuspensionRequired);
        return $this;
    }

}


