<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !$vars["clientenable"] ) 
{
    redir();
}

$whmcs = DI::make("app");
$tplvars = array(  );
$tplvars["_lang"] = $vars["_lang"];
$tplvars["features"] = $features = explode(",", $vars["clientfeatures"]);
$a = $_GET["a"];
if( !$a ) 
{
    $pageTitle = $vars["_lang"]["projectsoverview"];
    $tagline = $vars["_lang"]["projectsoverviewdesc"];
    $tplfile = "clienthome";
    $result = select_query("mod_project", "COUNT(*)", array( "userid" => $_SESSION["uid"] ));
    $data = mysql_fetch_array($result);
    $numitems = $data[0];
    list($orderby, $sort, $limit) = clientAreaTableInit("projects", "lastmodified", "DESC", $numitems);
    $projects = array(  );
    $result = select_query("mod_project", "", array( "userid" => $_SESSION["uid"] ), $orderby, $sort, $limit);
    while( $data = mysql_fetch_array($result) ) 
    {
        $projects[] = array( "id" => $data["id"], "title" => $data["title"], "adminid" => $data["adminid"], "adminname" => get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array( "id" => $data["adminid"] )), "created" => fromMySQLDate($data["created"], 0, 1), "duedate" => fromMySQLDate($data["duedate"], 0, 1), "lastmodified" => fromMySQLDate($data["lastmodified"], 0, 1), "status" => $data["status"] );
    }
    $tplvars["projects"] = $projects;
    $tplvars["orderby"] = $orderby;
    $tplvars["sort"] = strtolower($sort);
    $tplvars = array_merge($tplvars, clientAreaTablePageNav($numitems));
}
else
{
    if( $a == "view" ) 
    {
        $tplfile = "clientview";
        $result = select_query("mod_project", "", array( "userid" => $_SESSION["uid"], "id" => $_REQUEST["id"] ));
        $data = mysql_fetch_array($result);
        $projectid = (int) $data["id"];
        if( !$projectid ) 
        {
            exit( "Access Denied" );
        }

        if( in_array("addtasks", $features) && trim($_POST["newtask"]) ) 
        {
            check_token();
            insert_query("mod_projecttasks", array( "projectid" => $projectid, "task" => trim($_POST["newtask"]), "created" => "now()", "order" => get_query_val("mod_projecttasks", "`order`", array( "projectid" => $projectid ), "order", "DESC") + 1 ));
            redir("m=project_management&a=view&id=" . $projectid . "&taskadded=1");
        }

        if( in_array("files", $features) && $_POST["upload"] ) 
        {
            $attachmentsDirectory = $whmcs->getAttachmentsDir();
            $projectsdir2 = $attachmentsDirectory . DIRECTORY_SEPARATOR . "projects" . DIRECTORY_SEPARATOR;
            $projectsdir = $attachmentsDirectory . DIRECTORY_SEPARATOR . "projects" . DIRECTORY_SEPARATOR . $projectid . DIRECTORY_SEPARATOR;
            if( !is_dir($projectsdir2) ) 
            {
                mkdir($projectsdir2);
            }

            if( !file_exists($projectsdir2 . "index.php") ) 
            {
                $src = "<?php\nheader(\"Location: ../../index.php\");";
                try
                {
                    $file = new WHMCS\File($projectsdir2 . "index.php");
                    $file->create($src);
                }
                catch( Exception $e ) 
                {
                }
            }

            if( !is_dir($projectsdir) ) 
            {
                mkdir($projectsdir);
            }

            if( !file_exists($projectsdir . "index.php") ) 
            {
                $src = "<?php\nheader(\"Location: ../../../index.php\");";
                try
                {
                    $file = new WHMCS\File($projectsdir . "index.php");
                    $file->create($src);
                }
                catch( Exception $e ) 
                {
                }
            }

            if( isset($_FILES["attachments"]) ) 
            {
                $attachments = array(  );
                foreach( $_FILES["attachments"]["name"] as $num => $filename ) 
                {
                    try
                    {
                        $file = new WHMCS\File\Upload("attachments", $num);
                        if( !$file->checkExtension() ) 
                        {
                            redir("m=project_management&a=view&id=" . $projectid . "&uploadfailed=1");
                        }

                        $prefix = "{RAND}_";
                        $filename = $file->move($projectsdir, $prefix);
                        $attachments[] = $filename;
                        project_management_log($projectid, $vars["_lang"]["clientaddedattachment"] . " " . $file->getCleanName());
                    }
                    catch( WHMCS\Exception\File\NotUploaded $e ) 
                    {
                    }
                    catch( Exception $e ) 
                    {
                        if( is_object($aInt) ) 
                        {
                            $aInt->gracefulExit($e->getMessage());
                        }
                        else
                        {
                            redir("m=project_management&a=view&id=" . $projectid . "&uploadfailed=1");
                        }

                    }
                }
                if( 0 < count($attachments) ) 
                {
                    foreach( $attachments as $attachment ) 
                    {
                        $uploadedFile = new WHMCSProjectManagement\Models\ProjectFile();
                        $uploadedFile->projectId = $projectid;
                        $uploadedFile->filename = $attachment;
                        $uploadedFile->adminId = 0;
                        $uploadedFile->messageId = 0;
                        $uploadedFile->save();
                    }
                }

            }

            redir("m=project_management&a=view&id=" . $projectid . "&uploadsuccess=1");
        }

        global $currency;
        $currency = getCurrency($_SESSION["uid"]);
        $pageTitle = $data["title"];
        $breadcrumb["?m=project_management&a=view&id=" . $data["id"]] = $data["title"];
        $tplvars["taskAddSuccess"] = (bool) $whmcs->get_req_var("taskadded");
        $tplvars["fileUploadSuccess"] = (bool) $whmcs->get_req_var("uploadsuccess");
        $tplvars["fileUploadFailed"] = (bool) $whmcs->get_req_var("uploadfailed");
        $tplvars["fileUploadDisallowed"] = (bool) $whmcs->get_req_var("uploaddisallowed");
        $tplvars["project"] = array( "id" => $data["id"], "title" => $data["title"], "adminid" => $data["adminid"], "adminname" => get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array( "id" => $data["adminid"] )), "created" => fromMySQLDate($data["created"], 0, 1), "duedate" => fromMySQLDate($data["duedate"], 0, 1), "duein" => project_management_daysleft($data["duedate"], $vars), "lastmodified" => fromMySQLDate($data["lastmodified"], 0, 1), "totaltime" => $totaltime, "status" => $data["status"] );
        if( !$tplvars["project"]["adminname"] ) 
        {
            $tplvars["project"]["adminname"] = "None";
        }

        $ticketids = $data["ticketids"];
        $invoiceids = $data["invoiceids"];
        $tickets = $invoices = $ticketinvoicelinks = array(  );
        $ticketids = explode(",", $ticketids);
        foreach( $ticketids as $ticketnum ) 
        {
            if( $ticketnum ) 
            {
                $result = select_query("tbltickets", "id,tid,c,title,status,lastreply", array( "tid" => $ticketnum ));
                $data = mysql_fetch_array($result);
                $ticketid = $data["id"];
                if( $ticketid ) 
                {
                    $tickets[] = array( "tid" => $data["tid"], "c" => $data["c"], "title" => $data["title"], "status" => $data["status"], "lastreply" => $data["lastreply"] );
                    $ticketinvoicelinks[] = "description LIKE '%Ticket #" . $data["tid"] . "%'";
                }

            }

        }
        $tplvars["tickets"] = $tickets;
        $invoiceids = explode(",", $invoiceids);
        foreach( $invoiceids as $k => $invoiceid ) 
        {
            if( !$invoiceid ) 
            {
                unset($invoiceids[$k]);
            }

        }
        if( !function_exists("getGatewaysArray") ) 
        {
            require(ROOTDIR . "/includes/gatewayfunctions.php");
        }

        $gateways = getGatewaysArray();
        $ticketinvoicesquery = (!empty($ticketinvoicelinks) ? "(" . implode(" OR ", $ticketinvoicelinks) . ") OR " : "");
        $where = "id IN (SELECT invoiceid FROM tblinvoiceitems" . " WHERE description LIKE '%Project #" . $projectid . "%' OR " . $ticketinvoicesquery . " (type='Project' AND relid='" . $projectid . "'))";
        if( 0 < count($invoiceids) ) 
        {
            $where .= " OR id IN (" . db_build_in_array($invoiceids) . ")";
        }

        $result = select_query("tblinvoices", "", $where, "id", "ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $invoices[] = array( "id" => $data["id"], "date" => fromMySQLDate($data["date"], 0, 1), "duedate" => fromMySQLDate($data["duedate"], 0, 1), "datepaid" => fromMySQLDate($data["datepaid"], 0, 1), "total" => formatCurrency($data["total"]), "paymentmethod" => $gateways[$data["paymentmethod"]], "status" => $data["status"], "rawstatus" => strtolower($data["status"]) );
        }
        $tplvars["invoices"] = $invoices;
        $attachmentsArray = array(  );
        $attachments = WHMCSProjectManagement\Models\ProjectFile::where("message_id", 0)->where("project_id", $projectid)->get();
        foreach( $attachments as $attachment ) 
        {
            $filename = substr($attachment->filename, 7);
            if( $filename ) 
            {
                $attachmentsArray[$attachment->id] = array( "filename" => $filename );
            }

        }
        $tplvars["attachments"] = $attachmentsArray;
        $totaltimecount = 0;
        $i = 1;
        $tasks = array(  );
        for( $result = select_query("mod_projecttasks", "id,task,notes,adminid,created,duedate,completed", array( "projectid" => $projectid ), "order", "ASC"); $data = mysql_fetch_assoc($result); $i++ ) 
        {
            $tasks[$i] = $data;
            $tasks[$i]["adminname"] = ($data["adminid"] ? get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array( "id" => $data["adminid"] )) : "0");
            $tasks[$i]["duein"] = ($data["duedate"] != "0000-00-00" ? project_management_daysleft($data["duedate"], $vars) : "0");
            $tasks[$i]["duedate"] = ($data["duedate"] != "0000-00-00" ? fromMySQLDate($data["duedate"], 0, 1) : "0");
            $totaltasktime = 0;
            $result2 = select_query("mod_projecttimes", "", array( "projectid" => $projectid, "taskid" => $data["id"] ));
            while( $data = mysql_fetch_array($result2) ) 
            {
                $timerid = $data["id"];
                $timerstart = $data["start"];
                $timerend = $data["end"];
                $starttime = fromMySQLDate(date("Y-m-d H:i:s", $timerstart), 1, 1);
                $endtime = ($timerend ? fromMySQLDate(date("Y-m-d H:i:s", $timerend), 1, 1) : 0);
                $totaltime = ($timerend ? project_management_sec2hms($timerend - $timerstart) : 0);
                $tasks[$i]["times"][] = array( "id" => $data["id"], "adminid" => $data["adminid"], "adminname" => get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array( "id" => $data["adminid"] )), "start" => $starttime, "end" => $endtime, "duration" => $totaltime );
                if( $timerend ) 
                {
                    $totaltasktime += $timerend - $timerstart;
                }

            }
            $totaltimecount += $totaltasktime;
            $tasks[$i]["totaltime"] = project_management_sec2hms($totaltasktime);
        }
        $tplvars["tasks"] = $tasks;
        $totaltime = project_management_sec2hms($totaltimecount);
        $tplvars["project"]["totaltime"] = $totaltime;
        if( in_array("files", $features) ) 
        {
            $tplvars["allowedExtensions"] = $whmcs->get_config("TicketAllowedFileTypes");
        }

    }
    else
    {
        redir("m=project_management");
    }

}


