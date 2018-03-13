<?php 
namespace WHMCS\User\Client;


class AlertFactory
{
    protected $client = NULL;
    protected $alerts = array(  );

    public function __construct(\WHMCS\User\Client $client)
    {
        $this->client = $client;
    }

    public function build()
    {
        $this->checkForExpiringCreditCard()->checkForDomainsExpiringSoon()->checkForUnpaidInvoices()->checkForCreditBalance();
        $alerts = run_hook("ClientAlert", $this->client);
        foreach( $alerts as $response ) 
        {
            if( $response instanceof \WHMCS\User\Alert ) 
            {
                $this->addAlert($response);
            }

        }
        return new \Illuminate\Support\Collection($this->alerts);
    }

    protected function addAlert(\WHMCS\User\Alert $alert)
    {
        $this->alerts[] = $alert;
        return $this;
    }

    protected function checkForExpiringCreditCard()
    {
        if( $this->client->isCreditCardExpiring() ) 
        {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.creditCardExpiring", array( ":creditCardType" => $this->client->creditCardType, ":creditCardLastFourDigits" => $this->client->creditCardLastFourDigits, ":days" => 60 )), "warning", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=creditcard", \Lang::trans("clientareaupdatebutton")));
        }

        return $this;
    }

    protected function checkForDomainsExpiringSoon()
    {
        $domainsDueWithin7Days = $this->client->domains()->nextDueBefore(\Carbon\Carbon::now()->addDays(7))->count();
        if( 0 < $domainsDueWithin7Days ) 
        {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.domainsExpiringSoon", array( ":days" => 7, ":numberOfDomains" => $domainsDueWithin7Days )), "danger", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "cart.php?gid=renewals", \Lang::trans("domainsrenewnow")));
        }

        $domainsDueWithin30Days = $this->client->domains()->nextDueBefore(\Carbon\Carbon::now()->addDays(30))->count();
        $domainsDueWithin30Days -= $domainsDueWithin7Days;
        if( 0 < $domainsDueWithin30Days ) 
        {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.domainsExpiringSoon", array( ":days" => 30, ":numberOfDomains" => $domainsDueWithin30Days )), "info", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "cart.php?gid=renewals", \Lang::trans("domainsrenewnow")));
        }

        return $this;
    }

    protected function checkForUnpaidInvoices()
    {
        $clientId = $this->client->id;
        $unpaidInvoices = \WHMCS\Billing\Invoice::with("transactions")->whereUserid($clientId)->unpaid()->get();
        if( 0 < count($unpaidInvoices) ) 
        {
            $currency = getCurrency($clientId);
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.invoicesUnpaid", array( ":numberOfInvoices" => count($unpaidInvoices), ":balanceDue" => formatCurrency($unpaidInvoices->sum("balance")) )), "info", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=masspay&all=true", \Lang::trans("invoicespaynow")));
        }

        $overdueInvoices = \WHMCS\Billing\Invoice::with("transactions")->whereUserid($clientId)->overdue()->get();
        if( 0 < count($overdueInvoices) ) 
        {
            $currency = getCurrency($clientId);
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.invoicesOverdue", array( ":numberOfInvoices" => count($overdueInvoices), ":balanceDue" => formatCurrency($overdueInvoices->sum("balance")) )), "warning", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=masspay&all=true", \Lang::trans("invoicespaynow")));
        }

        return $this;
    }

    protected function checkForCreditBalance()
    {
        $creditBalance = $this->client->credit;
        if( 0 < $creditBalance ) 
        {
            $currency = getCurrency($this->client->id);
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.creditBalance", array( ":creditBalance" => formatCurrency($creditBalance) )), "success", "", ""));
        }

        return $this;
    }

}


