<?php 
namespace WHMCSProjectManagement;


class Messages extends BaseProjectEntity
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
        $messages = array(  );
        $where = array( "projectid" => $this->project->id );
        $totalMessages = get_query_val("mod_projectmessages", "COUNT(id)", $where);
        if( $messageId ) 
        {
            $where["mod_projectmessages.id"] = $messageId;
        }

        $messageNumber = $totalMessages;
        for( $result = select_query("mod_projectmessages", "*,(SELECT CONCAT(firstname,' ',lastname,'|',email) FROM tbladmins WHERE tbladmins.id=mod_projectmessages.adminid) AS adminuser", $where, "date", "DESC"); $data = mysql_fetch_array($result); $messageNumber-- ) 
        {
            $msgid = $data["id"];
            $date = $data["date"];
            $message = strip_tags($data["message"]);
            $adminuser = $data["adminuser"];
            $adminuser = explode("|", $adminuser, 2);
            list($adminuser, $adminemail) = $adminuser;
            $dates = explode(" ", $date);
            $dates2 = explode("-", $dates[0]);
            $dates = $dates[1];
            $dates = explode(":", $dates);
            $date = date("D, F jS, g:ia", mktime($dates[0], $dates[1], $dates[2], $dates2[1], $dates2[2], $dates2[0]));
            $attachments = $this->project->files()->get($msgid);
            require_once(ROOTDIR . "/includes/ticketfunctions.php");
            $messages[] = array( "id" => $msgid, "date" => $date, "name" => $adminuser, "email" => $adminemail, "gravatarUrl" => pm_get_gravatar($adminemail, "70"), "message" => nl2br(ticketAutoHyperlinks($message)), "attachment" => $attachments, "number" => $messageNumber );
        }
        return $messages;
    }

    public function add()
    {
        $message = trim(\App::getFromRequest("message"));
        $fileIds = \App::getFromRequest("fileId");
        if( !$message ) 
        {
            throw new Exception("Message is required");
        }

        $newMessageId = insert_query("mod_projectmessages", array( "projectid" => $this->project->id, "date" => "now()", "message" => $message, "adminid" => \WHMCS\Session::get("adminid") ));
        $this->project->log()->add("Message Posted");
        if( $fileIds ) 
        {
            Models\ProjectFile::whereIn("id", $fileIds)->update(array( "message_id" => $newMessageId ));
        }

        $projectChanges[] = array( "field" => "Message Posted", "oldValue" => "N/A", "newValue" => $message );
        $this->project->notify()->staff($projectChanges);
        $data = $this->get($newMessageId);
        return array( "newMessageId" => $newMessageId, "newMessage" => $data, "projectId" => $this->project->id, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count(), "messageCount" => count($this->get()) );
    }

    public function delete()
    {
        $projectChanges = array(  );
        $msgId = trim(\App::getFromRequest("msgid"));
        delete_query("mod_projectmessages", array( "projectid" => $this->project->id, "id" => $msgId ));
        $attachmentCollection = Models\ProjectFile::whereProjectId($this->project->id)->where("message_id", "=", $msgId)->get();
        $deletedFiles = array(  );
        if( $attachmentCollection ) 
        {
            foreach( $attachmentCollection as $attach ) 
            {
                if( $attach ) 
                {
                    try
                    {
                        $deletedFiles[] = $attach->id;
                        $projectChanges[] = array( "field" => "File Deleted on Message Delete", "oldValue" => substr($attach->filename, 7), "newValue" => "" );
                        $this->project->files()->delete($attach);
                    }
                    catch( \Exception $e ) 
                    {
                    }
                }

            }
        }

        $this->project->log()->add("Message Deleted");
        $projectChanges[] = array( "field" => "Message Deleted", "oldValue" => $msgId, "newValue" => "" );
        $this->project->notify()->staff($projectChanges);
        return array( "deletedMessageId" => $msgId, "deletedFiles" => $deletedFiles, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count(), "messageCount" => count($this->get()) );
    }

    public function uploadFile()
    {
        $projectId = $this->project->id;
        $this->createAttachmentsDirectory();
        $projectDir = $this->getAttachmentsDirectory($projectId);
        if( !is_dir($projectDir) ) 
        {
            mkdir($projectDir);
        }

        $newFiles = array(  );
        if( is_array($_FILES["attachments"]["name"]) ) 
        {
            foreach( $_FILES["attachments"]["name"] as $key => $value ) 
            {
                $file = new \WHMCS\File\Upload("attachments", $key);
                $newFiles[] = $this->saveFile($file, $projectDir);
            }
        }
        else
        {
            $file = new \WHMCS\File\Upload("attachments");
            $newFiles[] = $this->saveFile($file, $projectDir);
        }

        return array( "uploaded" => true, "newFiles" => $newFiles );
    }

    protected function saveFile(\WHMCS\File\Upload $file, $projectDir)
    {
        $prefix = "{RAND}_";
        $filename = $file->move($projectDir, $prefix);
        $uploadedFile = new Models\ProjectFile();
        $uploadedFile->projectId = $this->project->id;
        $uploadedFile->filename = $filename;
        $uploadedFile->adminId = \WHMCS\Session::get("adminid");
        $uploadedFile->messageId = 0;
        $uploadedFile->save();
        $this->project->log()->add("File Uploaded: " . $this->project->files()->formatFilenameForDisplay($filename));
        return $uploadedFile->id;
    }

}


