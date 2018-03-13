<?php 
namespace WHMCS\Updater\Version;


class Version700alpha1 extends IncrementalVersion
{
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $config = \DI::make("config");
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . (($config["customadminpath"] ?: "admin")) . DIRECTORY_SEPARATOR . "browser.php";
    }

}


