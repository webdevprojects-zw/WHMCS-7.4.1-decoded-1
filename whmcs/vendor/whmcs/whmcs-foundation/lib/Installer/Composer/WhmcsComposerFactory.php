<?php 
namespace WHMCS\Installer\Composer;


class WhmcsComposerFactory extends \Composer\Factory
{
    protected function addLocalRepository(\Composer\IO\IOInterface $io, \Composer\Repository\RepositoryManager $rm, $vendorDir)
    {
        $rm->setRepositoryClass(WhmcsRepository::REPOSITORY_TYPE, "WHMCS\\Installer\\Composer\\WhmcsRepository");
        parent::addLocalRepository($io, $rm, $vendorDir);
    }

}


