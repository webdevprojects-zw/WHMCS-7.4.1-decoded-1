<?php 
namespace WHMCSProjectManagement;


class Files extends BaseProjectEntity
{
    protected $attachmentsDirectory = NULL;

    public function __construct(Project $project)
    {
        parent::__construct($project);
        $this->attachmentsDirectory = \App::getAttachmentsDir();
    }

    protected function getAttachmentsDirectory($projectId = NULL)
    {
        return $this->attachmentsDirectory . DIRECTORY_SEPARATOR . "projects" . DIRECTORY_SEPARATOR . (($projectId ? (int) $projectId . DIRECTORY_SEPARATOR : ""));
    }

    protected function createAttachmentsDirectory()
    {
        $dir = $this->getAttachmentsDirectory();
        if( !is_dir($dir) ) 
        {
            mkdir($dir);
        }

    }

    public function get($messageId = 0)
    {
        $projectDir = $this->getAttachmentsDirectory($this->project->id);
        $attachments = array(  );
        $fileList = Models\ProjectFile::whereProjectId($this->project->id);
        if( $messageId ) 
        {
            $fileList->where("message_id", $messageId);
        }

        foreach( $fileList->get() as $file ) 
        {
            $attachment = $file->filename;
            $displayFilename = substr($attachment, 7);
            $reversedParts = explode(".", strrev($displayFilename), 2);
            $filename = strrev($reversedParts[1]);
            $extension = "." . strrev($reversedParts[0]);
            $attachments[$file->id] = array( "fullFilename" => $attachment, "displayFilename" => $displayFilename, "filename" => $filename, "extension" => $extension, "filesize" => $this->formatFileSize(filesize($projectDir . $attachment)), "isImage" => $this->isAnImage($attachment), "browserViewable" => $this->isBrowserViewable($attachment), "admin" => getAdminName($file->adminId), "when" => str_replace("-", "", Helper::daysUntilDate($file->createdAt)) );
        }
        return $attachments;
    }

    public function upload()
    {
        $this->createAttachmentsDirectory();
        $projectDir = $this->getAttachmentsDirectory($this->project->id);
        if( !is_dir($projectDir) ) 
        {
            mkdir($projectDir);
        }

        $file = new \WHMCS\File\Upload("file");
        $prefix = "{RAND}_";
        $filename = $file->move($projectDir, $prefix);
        $uploadedFile = new Models\ProjectFile();
        $uploadedFile->projectId = $this->project->id;
        $uploadedFile->filename = $filename;
        $uploadedFile->adminId = \WHMCS\Session::get("adminid");
        $uploadedFile->messageId = 0;
        $uploadedFile->save();
        $this->project->log()->add("File Uploaded: " . $this->formatFilenameForDisplay($filename));
        $reversedParts = explode(".", strrev($_FILES["file"]["name"]), 2);
        $fileExtension = "." . strrev($reversedParts[0]);
        $fileCount = Models\ProjectFile::where("project_id", $this->project->id)->count();
        return array( "key" => $uploadedFile->id, "fileCount" => $fileCount, "admin" => getAdminName(), "extension" => $fileExtension, "filename" => str_replace($fileExtension, "", $_FILES["file"]["name"]), "filesize" => $this->formatFileSize($_FILES["file"]["size"]), "isImage" => $this->isAnImage($filename), "browserViewable" => $this->isBrowserViewable($filename) );
    }

    public function delete(Models\ProjectFile $specificFile = NULL)
    {
        $num = (int) \App::getFromRequest("num");
        if( $num || $specificFile ) 
        {
            $fileToDelete = ($specificFile ?: Models\ProjectFile::findOrFail($num));
            $projectDir = $this->getAttachmentsDirectory($this->project->id);
            $filename = $fileToDelete->filename;
            try
            {
                $file = new \WHMCS\File($projectDir . $fileToDelete->filename);
                $file->delete();
            }
            catch( \WHMCS\Exception\File\NotDeleted $e ) 
            {
                throw new Exception("Unable to Delete File: " . $e->getMessage());
            }
            catch( \Exception $e ) 
            {
            }
            $fileToDelete->delete();
            $this->project->log()->add("File Deleted: " . $this->formatFilenameForDisplay($filename));
        }

        return array( "deletedFileNumber" => $num, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count() );
    }

    protected function formatFileSize($val, $digits = 3)
    {
        $factor = 1024;
        $symbols = array( "", "k", "M", "G", "T", "P", "E", "Z", "Y" );
        for( $i = 0; $i < count($symbols) - 1 && $factor <= $val; $i++ ) 
        {
            $val /= $factor;
        }
        $p = strpos($val, ".");
        if( $p !== false && $digits < $p ) 
        {
            $val = round($val);
        }
        else
        {
            if( $p !== false ) 
            {
                $val = round($val, $digits - $p);
            }

        }

        return round($val, $digits) . " " . $symbols[$i] . "B";
    }

    protected function isAnImage($file)
    {
        if( !$file ) 
        {
            return false;
        }

        return (bool) getimagesize($this->getAttachmentsDirectory($this->project->id) . $file);
    }

    protected function isBrowserViewable($fileName)
    {
        $browserViewable = array( "application/javascript", "application/pdf", "text/css", "text/plain" );
        $fileInfo = null;
        if( class_exists("\\finfo") ) 
        {
            $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
            $fileInfo = $fileInfo->file($this->getAttachmentsDirectory($this->project->id) . $fileName);
        }
        else
        {
            if( function_exists("finfo_open") ) 
            {
                $fInfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileInfo = finfo_file($fInfo, $this->getAttachmentsDirectory($this->project->id) . $fileName);
            }

        }

        if( $fileInfo ) 
        {
            return in_array($fileInfo, $browserViewable);
        }

        return false;
    }

    public function formatFilenameForDisplay($filename)
    {
        return substr($filename, 7);
    }

}


