<?php 
namespace WHMCS\Updater\Version;


class Version600beta2 extends IncrementalVersion
{
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . "ensimx";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . "fluidvm";
    }

}


