<?php 
namespace WHMCS\Updater\Version;


class Version610alpha1 extends IncrementalVersion
{
    protected $updateActions = array( "migrateMaxMindIgnoreCity", "moveAttachmentsProjectsFiles", "detectCronRunForHealthAndUpdates" );

    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "WHMCS" . DIRECTORY_SEPARATOR . "Http" . DIRECTORY_SEPARATOR . "Client";
        $config = \DI::make("config");
        $adminDir = $config::DEFAULT_ADMIN_FOLDER;
        if( $config->customadminpath ) 
        {
            $adminDir = $config->customadminpath;
        }

        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . $adminDir . DIRECTORY_SEPARATOR . "systemupdates.php";
    }

    protected function migrateMaxMindIgnoreCity()
    {
        $maxmindFraudSetting = \Illuminate\Database\Capsule\Manager::table("tblfraud")->where("fraud", "maxmind")->where("setting", "Do Not Include City")->count();
        if( 0 < $maxmindFraudSetting ) 
        {
            \Illuminate\Database\Capsule\Manager::table("tblfraud")->where("fraud", "maxmind")->where("setting", "Do Not Include City")->update(array( "setting" => "Do Not Validate Address Information" ));
        }

        return $this;
    }

    protected function moveAttachmentsProjectsFiles()
    {
        $config = \DI::make("config");
        $attachmentsDir = $config->getAbsoluteAttachmentsPath();
        $badProjectsAttachmentsDir = $attachmentsDir . "projects";
        $goodProjectsAttachmentsDir = $attachmentsDir . DIRECTORY_SEPARATOR . "projects";
        if( !is_dir($badProjectsAttachmentsDir) ) 
        {
            return $this;
        }

        if( !is_dir($goodProjectsAttachmentsDir) ) 
        {
            mkdir($goodProjectsAttachmentsDir);
        }

        try
        {
            \WHMCS\Utility\File::recursiveCopy($badProjectsAttachmentsDir, $goodProjectsAttachmentsDir);
        }
        catch( \Exception $e ) 
        {
            \Log::warn($e->getMessage());
        }
        return $this;
    }

    protected function detectCronRunForHealthAndUpdates()
    {
        $cronJobCompletedWithinLast24Hours = \Illuminate\Database\Capsule\Manager::table("tblactivitylog")->where("description", "Cron Job: Completed")->whereBetween("date", array( \Carbon\Carbon::now()->subDay()->format("Y-m-d H:i:s"), \Carbon\Carbon::now()->format("Y-m-d H:i:s") ))->count();
        if( 0 < $cronJobCompletedWithinLast24Hours ) 
        {
            \Illuminate\Database\Capsule\Manager::table("tbltransientdata")->insert(array( "name" => "cronComplete", "data" => true, "expires" => \Carbon\Carbon::now()->addDay()->timestamp ));
        }

        return $this;
    }

}


