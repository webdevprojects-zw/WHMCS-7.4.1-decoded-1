<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Ticket Feedback Comments";
$reportdata["description"] = "This report allows you to review feedback comments submitted by customers.";

if (!$fromdate) $fromdate = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))));
if (!$todate) $todate = getTodaysDate();

$reportdata["headertext"] = "<form method=\"post\" action=\"?".((isset($_REQUEST['module']))?'module='.$_REQUEST['module'].'&':'')."report=$report&currencyid=$currencyid&calculate=true\"><center>Staff Name: <select name=\"staffid\"><option value=\"0\">- Any -</option>";
$result = select_query("tbladmins","id,CONCAT(firstname,' ',lastname)","","firstname","ASC");
while ($data = mysql_fetch_array($result)) {
    $reportdata["headertext"] .= "<option value=\"".$data[0]."\"".(($data[0]==$staffid)?" selected":"").">".$data[1]."</option>";
}
$reportdata["headertext"] .= "</select> &nbsp;&nbsp;&nbsp; Start Date: <input type=\"text\" name=\"fromdate\" value=\"$fromdate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; End Date: <input type=\"text\" name=\"todate\" value=\"$todate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"Generate Report\" /></form>";

$reportdata["tableheadings"][] = "Ticket ID";
$reportdata["tableheadings"][] = "Staff Name";
$reportdata["tableheadings"][] = "Subject";
$reportdata["tableheadings"][] = "Feedback Left";
$reportdata["tableheadings"][] = "Rating";
$reportdata["tableheadings"][] = "Comments";
$reportdata["tableheadings"][] = "IP Address";

$result = select_query("tblticketfeedback","tblticketfeedback.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblticketfeedback.adminid) AS adminname,(SELECT CONCAT(tid,'|||',title) FROM tbltickets WHERE tbltickets.id=tblticketfeedback.ticketid) AS ticketinfo","datetime>='".db_make_safe_human_date($fromdate)."' AND datetime<='".db_make_safe_human_date($todate)." 23:59:59'".(($staffid)?" AND adminid=".(int)$staffid:""),"datetime","ASC");
while ($data = mysql_fetch_array($result)) {

    $id = $data['id'];
    $ticketid = $data['ticketid'];
    $ticketinfo = $data['ticketinfo'];
    $adminid = $data['adminid'];
    $adminname = $data['adminname'];
    $rating = $data['rating'];
    $comments = $data['comments'];
    $datetime = $data['datetime'];
    $ip = $data['ip'];

    if ($adminid==0) $adminname = 'Generic Feedback';
    elseif (!trim($adminname)) $adminname = 'Deleted Admin';

    if (!trim($comments)) $comments = 'No Comments Left';

    $datetime = fromMySQLDate($datetime,1);

    $ticketinfo = explode('|||',$ticketinfo);
    $tickettid = $ticketinfo[0];
    $subject = $ticketinfo[1];
    if (!$tickettid) $tickettid = 'Not Found';

    $reportdata["tablevalues"][] = array('<a href="supporttickets.php?action=viewticket&id='.$ticketid.'" target="_blank">'.$tickettid.'</a>',$adminname,$subject,$datetime,$rating,nl2br($comments),'<a href="http://www.geoiptool.com/en/?IP='.$ip.'" target="_blank">'.$ip.'</a>');

}