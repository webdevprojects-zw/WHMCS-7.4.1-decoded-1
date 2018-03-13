<?php 
namespace WHMCS\Installer\Composer\Hooks;


class ComposerInstaller
{
    private $event = NULL;
    private $updateConfig = NULL;
    private $targetDir = NULL;
    private $sourceDir = NULL;
    private $suppressOutput = NULL;
    private $deleteSourceFiles = NULL;

    const PACKAGE_NAME = "whmcs/whmcs";
    const RELMODE_INSTALL = "install";
    const RELMODE_UPDATE = "update";
    const OUTPUT_INFO = "info";
    const OUTPUT_COMMENT = "comment";

    public function __construct(\Composer\Script\Event $event, $targetDir = ".", $vendorDir = NULL)
    {
        $this->event = $event;
        $this->setTargetDir($targetDir)->setDeleteSourceFiles(true);
        $this->updateConfig = new UpdateConfig();
        if( is_null($vendorDir) ) 
        {
            $vendorDir = $this->event->getComposer()->getConfig()->get("vendor-dir");
        }

        $this->sourceDir = $vendorDir . DIRECTORY_SEPARATOR . self::PACKAGE_NAME;
    }

    public function setTargetDir($value)
    {
        $this->targetDir = $value;
        if( !defined("ROOTDIR") ) 
        {
            define("ROOTDIR", $this->targetDir);
        }

        return $this;
    }

    public function getTargetDir()
    {
        return $this->targetDir;
    }

    public function setUpdateConfig(UpdateConfig $updateConfig)
    {
        $this->updateConfig = $updateConfig;
    }

    public function getUpdateConfig()
    {
        return $this->updateConfig;
    }

    public function setSuppressOutput($value)
    {
        $this->suppressOutput = $value;
    }

    public function getSuppressOutput()
    {
        return $this->suppressOutput;
    }

    public function setSourceDir($dir)
    {
        $this->sourceDir = $dir;
        return $this;
    }

    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    public function getDeleteSourceFiles()
    {
        return $this->deleteSourceFiles;
    }

    public function setDeleteSourceFiles($value)
    {
        $this->deleteSourceFiles = $value;
        return $this;
    }

    public function output($message, $type = "")
    {
        if( $this->suppressOutput ) 
        {
            return NULL;
        }

        if( $type ) 
        {
            $message = sprintf("<%s>%s</%s>", $type, $message, $type);
        }

        $this->event->getIO()->write($message);
    }

    public function loadConfigFile($existingConfigPath)
    {
        if( $this->updateConfig->configFileExists($existingConfigPath) && !$this->updateConfig->loadConfigFile($existingConfigPath) ) 
        {
            throw new \WHMCS\Exception("An existing installation file has been found at " . $existingConfigPath . ", but it could not be loaded. " . "Please verify the configuration file is valid and readable before proceeding.");
        }

    }

    protected function normalizeDirSettingPath($dir)
    {
        if( !preg_match("~^[\\/]~", $dir) ) 
        {
            $dir = $this->targetDir . DIRECTORY_SEPARATOR . $dir;
        }

        return $dir;
    }

    protected function isDirSettingValid($dir)
    {
        if( !empty($dir) ) 
        {
            return (is_dir($this->normalizeDirSettingPath($dir)) ? true : false);
        }

        return true;
    }

    public function findExistingInstallation()
    {
        if( !$this->updateConfig->isConfigFileLoaded() ) 
        {
            return false;
        }

        $dirsToCheck = array( "custom admin directory" => $this->updateConfig->getCustomAdminPath(), "custom attachments directory" => $this->updateConfig->getCustomAttachmentsDir(), "custom downloads directory" => $this->updateConfig->getCustomDownloadsDir(), "custom compiled templates directory" => $this->updateConfig->getCustomCompiledTemplatesDir(), "custom crons directory" => $this->updateConfig->getCustomCronsDir() );
        foreach( $dirsToCheck as $description => $dir ) 
        {
            if( !$this->isDirSettingValid($dir) ) 
            {
                throw new \WHMCS\Exception("A configuration file and/or existing installation has been found, but " . $description . " could not be located as configured (" . $dir . "). " . "Please verify your existing installation configuration before proceeding.");
            }

        }
        return true;
    }

    private function performEarlyFileCopy(array $files)
    {
        foreach( $files as $fileToCopy ) 
        {
            $destSubDir = dirname($fileToCopy);
            if( $destSubDir === "." ) 
            {
                $destSubDir = "";
            }

            if( $destSubDir ) 
            {
                $destDir = $this->targetDir . DIRECTORY_SEPARATOR . $destSubDir;
                if( !is_dir($destDir) ) 
                {
                    \WHMCS\Utility\File::recursiveMkDir($this->targetDir, $destSubDir);
                }

            }

            if( !@copy($this->sourceDir . DIRECTORY_SEPARATOR . $fileToCopy, $this->targetDir . DIRECTORY_SEPARATOR . $fileToCopy) ) 
            {
                throw new \WHMCS\Exception("Failed to perform early file copy during WHMCS file relocation: " . $fileToCopy);
            }

        }
    }

    public function relocateWhmcsCoreFiles($relocationMode)
    {
        $this->output("Preparing to relocate WHMCS core files...");
        $dirsWithCustomTargets = array(  );
        $excludeFromCopy = array(  );
        switch( $relocationMode ) 
        {
            case self::RELMODE_INSTALL:
                break;
            case self::RELMODE_UPDATE:
                $dirsWithCustomTargets = array( UpdateConfig::DEFAULT_ADMIN_FOLDER => $this->updateConfig->getCustomAdminPath(), UpdateConfig::DEFAULT_CRON_FOLDER => $this->updateConfig->getCustomCronsDir() );
                $dirsToSkip = array_merge($dirsWithCustomTargets, array( UpdateConfig::DEFAULT_ATTACHMENTS_FOLDER => $this->updateConfig->getCustomAttachmentsDir(), UpdateConfig::DEFAULT_COMPILED_TEMPLATES_FOLDER => $this->updateConfig->getCustomCompiledTemplatesDir(), UpdateConfig::DEFAULT_DOWNLOADS_FOLDER => $this->updateConfig->getCustomDownloadsDir() ));
                foreach( $dirsToSkip as $defaultDir => $customPath ) 
                {
                    if( $customPath ) 
                    {
                        $this->output("Skipping: " . $defaultDir);
                        $excludeFromCopy[] = $this->sourceDir . DIRECTORY_SEPARATOR . $defaultDir;
                    }

                }
                break;
            default:
                throw new \WHMCS\Exception("Unexpected parameter");
        }
        $excludeFromCopy[] = "vendor/whmcs/whmcs/vendor/whmcs/whmcs-foundation/lib/Installer/Composer/Hooks";
        $this->output("Relocating WHMCS core files...");
        $this->performEarlyFileCopy(array( "init.php", "vendor/whmcs/whmcs-foundation/lib/Application.php" ));
        \WHMCS\Utility\File::recursiveCopy($this->sourceDir, $this->targetDir, $excludeFromCopy);
        if( self::RELMODE_UPDATE === $relocationMode ) 
        {
            foreach( $dirsWithCustomTargets as $defaultDir => $customPath ) 
            {
                if( $customPath ) 
                {
                    $this->output("Copying " . $defaultDir . " directory to its configured path (" . $customPath . ")...");
                    \WHMCS\Utility\File::recursiveCopy($this->sourceDir . DIRECTORY_SEPARATOR . $defaultDir, $this->normalizeDirSettingPath($customPath));
                }

            }
        }

        $installedJsonPath = $this->targetDir . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "composer" . DIRECTORY_SEPARATOR . "installed.json";
        if( file_exists($installedJsonPath) ) 
        {
            $this->output("Deleting installed.json file: " . $installedJsonPath);
            if( !@unlink($installedJsonPath) ) 
            {
                $this->output("Error: installed.json file could not be deleted. Installation will continue.");
            }

        }

        if( $this->deleteSourceFiles ) 
        {
            $this->output("Deleting source directory to conserve disk space: " . $this->sourceDir);
            try
            {
                \WHMCS\Utility\File::recursiveDelete($this->sourceDir);
            }
            catch( \Exception $e ) 
            {
                $this->output("Error while trying to delete source directory: " . $e->getMessage() . ". Installation will continue.");
            }
        }
        else
        {
            $this->output("Source files have not been deleted.");
        }

    }

    public function installWhmcs()
    {
        $this->output("No existing installation was found, installing a new copy of WHMCS...");
        $this->relocateWhmcsCoreFiles(self::RELMODE_INSTALL);
        $this->initializeConfigFile();
        $this->output("WHMCS files have been successfully copied.", self::OUTPUT_INFO);
    }

    public function updateWhmcs()
    {
        $this->output("An existing installation has been found - updating...");
        $this->relocateWhmcsCoreFiles(self::RELMODE_UPDATE);
        $this->output("WHMCS files have been successfully updated.", self::OUTPUT_INFO);
    }

    protected function copyFile($source, $destination)
    {
        if( !@copy($source, $destination) ) 
        {
            throw new \WHMCS\Exception("Unable to copy " . $source . " to " . $destination);
        }

    }

    public function initializeConfigFile($initConfigFile = NULL, $targetConfigFile = NULL)
    {
        if( is_null($initConfigFile) ) 
        {
            $initConfigFile = $this->targetDir . DIRECTORY_SEPARATOR . $this->updateConfig->getDefaultApplicationConfigFilename() . ".new";
        }

        if( is_null($targetConfigFile) ) 
        {
            $targetConfigFile = $this->targetDir . DIRECTORY_SEPARATOR . $this->updateConfig->getDefaultApplicationConfigFilename();
        }

        if( !file_exists($targetConfigFile) ) 
        {
            $this->output("Creating configuration file...");
            $this->copyFile($initConfigFile, $targetConfigFile);
        }
        else
        {
            throw new \WHMCS\Exception("Configuration file already exists at " . $targetConfigFile . ". Please verify your installation is still correct.");
        }

    }

    public function run()
    {
        $this->output("-------------------------------------", self::OUTPUT_INFO);
        $this->output(" WHMCS Installation/Update Assistant ", self::OUTPUT_INFO);
        $this->output("-------------------------------------", self::OUTPUT_INFO);
        $updateConfig = $this->getUpdateConfig();
        $existingConfigPath = $this->getTargetDir() . DIRECTORY_SEPARATOR . $updateConfig->getDefaultApplicationConfigFilename();
        $this->loadConfigFile($existingConfigPath);
        if( $this->findExistingInstallation() ) 
        {
            $this->updateWhmcs();
        }
        else
        {
            $this->installWhmcs();
        }

    }

}


