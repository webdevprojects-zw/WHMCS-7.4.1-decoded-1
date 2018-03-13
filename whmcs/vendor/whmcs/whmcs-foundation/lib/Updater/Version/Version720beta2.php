<?php 
namespace WHMCS\Updater\Version;


class Version720beta2 extends IncrementalVersion
{
    protected $updateActions = array( "addPaymentReversalChangeSettings" );

    protected function addPaymentReversalChangeSettings()
    {
        \WHMCS\Config\Setting::setValue("ReversalChangeInvoiceStatus", 1);
        \WHMCS\Config\Setting::setValue("ReversalChangeDueDates", 1);
        return $this;
    }

}


