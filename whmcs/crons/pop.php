<?php 
require_once(__DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php");
require(ROOTDIR . "/includes/adminfunctions.php");
require(ROOTDIR . "/includes/ticketfunctions.php");
define("IN_CRON", true);
$transientData = WHMCS\TransientData::getInstance();
$transientData->delete("popCronComplete");
$whmcs = App::self();
$whmcsAppConfig = $whmcs->getApplicationConfig();
$attachments_dir = $whmcsAppConfig["attachments_dir"] . "/";
if( defined("PROXY_FILE") ) 
{
    echo formatoutput(WHMCS\Cron::getLegacyCronMessage());
}

echo formatoutput("<b>POP Import Log</b><br>Date: " . date("d/m/Y H:i:s") . "<hr>");
$ticketDepartments = Illuminate\Database\Capsule\Manager::table("tblticketdepartments")->where("host", "!=", "")->where("port", "!=", "")->where("login", "!=", "")->orderBy("order")->get();
foreach( $ticketDepartments as $ticketDepartment ) 
{
    ob_start();
    echo "Host: " . $ticketDepartment->host . "<br>Email: " . $ticketDepartment->login . "<br>";
    $connectionFlags = "/pop3/notls";
    if( $ticketDepartment->port == 995 ) 
    {
        $connectionFlags = "/pop3/ssl/novalidate-cert";
    }

    try
    {
        $connectionString = $ticketDepartment->host . ":" . $ticketDepartment->port;
        $mailbox = new WHMCS\WhmcsMailbox("{" . $connectionString . $connectionFlags . "}INBOX", $ticketDepartment->login, decrypt($ticketDepartment->password), sys_get_temp_dir(), WHMCS\Config\Setting::getValue("Charset"));
        $mailIds = $mailbox->searchMailbox();
        if( !$mailIds ) 
        {
            echo "Mailbox is empty<hr>";
        }
        else
        {
            echo "Email Count: " . $mailbox->countMails() . "<hr>";
        }

        foreach( $mailIds as $mailId ) 
        {
            $mail = $mailbox->getMail($mailId);
            $toEmails = array(  );
            $toString = $mail->toString;
            $subject = $mail->subject;
            $fromName = $mail->fromName;
            $fromEmail = $mail->fromAddress;
            if( !$fromName ) 
            {
                $fromName = $fromEmail;
            }

            $replyTo = $mail->replyTo;
            if( $replyTo ) 
            {
                $fromEmail = key($replyTo);
                $fromName = ($replyTo[$fromEmail] ?: $fromEmail);
            }

            foreach( explode(",", $toString) as $toEmail ) 
            {
                if( strpos($toEmail, "<") !== false ) 
                {
                    $emailAddressesMatch = array(  );
                    preg_match("/<(\\S+)>/", $toEmail, $emailAddressesMatch);
                    $emailAddressesMatch = preg_grep("/</", $emailAddressesMatch, PREG_GREP_INVERT);
                    foreach( $emailAddressesMatch as $emailAddress ) 
                    {
                        $toEmails[] = $emailAddress;
                    }
                }
                else
                {
                    $toEmails[] = $toEmail;
                }

            }
            $toEmails[] = $ticketDepartment->email;
            $subject = str_replace(array( "{", "}" ), array( "[", "]" ), $mail->subject);
            $messageBody = $mail->textPlain;
            if( !$messageBody ) 
            {
                $messageBody = strip_tags($mail->textHtml);
            }

            if( !$messageBody ) 
            {
                $messageBody = "No message found.";
            }

            $messageBody = str_replace("&nbsp;", " ", $messageBody);
            $ticketAttachments = array(  );
            $attachments = $mail->getAttachments();
            foreach( $attachments as $attachment ) 
            {
                $filename = $attachment->name;
                if( checkTicketAttachmentExtension($filename) ) 
                {
                    $filenameParts = explode(".", $filename);
                    $extension = end($filenameParts);
                    $filename = implode(array_slice($filenameParts, 0, -1));
                    $filename = preg_replace("/[^a-zA-Z0-9-_ ]/", "", $filename);
                    if( !$filename ) 
                    {
                        $filename = "filename";
                    }

                    mt_srand(time());
                    $rand = mt_rand(100000, 999999);
                    $attachmentFilename = $rand . "_" . $filename . "." . $extension;
                    while( file_exists($attachments_dir . $attachmentFilename) ) 
                    {
                        mt_srand(time());
                        $rand = mt_rand(100000, 999999);
                        $attachmentFilename = $rand . "_" . $filename . "." . $extension;
                    }
                    $ticketAttachments[] = $attachmentFilename;
                    $flIn = fopen($attachment->filePath, "rb");
                    if( $flIn ) 
                    {
                        $flOut = fopen($attachments_dir . $attachmentFilename, "wb");
                        if( $flOut ) 
                        {
                            $chunkSize = 131072;
                            do
                            {
                                $content = fread($flIn, $chunkSize);
                                if( 0 < strlen($content) ) 
                                {
                                    fwrite($flOut, $content);
                                }

                            }
                            while( strlen($content) == $chunkSize );
                            fclose($flOut);
                        }
                        else
                        {
                            $messageBody .= "\n\nAttachment " . $filename . " could not be saved.";
                        }

                        fclose($flIn);
                        unlink($attachment->filePath);
                    }

                }
                else
                {
                    unlink($attachment->filePath);
                    $messageBody .= "\n\nAttachment " . $filename . " blocked - file type not allowed.";
                }

            }
            $attachmentList = implode("|", $ticketAttachments);
            processPoppedTicket(implode(",", $toEmails), $fromName, $fromEmail, $subject, $messageBody, $attachmentList);
            $mailbox->deleteMail($mailId);
        }
        $mailbox->expungeDeletedMails();
    }
    catch( Exception $e ) 
    {
        echo $e->getMessage();
    }
    $content = ob_get_contents();
    ob_end_clean();
    echo formatoutput($content);
}
$transientData->store("popCronComplete", "true", 3600);
function formatOutput($output)
{
    if( WHMCS\Environment\Php::isCli() ) 
    {
        $output = strip_tags(str_replace(array( "<br>", "<hr>" ), array( "\n", "\n---\n" ), $output));
    }

    return $output;
}


