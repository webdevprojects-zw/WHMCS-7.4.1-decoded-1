<?php 
namespace WHMCS\Updater\Version;


class Version630rc1 extends IncrementalVersion
{
    protected $updateActions = array( "insertUpgradeTimeForMDE" );

    public function insertUpgradeTimeForMDE()
    {
        \WHMCS\Config\Setting::setValue("MDEFromTime", \Carbon\Carbon::now());
        return $this;
    }

}


