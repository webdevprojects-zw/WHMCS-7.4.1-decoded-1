<?php 
namespace WHMCS\Installer\Cli;


class Application extends AbstractApplication
{
    protected $preflightCount = 1;

    public function status()
    {
        $installer = $this->getInstaller();
        $padding = $this->getCli()->padding(37);
        $padding->label("Database schema version")->result($installer->getVersion()->getCanonical());
        $padding->label("Deployed WHMCS application version")->result($installer->getLatestVersion()->getCanonical());
        return $this;
    }

    public function preflightCheckOutput($msg)
    {
        $this->getCli()->inline(str_pad($this->preflightCount . ". " . $msg . " ", 60, ".", STR_PAD_RIGHT) . " ");
        $this->preflightCount++;
        return $this->getCli();
    }

    protected function preInstallCheck()
    {
        $cli = $this->getCli();
        $cli->comment("** Preflight Checks **");
        $this->preflightCheckOutput("Attempting to load configuration file");
        $config = new \WHMCS\Config\Application();
        $config->loadConfigFile(\WHMCS\Config\Application::WHMCS_DEFAULT_CONFIG_FILE);
        if( !$config->isConfigFileLoaded() ) 
        {
            $cli->error("FAILED")->br();
            throw new \WHMCS\Exception("Configuration file not found at '" . ROOTDIR . DIRECTORY_SEPARATOR . \WHMCS\Config\Application::WHMCS_DEFAULT_CONFIG_FILE . "'" . "\n" . "Installation requires a valid configuration file in the root WHMCS directory.");
        }

        $cli->green("Ok");
        $this->preflightCheckOutput("Validating configuration data");
        $invalidConfigurationValues = $config->invalidConfigurationValues();
        if( !empty($invalidConfigurationValues) ) 
        {
            $cli->error("FAILED")->br();
            throw new \WHMCS\Exception("Please address the invalid configuration data." . "\n- " . implode("\n- ", $invalidConfigurationValues));
        }

        $cli->green("Ok");
        $this->preflightCheckOutput("Attempting to connect to database");
        try
        {
            \DI::make("db");
        }
        catch( \WHMCS\Exception $e ) 
        {
            $cli->error("FAILED")->br();
            throw new \WHMCS\Exception("Database connection failed: " . $e->getMessage());
        }
        $cli->green("Ok");
        $this->preflightCheckOutput("Validating database for install");
        $installer = $this->getInstaller();
        try
        {
            $installer->checkIfInstalled(true);
            if( $installer->isInstalled() ) 
            {
                throw new \WHMCS\Exception("Existing WHMCS installation found in database.");
            }

            if( !$installer->getDatabase() ) 
            {
                throw new \WHMCS\Exception("Unable to connect to database.");
            }

            if( \DI::make("db")->isSqlStrictMode() ) 
            {
                throw new \WHMCS\Exception("MySQL Strict Mode is enabled.");
            }

        }
        catch( \Exception $e ) 
        {
            $cli->error("FAILED")->br();
            throw $e;
        }
        $cli->green("Ok")->br()->green("All checks passed successfully. Ready to Install.");
        return $this;
    }

    protected function installDatabaseSeed()
    {
        mysql_import_file("install.sql");
        mysql_import_file("emailtemplates.sql");
    }

    protected function installOneTimeTasks()
    {
        $cli = $this->getCli();
        $adminPassword = generateFriendlyPassword();
        mysql_query(sprintf("INSERT INTO `tbladmins` ( `username` , `password`, `firstname` , `lastname` , `email` , `roleid` , `signature` , `notes` , `supportdepts`, `template`)" . " VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", "Admin", md5($adminPassword), "Primary", "User", "yourname@example.com", "1", "", "Welcome to WHMCS!  Please ensure you have setup the cron job to automate tasks", ",", "blend"));
        $cli->br()->out("A primary admin user account has been created with the following credentials:")->br()->out("Username: Admin")->out("Password: " . $adminPassword)->br();
        return $this;
    }

    public function install()
    {
        $cli = $this->getCli();
        try
        {
            $this->preInstallCheck();
            $installer = $this->getInstaller();
            if( !$cli->arguments->defined("non-interactive") ) 
            {
                $input = $cli->confirm("Are you sure you wish to continue?");
                if( !$input->confirmed() ) 
                {
                    throw new \WHMCS\Exception\Installer\UserBail("Installation aborted per request.");
                }

            }

            $cli->br()->comment("** Beginning Installation **")->out("This may take a few minutes. Please Wait...");
            $cli->out("");
            $this->installDatabaseSeed();
            $this->addProgressBar();
            try
            {
                $installer->runUpgrades();
            }
            catch( \WHMCS\Exception $e ) 
            {
                throw new \WHMCS\Exception\Fatal("Installation database upgrade failed: " . $e->getMessage());
            }
            $this->installOneTimeTasks();
            $cli->green("Installation Completed Successfully!");
        }
        catch( \WHMCS\Exception\Installer\UserBail $e ) 
        {
            $cli->br()->bold($e->getMessage());
        }
        return $this;
    }

    public function upgrade()
    {
        $this->addProgressBar();
        $cli = $this->getCli();
        $installer = $this->getInstaller();
        $dbVersion = $installer->getVersion()->getCanonical();
        $filesVersion = $installer->getLatestVersion()->getCanonical();
        $cli->out("");
        $this->status();
        $cli->out("");
        if( $installer->isUpToDate() ) 
        {
            $cli->comment("WHMCS is up to date!");
        }
        else
        {
            try
            {
                if( !$cli->arguments->defined("non-interactive") ) 
                {
                    $input = $cli->confirm(sprintf("Are you sure you which to upgrade from %s to %s?", $dbVersion, $filesVersion));
                    if( !$input->confirmed() ) 
                    {
                        throw new \WHMCS\Exception\Installer\UserBail("Upgrade aborted per request.");
                    }

                    $input = $cli->confirm("Have you backed up your database?");
                    if( !$input->confirmed() ) 
                    {
                        throw new \WHMCS\Exception\Installer\UserBail("Please backup your database and run this program again.");
                    }

                    $cli->out("");
                }

                $cli->comment("** Beginning Upgrade **")->out("This may take a few minutes. Please Wait...");
                $cli->out("");
                try
                {
                    $installer->runUpgrades();
                }
                catch( \WHMCS\Exception $e ) 
                {
                    throw new \WHMCS\Exception\Fatal("Applying database upgrade failed: " . $e->getMessage());
                }
                \Log::debug("Applying Updates Done");
                $installer->checkIfInstalled();
                $cli->out("");
                $this->status();
            }
            catch( \WHMCS\Exception\Installer\UserBail $e ) 
            {
                $this->status();
                $cli->comment($e->getMessage());
            }
        }

        return $this;
    }

}


