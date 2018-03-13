<?php 
namespace WHMCS\Updater\Version;


class Version701release1 extends IncrementalVersion
{
    protected $updateActions = array( "removeAdminForceSSLSetting" );

    public function removeAdminForceSSLSetting()
    {
        \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "=", "AdminForceSSL")->delete();
        return $this;
    }

}


