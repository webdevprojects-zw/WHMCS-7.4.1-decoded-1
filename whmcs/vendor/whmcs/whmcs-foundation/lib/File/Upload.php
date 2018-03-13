<?php 
namespace WHMCS\File;


class Upload extends \WHMCS\File
{
    protected $uploadFilename = NULL;
    protected $uploadTmpName = NULL;

    public function __construct($name, $key = NULL)
    {
        if( !isset($_FILES[$name]) ) 
        {
            throw new \WHMCS\Exception\File\NotUploaded("Check name and key parameters.");
        }

        if( is_numeric($key) ) 
        {
            $this->uploadFilename = $_FILES[$name]["name"][$key];
            $this->uploadTmpName = $_FILES[$name]["tmp_name"][$key];
        }
        else
        {
            $this->uploadFilename = $_FILES[$name]["name"];
            $this->uploadTmpName = $_FILES[$name]["tmp_name"];
        }

        if( !$this->isUploaded() ) 
        {
            throw new \WHMCS\Exception\File\NotUploaded(\Lang::trans("filemanagement.nofileuploaded"));
        }

        if( !$this->isFileNameSafe($this->getCleanName()) ) 
        {
            throw new \WHMCS\Exception(\Lang::trans("filemanagement.invalidname"));
        }

    }

    public function getFileName()
    {
        return $this->uploadFilename;
    }

    public function getFileTmpName()
    {
        return $this->uploadTmpName;
    }

    public function getCleanName()
    {
        return preg_replace("/[^a-zA-Z0-9-_. ]/", "", $this->getFileName());
    }

    public function isUploaded()
    {
        return is_uploaded_file($this->getFileTmpName());
    }

    public function move($dest_dir = "", $prefix = "")
    {
        if( !is_writeable($dest_dir) ) 
        {
            throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.checkPermissions"));
        }

        $destinationPath = $this->generateUniqueDestinationPath($dest_dir, $prefix);
        if( !move_uploaded_file($this->getFileTmpName(), $destinationPath) ) 
        {
            throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.checkAvailableDiskSpace"));
        }

        return basename($destinationPath);
    }

    protected function generateUniqueDestinationPath($dest_dir, $prefix)
    {
        mt_srand($this->makeRandomSeed());
        $i = 1;
        while( $i <= 30 ) 
        {
            $rand = mt_rand(100000, 999999);
            $destinationPath = $dest_dir . DIRECTORY_SEPARATOR . str_replace("{RAND}", $rand, $prefix) . $this->getCleanName();
            $file = new \WHMCS\File($destinationPath);
            if( $file->exists() ) 
            {
                if( strpos($prefix, "{RAND}") === false ) 
                {
                    throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.fileAlreadyExists"));
                }

                $i++;
            }
            else
            {
                return $destinationPath;
            }

        }
        throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.noUniqueName"));
    }

    protected function makeRandomSeed()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (double) $sec + (double) $usec * 100000;
    }

    public function checkExtension()
    {
        return self::isExtensionAllowed($this->getFileName());
    }

    public static function isExtensionAllowed($filename)
    {
        if( $filename[0] == "." ) 
        {
            return false;
        }

        $whmcs = \DI::make("app");
        $alwaysBannedExtensions = array( "php", "cgi", "pl", "htaccess" );
        $extensionArray = explode(",", strtolower($whmcs->get_config("TicketAllowedFileTypes")));
        $filenameParts = pathinfo($filename);
        $fileExtension = strtolower($filenameParts["extension"]);
        if( in_array($fileExtension, $alwaysBannedExtensions) ) 
        {
            return false;
        }

        if( in_array("." . $fileExtension, $extensionArray) ) 
        {
            return true;
        }

        return false;
    }

    public function contents()
    {
        return file_get_contents($this->getFileTmpName());
    }

    public function setFilename($name)
    {
        $this->uploadFilename = $name;
    }

}


