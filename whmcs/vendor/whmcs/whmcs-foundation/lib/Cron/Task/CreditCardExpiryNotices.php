<?php 
namespace WHMCS\Cron\Task;


class CreditCardExpiryNotices extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1650;
    protected $defaultFrequency = 43200;
    protected $defaultDescription = "Sending Credit Card Expiry Reminders";
    protected $defaultName = "Credit Card Expiry Notices";
    protected $systemName = "CreditCardExpiryNotices";
    protected $outputs = array( "notices" => array( "defaultValue" => 0, "identifier" => "notices", "name" => "Credit Card Expiry Notices" ) );
    protected $icon = "fa-credit-card-alt";
    protected $isBooleanStatus = false;
    protected $successCountIdentifier = "notices";
    protected $successKeyword = "Sent";

    public function monthlyDayOfExecution()
    {
        $dayForNotices = (int) \WHMCS\Config\Setting::getValue("CCDaySendExpiryNotices");
        $daysInThisMonth = \Carbon\Carbon::now()->daysInMonth;
        if( $daysInThisMonth < $dayForNotices ) 
        {
            $dayForNotices = $daysInThisMonth;
        }

        return \Carbon\Carbon::now()->startOfDay()->day($dayForNotices);
    }

    public function anticipatedNextRun(\Carbon\Carbon $date = NULL)
    {
        $correctDayDate = $this->anticipatedNextMonthlyRun((int) \WHMCS\Config\Setting::getValue("CCDaySendExpiryNotices"), $date);
        if( $date ) 
        {
            $correctDayDate->hour($date->format("H"))->minute($date->format("i"));
        }

        return $correctDayDate;
    }

    public function __invoke()
    {
        $whmcs = \DI::make("app");
        $cc_encryption_hash = $whmcs->getApplicationConfig()->cc_encryption_hash;
        $expiryEmailCount = 0;
        $expirymonth = date("my", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $result = select_query("tblclients", "id", "cardtype!='' AND status='Active'");
        while( $data = mysql_fetch_array($result) ) 
        {
            $userid = $data["id"];
            $cchash = md5($cc_encryption_hash . $userid);
            $result2 = select_query("tblclients", "id", "id='" . $userid . "' AND AES_DECRYPT(expdate,'" . $cchash . "')='" . $expirymonth . "'");
            $data = mysql_fetch_array($result2);
            $userid = $data["id"];
            if( $userid ) 
            {
                sendMessage("Credit Card Expiring Soon", $userid);
                if( !\WHMCS\Config\Setting::getValue("CCDoNotRemoveOnExpiry") ) 
                {
                    update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "issuenumber" => "", "startdate" => "" ), array( "id" => $userid ));
                }

                $expiryEmailCount++;
            }

        }
        logActivity("Cron Job: Sent " . $expiryEmailCount . " Credit Card Expiry Notices");
        $this->output("notices")->write($expiryEmailCount);
        return $this;
    }

}


