<?php 
namespace WHMCS\Installer;


class Installer
{
    protected $installed = false;
    protected $version = NULL;
    protected $latestVersion = NULL;
    protected $database = NULL;
    protected $customAdminPath = "admin";
    protected $templatesCompiledDir = \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER;
    protected $installerDirectory = "";

    const DEFAULT_VERSION = "0.0.0";

    public function __construct(\WHMCS\Version\SemanticVersion $installedVersion, \WHMCS\Version\SemanticVersion $latestVersionAvailable)
    {
        $this->setVersion($installedVersion)->setLatestVersion($latestVersionAvailable)->checkIfInstalled();
    }

    public function setInstallerDirectory($dir)
    {
        if( !is_dir($dir) ) 
        {
            throw new \WHMCS\Exception\Installer(sprintf("\"%s\" is not a valid installer directory", $dir));
        }

        $this->installerDirectory = $dir;
    }

    public function getInstallerDirectory()
    {
        return $this->installerDirectory;
    }

    public function isInstalled()
    {
        return $this->installed;
    }

    public function getLatestMajorMinorVersion()
    {
        $latest = $this->getLatestVersion();
        return sprintf("%s.%s", $latest->getMajor(), $latest->getMinor());
    }

    public function getInstalledVersion()
    {
        return $this->getVersion()->getRelease();
    }

    public function getInstalledVersionNumeric()
    {
        $previous = $this->getVersion();
        return sprintf("%s%s%s", $previous->getMajor(), $previous->getMinor(), $previous->getPatch());
    }

    protected function shouldRunUpgrade(\WHMCS\Version\SemanticVersion $versionOfInterest)
    {
        $previousInstalledVersion = $this->getVersionFromDatabase();
        return \WHMCS\Version\SemanticVersion::compare($versionOfInterest, $previousInstalledVersion, ">");
    }

    public function isUpToDate()
    {
        return !$this->shouldRunUpgrade($this->latestVersion);
    }

    public function checkIfInstalled($forceLoadConfig = false)
    {
        $db = null;
        if( !$forceLoadConfig ) 
        {
            try
            {
                $capsule = \WHMCS\Database\Capsule::getInstance();
                if( $capsule && ($connection = $capsule->connection()) ) 
                {
                    $db = $connection->getPdo();
                }

            }
            catch( \Exception $e ) 
            {
            }
        }

        $applicationConfig = new \WHMCS\Config\Application();
        if( !$db && $applicationConfig->configFileExists(\WHMCS\Config\Application::WHMCS_DEFAULT_CONFIG_FILE) ) 
        {
            $db_host = $db_port = $db_username = $db_password = $db_name = $mysql_charset = $templates_compiledir = $customadminpath = "";
            include(ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php");
            if( $customadminpath ) 
            {
                $this->customAdminPath = $customadminpath;
            }

            if( $templates_compiledir ) 
            {
                $this->templatesCompiledDir = $templates_compiledir;
            }

            if( !$this->templatesCompiledDir || preg_match("/^" . \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER . "[\\\\\\/]*\$/", $this->templatesCompiledDir) ) 
            {
                $this->templatesCompiledDir = ROOTDIR . DIRECTORY_SEPARATOR . \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER;
            }

            $this->templatesCompiledDir = rtrim($this->templatesCompiledDir, DIRECTORY_SEPARATOR);
            if( $db_username && $db_name ) 
            {
                try
                {
                    $db = $this->factoryDatabase($db_host, $db_port, $db_username, $db_password, $db_name, $mysql_charset);
                    $this->setDatabase($db);
                }
                catch( \WHMCS\Exception $e ) 
                {
                }
            }

        }

        try
        {
            if( $db ) 
            {
                $previousVersion = $this->getVersionFromDatabase();
                if( $previousVersion instanceof \WHMCS\Version\SemanticVersion ) 
                {
                    $this->setVersion($previousVersion);
                }

                if( !\WHMCS\Version\SemanticVersion::compare(new \WHMCS\Version\SemanticVersion(self::DEFAULT_VERSION), $previousVersion, "==") ) 
                {
                    $this->installed = true;
                }

            }

        }
        catch( \Exception $e ) 
        {
        }
        return $this;
    }

    public function factoryDatabase($host = "127.0.0.1", $port = "", $username = "", $password = "", $dbName = "", $mysqlCharset = "")
    {
        $tmpConfig = new \WHMCS\Config\Application();
        $tmpConfig->setDatabaseCharset($mysqlCharset)->setDatabaseHost($host)->setDatabaseName($dbName)->setDatabaseUsername($username)->setDatabasePassword($password);
        if( $port ) 
        {
            $tmpConfig->setDatabasePort($port);
        }

        try
        {
            $db = new \WHMCS\Database($tmpConfig);
        }
        catch( \Exception $e ) 
        {
            $hostAndPort = $host;
            if( $port ) 
            {
                $hostAndPort .= ":" . $port;
            }

            throw new \WHMCS\Exception(sprintf("Could not connect to MySQL database \"%s\" at \"%s\" with user \"%s\"", $dbName, $hostAndPort, $username));
        }
        return $db;
    }

    protected function getVersionFromDatabase()
    {
        $versionToReturn = new \WHMCS\Version\SemanticVersion(self::DEFAULT_VERSION);
        try
        {
            $storedVersion = $this->fetchDatabaseConfigurationValue("Version");
            $previousVersion = ($storedVersion ? $storedVersion : self::DEFAULT_VERSION);
            if( $previousVersion == "5.3.3" ) 
            {
                $previousVersion .= "-rc.1";
            }

            $versionToReturn = new \WHMCS\Version\SemanticVersion($previousVersion);
        }
        catch( \WHMCS\Exception $e ) 
        {
        }
        return $versionToReturn;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function setDatabase($db)
    {
        $this->database = $db;
        return $this;
    }

    protected function fetchDatabaseConfigurationValue($key = "Version")
    {
        if( !is_string($key) ) 
        {
            throw new \InvalidArgumentException("Configuration setting to retrieve must be a string");
        }

        $query = sprintf("SELECT value FROM tblconfiguration WHERE setting=\"%s\"", $key);
        if( $result = mysql_query($query) ) 
        {
            $data = mysql_fetch_array($result);
            if( isset($data["value"]) ) 
            {
                return trim($data["value"]);
            }

            throw new \WHMCS\Exception(sprintf("Could not retrieve configuration value for \"%s\" . Invalid database schema", $key));
        }

        throw new \WHMCS\Exception("Could not query database");
    }

    protected function storeDatabaseConfigurationValue($value, $key = "Version")
    {
        if( !is_string($value) ) 
        {
            throw new \InvalidArgumentException("Configuration setting value to store must be a string");
        }

        if( !is_string($key) ) 
        {
            throw new \InvalidArgumentException("Configuration setting name to store must be a string");
        }

        $query = sprintf("UPDATE tblconfiguration SET value=\"%s\" WHERE setting=\"%s\"", $value, $key);
        mysql_query($query);
        return $this;
    }

    public function runUpgrades()
    {
        \DI::make("db");
        $versionOfInterest = "";
        try
        {
            foreach( \WHMCS\Updater\Version\IncrementalVersion::$versionIncrements as $version ) 
            {
                $currentVersion = new \WHMCS\Version\SemanticVersion(\WHMCS\Config\Setting::getValue("Version"));
                $versionOfInterest = new \WHMCS\Version\SemanticVersion($version);
                if( \WHMCS\Version\SemanticVersion::compare($versionOfInterest, $currentVersion, ">") ) 
                {
                    \WHMCS\Updater\Version\IncrementalVersion::factory($version)->applyUpdate();
                }

            }
            require_once(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php");
            rebuildModuleHookCache();
            rebuildAddonHookCache();
        }
        catch( \WHMCS\Exception\File\NotDeleted $e ) 
        {
            \Log::warning($e->getMessage(), array( "incrementalVersion" => $versionOfInterest->getCanonical(), "trace" => $e->getTraceAsString() ));
        }
        catch( \WHMCS\Exception $e ) 
        {
            $msg = "Unable to complete incremental updates: " . $e->getMessage();
            \Log::error($msg, array( "incrementalVersion" => $versionOfInterest->getCanonical(), "trace" => $e->getTraceAsString() ));
            throw new \WHMCS\Exception($msg);
        }
        return $this;
    }

    public function getAdminPath()
    {
        return $this->customAdminPath;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion(\WHMCS\Version\SemanticVersion $version)
    {
        $this->version = $version;
        return $this;
    }

    public function getLatestVersion()
    {
        return $this->latestVersion;
    }

    public function setLatestVersion(\WHMCS\Version\SemanticVersion $latest)
    {
        $this->latestVersion = $latest;
        return $this;
    }

    public function clearCompiledTemplates()
    {
        if( is_dir($this->templatesCompiledDir) ) 
        {
            $fileDeletionErrors = false;
            $finder = new \Symfony\Component\Finder\Finder();
            $files = $finder->name("*.php")->in(array( $this->templatesCompiledDir ));
            foreach( $files as $file ) 
            {
                $filename = $file->getFilename();
                if( $filename != "index.php" && !@unlink($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $filename) ) 
                {
                    $fileDeletionErrors = true;
                }

            }
            $subdirsToDelete = array( "HTML" );
            foreach( $subdirsToDelete as $subdir ) 
            {
                if( is_dir($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $subdir) ) 
                {
                    \WHMCS\Utility\File::recursiveDelete($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $subdir);
                }

            }
            if( $fileDeletionErrors ) 
            {
                throw new \WHMCS\Exception("Failed to delete one or more compiled template files.");
            }

        }

        return $this;
    }

    public function setReleaseTierPin()
    {
        if( \WHMCS\Config\Setting::getValue("WHMCSUpdatePinVersion") ) 
        {
            return NULL;
        }

        $filesVersion = new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION);
        switch( $filesVersion->getPreReleaseIdentifier() ) 
        {
            case "release":
                $pin = Composer\ComposerJson::STABILITY_STABLE;
                break;
            case "rc":
                $pin = Composer\ComposerJson::STABILITY_RC;
                break;
            case "beta":
                $pin = Composer\ComposerJson::STABILITY_BETA;
                break;
            case "alpha":
                $pin = Composer\ComposerJson::STABILITY_ALPHA;
                break;
            default:
                $pin = Composer\ComposerJson::STABILITY_STABLE;
        }
        \WHMCS\Config\Setting::setValue("WHMCSUpdatePinVersion", $pin);
    }

}


