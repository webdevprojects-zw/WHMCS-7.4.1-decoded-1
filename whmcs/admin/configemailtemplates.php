<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View Email Templates");
$aInt->title = $aInt->lang("emailtpls", "title");
$aInt->sidebar = "config";
$aInt->icon = "massmail";
$aInt->helplink = "Email Templates";
$activelanguages = WHMCS\Mail\Template::getActiveLanguages();
if( $action == "new" ) 
{
    check_token("WHMCS.admin.default");
    checkPermission("Create/Edit Email Templates");
    $name = App::get_req_var("name");
    if( !empty($name) ) 
    {
        $template = new WHMCS\Mail\Template();
        $template->type = $type;
        $template->name = $name;
        $template->custom = true;
        try
        {
            $template->save();
            logAdminActivity("Email Template Created: '" . $name . "' - Template ID: " . $template->id);
        }
        catch( WHMCS\Exception\Model\UniqueConstraint $e ) 
        {
            redir("error=nameNotUnique");
        }
        catch( Exception $e ) 
        {
        }
        redir("action=edit&id=" . $template->id);
    }

    redir("error=blankName");
}

if( $action == "delatt" ) 
{
    check_token("WHMCS.admin.default");
    checkPermission("Create/Edit Email Templates");
    $template = WHMCS\Mail\Template::find($id);
    $i = (int) $_GET["i"];
    $attachments = $template->attachments;
    try
    {
        $file = new WHMCS\File($whmcs->getDownloadsDir() . DIRECTORY_SEPARATOR . $attachments[$i]);
        $file->delete();
        unset($attachments[$i]);
        $template->attachments = $attachments;
        $template->save();
        logAdminActivity("Email Template Attachments Modified: '" . $template->name . "' - Template ID: " . $template->id);
    }
    catch( WHMCS\Exception\File\NotFound $e ) 
    {
    }
    catch( Exception $e ) 
    {
        $aInt->gracefulExit("Could not delete file: " . htmlentities($e->getMessage()));
    }
    redir("action=edit&id=" . $id);
}

ob_start();
if( $action == "" ) 
{
    if( $addlanguage ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Manage Email Template Languages");
        if( WHMCS\Mail\Template::where("language", "=", $addlang)->count() ) 
        {
            logAdminActivity("Email Template Language Not Added: '" . $addlang . "' already exists");
        }
        else
        {
            $templates = WHMCS\Mail\Template::where("language", "=", "")->get();
            $addlang = $whmcs->get_req_var("addlang");
            foreach( $templates as $template ) 
            {
                $newTemplate = new WHMCS\Mail\Template();
                $newTemplate->type = $template->type;
                $newTemplate->name = $template->name;
                $newTemplate->subject = $template->subject;
                $newTemplate->message = $template->message;
                $newTemplate->language = $addlang;
                $newTemplate->save();
            }
            logAdminActivity("Email Template Language Added: '" . $addlang . "'");
        }

        redir();
    }

    if( $disablelanguage && $dislang ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Manage Email Template Languages");
        WHMCS\Mail\Template::where("language", "=", $dislang)->delete();
        $activelanguages = WHMCS\Mail\Template::getActiveLanguages();
        logAdminActivity("Email Template Language Removed: '" . $dislang . "'");
        redir();
    }

    if( $savemessage ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Create/Edit Email Templates");
        if( $fromname == $CONFIG["CompanyName"] ) 
        {
            $fromname = "";
        }

        if( $fromemail == $CONFIG["Email"] ) 
        {
            $fromemail = "";
        }

        $template = WHMCS\Mail\Template::find($id);
        $attachments = $template->attachments;
        if( isset($_FILES["attachments"]) ) 
        {
            foreach( array_keys($_FILES["attachments"]["name"]) as $i ) 
            {
                try
                {
                    $file = new WHMCS\File\Upload("attachments", $i);
                    $prefix = "{RAND}_";
                    $attachments[] = $file->move($whmcs->getDownloadsDir(), $prefix);
                }
                catch( WHMCS\Exception\File\NotUploaded $e ) 
                {
                }
                catch( Exception $e ) 
                {
                    $aInt->gracefulExit($e->getMessage());
                }
            }
        }

        $copyTo = explode(",", App::getFromRequest("copyto"));
        $bcc = explode(",", App::getFromRequest("bcc"));
        $template->fromName = $fromname;
        $template->fromEmail = $fromemail;
        $template->attachments = $attachments;
        $template->disabled = (bool) $disabled;
        $template->copyTo = $copyTo;
        $template->blindCopyTo = $bcc;
        $template->plaintext = (bool) $plaintext;
        $template->save();
        foreach( $subject as $key => $value ) 
        {
            $template = WHMCS\Mail\Template::find($key);
            $template->subject = WHMCS\Input\Sanitize::decode($value);
            $template->message = WHMCS\Input\Sanitize::decode($message[$key]);
            $template->save();
        }
        logAdminActivity("Email Template Modified: '" . $template->name . "' - Template ID: " . $id);
        if( $toggleeditor ) 
        {
            if( $editorstate ) 
            {
                redir("action=edit&id=" . $template->id);
            }
            else
            {
                redir("action=edit&id=" . $template->id . "&noeditor=1");
            }

        }

        redir("success=true");
    }

    if( $delete == "true" ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Delete Email Templates");
        $id = $whmcs->get_req_var("id");
        $template = WHMCS\Mail\Template::find($id);
        $templateName = $template->name;
        $template->delete();
        logAdminActivity("Email Template Deleted: '" . $templateName . "' - Template ID: " . $id);
        redir("deleted=true");
    }

    if( $success ) 
    {
        infoBox($aInt->lang("emailtpls", "updatesuccess"), $aInt->lang("emailtpls", "updatesuccessinfo"), "success");
    }
    else
    {
        if( $deleted ) 
        {
            infoBox($aInt->lang("emailtpls", "delsuccess"), $aInt->lang("emailtpls", "delsuccessinfo"), "success");
        }

    }

    if( $error ) 
    {
        if( $error == "blankName" ) 
        {
            infoBox(AdminLang::trans("emailtpls.cannotCreateTemplate"), AdminLang::trans("emailtpls.nameCannotBeBlank"), "error");
        }

        if( $error == "nameNotUnique" ) 
        {
            infoBox(AdminLang::trans("emailtpls.cannotCreateTemplate"), $aInt->lang("emailtpls", "nameNotUniqueInfo"), "error");
        }

    }

    echo $infobox;
    $aInt->deleteJSConfirm("doDelete", "emailtpls", "delsure", "?delete=true&id=");
    echo "\n<p>";
    echo $aInt->lang("emailtpls", "info");
    echo "</p>\n\n";
    if( checkPermission("Create/Edit Email Templates", true) ) 
    {
        echo "<div class=\"contextbar\">\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=new\" class=\"form-inline\">\n<strong>";
        echo AdminLang::trans("emailtpls.createnew");
        echo "</strong>\n    &nbsp;&nbsp;&nbsp; Type: <select name=\"type\" class=\"form-control\">\n        <option value=\"general\">";
        echo AdminLang::trans("emailtpls.typegeneral");
        echo "</option>\n        <option value=\"product\">";
        echo AdminLang::trans("emailtpls.typeproduct");
        echo "</option>\n        <option value=\"domain\">";
        echo AdminLang::trans("emailtpls.typedomain");
        echo "</option>\n        <option value=\"invoice\">";
        echo AdminLang::trans("emailtpls.typeinvoice");
        echo "</option>\n        <option value=\"notification\">";
        echo AdminLang::trans("emailtpls.typeNotification");
        echo "</option>\n    </select>\n    &nbsp;&nbsp;&nbsp; ";
        echo AdminLang::trans("emailtpls.uniquename");
        echo ": <input type=\"text\" name=\"name\" class=\"form-control\" />\n    &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"";
        echo AdminLang::trans("emailtpls.create");
        echo "\" class=\"button btn btn-default\">\n</form>\n</div>\n";
    }

    echo "\n";
function outputEmailTpls($type)
{
    global $aInt;
    global $tabledata;
    $tickets = new WHMCS\Tickets();
    $aInt->sortableTableInit("nopagination");
    $templates = WHMCS\Mail\Template::where("type", "=", $type)->where("language", "=", "")->orderBy("name")->get();
    foreach( $templates as $template ) 
    {
        $messageSummary = $tickets->getSummary($template->message, 250);
        $statusIcon = ($template->disabled ? "disabled" : "tick");
        $statusLabel = ($template->disabled ? " <span class=\"label label-default\">Disabled</span>" : "");
        $linkStyle = ($template->disabled ? " style=\"color:#666;\"" : "");
        $customText = ($template->custom ? " <span class=\"label label-danger\">" . $aInt->lang("emailtpls", "custom") . "</a>" : "");
        $editLink = "<a href=\"configemailtemplates.php?action=edit&id=" . $template->id . "\"><img src=\"images/icons/massmail.png\" align=\"absmiddle\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\" />";
        $deleteLink = ($template->custom ? "<a href=\"configemailtemplates.php#\" onClick=\"doDelete('" . $template->id . "');return false\"><img src=\"images/delete.gif\" align=\"absmiddle\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\" /></a>" : "");
        $tabledata[] = array( "<img src=\"images/icons/" . $statusIcon . ".png\" />", "<a href=\"configemailtemplates.php?action=edit&id=" . $template->id . "\" title=\"" . $messageSummary . "\"" . $linkStyle . ">" . $template->name . "</a>" . $customText . $statusLabel, $editLink, $deleteLink );
    }
    echo "<div id=\"" . $type . "EmailTemplates\">";
    echo $aInt->sortableTable(array( $aInt->lang("fields", "status"), $aInt->lang("emailtpls", "tplname"), "", "" ), $tabledata);
    echo "</div>";
}

    $messages = AdminLang::trans("emailtpls.messages");
    echo "<div class=\"row\">\n    <div class=\"col-md-6\">";
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typegeneral")) . " " . $messages . "</h2>";
    outputEmailTpls("general");
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typeinvoice")) . " " . $messages . "</h2>";
    outputEmailTpls("invoice");
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typesupport")) . " " . $messages . "</h2>";
    outputEmailTpls("support");
    echo "<h2>" . ucfirst(AdminLang::trans("emailtpls.typeNotification")) . " " . $messages . "</h2>";
    outputEmailTpls("notification");
    echo "    </div>\n    <div class=\"col-md-6\">";
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typeproduct")) . " " . $messages . "</h2>";
    outputEmailTpls("product");
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typedomain")) . " " . $messages . "</h2>";
    outputEmailTpls("domain");
    echo "<h2>" . ucfirst($aInt->lang("emailtpls", "typeadmin")) . " " . $messages . "</h2>";
    outputEmailTpls("admin");
    $otherTypes = array_unique(WHMCS\Mail\Template::whereNotIn("type", array( "general", "product", "domain", "invoice", "support", "admin", "notification" ))->orderBy("type")->pluck("type")->all());
    foreach( $otherTypes as $type ) 
    {
        echo "<h2>" . ucfirst($aInt->lang("emailtpls", "type" . $type)) . " " . $aInt->lang("emailtpls", "messages") . "</h2>";
        outputEmailTpls($type);
    }
    echo "    </div>\n</div>\n<div style=\"clear:both;\"></div>\n\n<br />\n\n";
    if( checkPermission("Manage Email Template Languages", true) ) 
    {
        echo "<div class=\"contextbar\">\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\" class=\"form-inline\">\n<div style=\"display:inline;padding-right:20px;\">\n<b>";
        echo $aInt->lang("emailtpls", "activelang");
        echo ":</b> ";
        echo $aInt->lang("global", "default");
        foreach( $activelanguages as $language ) 
        {
            echo ", " . ucfirst($language);
        }
        echo "</div>\n<div style=\"display:inline;padding-right:20px;\">\n<b>";
        echo $aInt->lang("global", "add");
        echo ":</b> <select name=\"addlang\" class=\"form-control\">";
        $availlangs = WHMCS\Language\ClientLanguage::getLanguages();
        foreach( $availlangs as $lang ) 
        {
            if( in_array($lang, $activelanguages->all()) ) 
            {
                continue;
            }

            echo "<option value=\"" . $lang . "\">" . ucfirst($lang) . "</option>";
        }
        echo "</select> <input type=\"submit\" name=\"addlanguage\" value=\"";
        echo $aInt->lang("global", "submit");
        echo "\" class=\"button btn btn-default\" />\n</div>\n<div style=\"display:inline;\">\n<b>";
        echo $aInt->lang("global", "disable");
        echo ":</b> <select name=\"dislang\" class=\"form-control\"><option value=\"xxx\">";
        echo $aInt->lang("emailtpls", "chooseone");
        echo "</option>\n";
        foreach( $activelanguages as $lang ) 
        {
            echo "<option value=\"" . $lang . "\">" . ucfirst($lang) . "</option>";
        }
        echo "</select> <input type=\"submit\" name=\"disablelanguage\" value=\"";
        echo $aInt->lang("global", "submit");
        echo "\" class=\"button btn btn-default\" />\n</div>\n</form>\n</div>\n";
    }

    echo "\n";
}
else
{
    if( $action == "edit" ) 
    {
        $template = WHMCS\Mail\Template::find($id);
        if( $plaintextchange ) 
        {
            if( $template->plaintext ) 
            {
                $template->message = str_replace("\n\n", "</p><p>", $template->message);
                $template->message = str_replace("\n", "<br>", $template->message);
                $template->plaintext = false;
                $template->save();
            }
            else
            {
                $template->message = str_replace("<p>", "", $template->message);
                $template->message = str_replace("</p>", "\n\n", $template->message);
                $template->message = str_replace("<br>", "\n", $template->message);
                $template->message = str_replace("<br />", "\n", $template->message);
                $template->message = strip_tags($template->message);
                $template->plaintext = true;
                $template->save();
            }

            logAdminActivity("Email Template Plain Text Toggled: '" . $template->name . "' - Template ID: " . $id);
        }

        if( $template->plaintext ) 
        {
            $noeditor = true;
        }

        $jquerycode = "\$(\"#addfileupload\").click(function () {\n    \$(\"#fileuploads\").append(\"<input type=\\\"file\\\" name=\\\"attachments[]\\\" class=\\\"form-control top-margin-5\\\" />\");\n    return false;\n});";
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?savemessage=true&id=";
        echo $template->id;
        echo "\" enctype=\"multipart/form-data\">\n<input type=\"hidden\" name=\"editorstate\" value=\"";
        echo $noeditor;
        echo "\" />\n<p><b>";
        echo $template->name;
        echo "</b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("emails", "from");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"fromname\" class=\"form-control input-inline input-200\" value=\"";
        if( $template->fromName == "" ) 
        {
            echo $CONFIG["CompanyName"];
        }
        else
        {
            echo $template->fromName;
        }

        echo "\" data-enter-submit=\"true\" />\n        <input type=\"text\" name=\"fromemail\" class=\"form-control input-inline input-400\" value=\"";
        if( $template->fromEmail == "" ) 
        {
            echo $CONFIG["Email"];
        }
        else
        {
            echo $template->fromEmail;
        }

        echo "\" data-enter-submit=\"true\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("emailtpls", "copyto");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"copyto\" class=\"form-control input-inline input-400\" value=\"";
        echo implode(",", $template->copyTo);
        echo "\" data-enter-submit=\"true\" />\n        ";
        echo $aInt->lang("emailtpls", "commasep");
        echo "    </td>\n</tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
        echo AdminLang::trans("emailtpls.bcc");
        echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"bcc\" class=\"form-control input-inline input-400\" value=\"";
        echo implode(",", $template->blindCopyTo);
        echo "\" data-enter-submit=\"true\" />\n            ";
        echo AdminLang::trans("emailtpls.commasep");
        echo "        </td>\n    </tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("support", "attachments");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div id=\"fileuploads\">\n";
        $hasAttachments = false;
        if( $template->attachments ) 
        {
            foreach( $template->attachments as $i => $attachment ) 
            {
                if( empty($attachment) ) 
                {
                    continue;
                }

                $filename = substr($attachment, 7);
                echo "<div class=\"email-attachment\">\n            <i class=\"fa fa-file-o\"></i>\n            " . $filename . "\n            &nbsp;\n            <a href=\"configemailtemplates.php?action=delatt&id=" . $template->id . "&i=" . $i . generate_token("link") . "\" title=\"" . $aInt->lang("global", "delete") . "\" class=\"btn btn-danger btn-xs\">\n                <i class=\"fa fa-remove\"></i>\n            </a>\n        </div>";
                $hasAttachments = true;
            }
        }

        if( !$hasAttachments ) 
        {
            echo "<input type=\"file\" name=\"attachments[]\" class=\"form-control\" />";
        }

        echo "        </div>\n        <div class=\"top-margin-5\">\n            <a href=\"configemailtemplates.php#\" id=\"addfileupload\" class=\"btn btn-default btn-xs\">\n                <i class=\"fa fa-plus-circle\"></i>\n                ";
        echo $aInt->lang("support", "addmore");
        echo "            </a>\n        </div>\n    </td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("emailtpls", "plaintext");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"plaintext\" value=\"1\"";
        if( $template->plaintext ) 
        {
            echo " checked";
        }

        echo " onClick=\"window.location='configemailtemplates.php?action=edit&id=";
        echo $template->id;
        echo "&plaintextchange=true'\">\n            ";
        echo $aInt->lang("emailtpls", "plaintextinfo");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("global", "disable");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"disabled\"";
        if( $template->disabled ) 
        {
            echo " checked";
        }

        echo " data-enter-submit=\"true\">\n            ";
        echo $aInt->lang("emailtpls", "disableinfo");
        echo "        </label>\n    </td>\n</tr>\n</table>\n<br>\n";
        $activelanguages = array_unique(WHMCS\Mail\Template::where("language", "!=", "")->orderBy("type")->pluck("language")->all());
        $noLanguageTemplate = WHMCS\Mail\Template::where("type", "=", $template->type)->where("name", "=", $template->name)->where("language", "=", "")->get()->first();
        $default_subject = WHMCS\Input\Sanitize::makeSafeForOutput($noLanguageTemplate->subject);
        $default_message = WHMCS\Input\Sanitize::makeSafeForOutput($noLanguageTemplate->message);
        $defaultVersionExp = sprintf($aInt->lang("emailtpls", "defaultversionexp"), ucfirst($CONFIG["Language"]));
        $jquerycode .= "\$(\"input[data-enter-submit]\").keypress(function(event) {\n    if ( event.which == 13 ) {\n        event.preventDefault();\n        \$(\"#savechanges\").click();\n    }\n});\n";
        $templateTop = "<div style=\"float:right;\">\n    <input type=\"submit\" name=\"toggleeditor\" value=\"" . $aInt->lang("emailtpls", "rteditor") . "\" class=\"btn btn-sm\" />\n</div>\n<b>" . $aInt->lang("emailtpls", "defaultversion") . "</b> - " . $defaultVersionExp . "<br />\n<br />\nSubject: <input type=\"text\" name=\"subject[" . $noLanguageTemplate->id . "]\" class=\"form-control input-inline input-700\" value=\"" . $default_subject . "\" data-enter-submit=\"true\" /><br />\n<br />";
        echo $templateTop;
        echo "<textarea name=\"message[";
        echo $noLanguageTemplate->id;
        echo "]\" id=\"email_msg1\" rows=\"25\" class=\"tinymce form-control\">";
        echo $default_message;
        echo "</textarea><br>\n";
        $i = 2;
        foreach( $activelanguages as $language ) 
        {
            try
            {
                $languageTemplate = WHMCS\Mail\Template::where("type", "=", $template->type)->where("name", "=", $template->name)->where("language", "=", $language)->firstOrFail();
                $subject = WHMCS\Input\Sanitize::makeSafeForOutput($languageTemplate->subject);
                $message = WHMCS\Input\Sanitize::makeSafeForOutput($languageTemplate->message);
                $id = $languageTemplate->id;
            }
            catch( Exception $e ) 
            {
                $subject = $default_subject;
                $message = $default_message;
                $newTemplate = new WHMCS\Mail\Template();
                $newTemplate->type = $template->type;
                $newTemplate->name = $template->name;
                $newTemplate->language = $language;
                $newTemplate->subject = $noLanguageTemplate->subject;
                $newTemplate->message = $noLanguageTemplate->message;
                $newTemplate->save();
                $id = $newTemplate->id;
            }
            echo "<b>" . ucfirst($language) . " " . $aInt->lang("emailtpls", "version") . "</b><br><br>Subject: <input type=\"text\" name=\"subject[" . $id . "]\" class=\"form-control input-inline input-700\" value=\"" . $subject . "\"><br><br>";
            echo "<textarea name=\"message[";
            echo $id;
            echo "]\" id=\"email_msg";
            echo $i;
            echo "\" rows=\"25\" class=\"tinymce form-control\">";
            echo $message;
            echo "</textarea><br>\n";
            $i++;
        }
        $saveChanges = $aInt->lang("global", "savechanges");
        echo "<div class=\"btn-container\">\n    <input type=\"submit\" id=\"savechanges\" value=\"";
        echo $saveChanges;
        echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" onClick=\"window.location='";
        echo $whmcs->getPhpSelf();
        echo "'\" class=\"btn btn-default\" />\n</div>\n</form>\n\n";
        if( !$plaintext && !$noeditor ) 
        {
            $aInt->richTextEditor();
        }

        $type = $template->type;
        $name = $template->name;
        include("mergefields.php");
    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->jquerycode = $jquerycode;
$aInt->display();

