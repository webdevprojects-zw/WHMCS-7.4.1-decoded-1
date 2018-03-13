<?php 
namespace WHMCS\Updater\Version;


class Version601release1 extends IncrementalVersion
{
    protected $updateActions = array( "migrateFixedInvoiceDataAddon" );

    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . "fixed_invoice_data";
    }

    protected function migrateFixedInvoiceDataAddon()
    {
        $fixedInvoiceDataSettings = \Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", "fixed_invoice_data")->count();
        if( 0 < $fixedInvoiceDataSettings ) 
        {
            \WHMCS\Config\Setting::setValue("StoreClientDataSnapshotOnInvoiceCreation", "on");
            $fixedInvoiceDataSettings = \Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", "fixed_invoice_data")->delete();
        }

        return $this;
    }

}


