<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

require(ROOTDIR."/includes/clientfunctions.php");

$reportdata["title"] = "Project Management Summary";
$reportdata["description"] = "This report shows a summary of all projects with times logged betwen";

if (!$datefrom) $datefrom = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))));
if (!$dateto) $dateto = getTodaysDate();

$statusdropdown = '<select name="status"><option value="">- Any -</option>';
$statuses = get_query_val("tbladdonmodules","value",array("module"=>"project_management","setting"=>"statusvalues"));;
$statuses = explode(",",$statuses);
foreach($statuses as $statusx) {
    $statusx = explode("|",$statusx,2);
    $statusdropdown .= '<option';
    if ($statusx[0]==$status) $statusdropdown .= ' selected';
    $statusdropdown .= '>'.$statusx[0].'</option>';
}
$statusdropdown .= '</status>';

$admindropdown = '<select name="adminid"><option value="0">- Any -</option>';
$result = select_query("tbladmins","id,firstname,lastname","","firstname` ASC,`lastname","ASC");
while ($data = mysql_fetch_array($result)) {
    $aid = $data['id'];
    $admindropdown .= '<option value="'.$aid.'"';
    if ($aid==$adminid) echo ' selected';
    $admindropdown .= '>'.$data['firstname'].' '.$data['lastname'].'</option>';
}
$admindropdown .= '</select>';

$reportdata["headertext"] = <<<EOF
<form method="post" action="$requeststr">
<table align="center">
<tr><td>Due Date Range - From</td><td><input type="text" name="datefrom" value="$datefrom" class="datepick" /></td><td width="20"></td><td>To</td><td><input type="text" name="dateto" value="$dateto" class="datepick" /></td><td width="20"></td><td>Filter by Status</td><td>$statusdropdown</td><td width="20"></td><td>Filter by Staff Member</td><td>$admindropdown</td><td width="20"></td><td><input type="submit" value="Submit" /></tr>
</table>
</form>
EOF;

$reportdata["tableheadings"] = array("ID","Created","Project Title","Assigned Staff","Associated Client","Due Date","Total Invoiced","Total Paid","Total Time","Status");

$totalprojectstime = $i = 0;

$adminquery = ($adminid) ? " AND adminid='".(int)$adminid."'" : '';
$statusquery = ($status) ? " AND status='".db_escape_string($status)."'" : '';
$result = select_query("mod_project","","duedate>='".toMySQLDate($datefrom)."' AND duedate<='".toMySQLDate($dateto)."'".$adminquery.$statusquery);
while($data = mysql_fetch_array($result)) {

    $totaltaskstime = 0;
    $projectid = $data["id"];
    $projectname = $data["title"];
    $adminid = $data["adminid"];
    $userid = $data["userid"];
    $created = $data["created"];
    $duedate = $data["duedate"];
    $ticketids = $data["ticketids"];
    $projectstatus = $data["status"];

    $created = fromMySQLDate($created);
    $duedate = fromMySQLDate($duedate);

    $admin = ($adminid) ? getAdminName($adminid) : 'None';

    if ($userid) {
        $clientsdetails = getClientsDetails($userid);
        $client = '<a href="clientssummary.php?userid='.$clientsdetails['userid'].'">'.$clientsdetails['firstname'].' '.$clientsdetails['lastname'];
        if($clientsdetails['companyname']) $client .= ' ('.$clientsdetails['companyname'].')';
        $client .= '</a>';
        $currency = getCurrency();
    } else {
        $client = 'None';
    }

    $ticketinvoicelinks = array();
    foreach ($ticketids AS $i=>$ticketnum) {
        if ($ticketnum) {
            $ticketnum = get_query_val("tbltickets","tid",array("tid"=>$ticketnum));
            $ticketinvoicelinks[] = "description LIKE '%Ticket #$ticketnum%'";
        }
    }
    $ticketinvoicesquery = (!empty($ticketinvoicelinks)) ? "(\".implode(' AND ',$ticketinvoicelinks).\") OR " : '';
    $totalinvoiced = get_query_val("tblinvoices","SUM(subtotal+tax+tax2)","id IN (SELECT invoiceid FROM tblinvoiceitems WHERE description LIKE '%Project #$projectid%' OR $ticketinvoicesquery (type='Project' AND relid='$projectid'))");
    $totalinvoiced = ($userid) ? formatCurrency($totalinvoiced) : format_as_currency($totalinvoiced);

    $totalpaid = get_query_val("tblinvoices","SUM(subtotal+tax+tax2)","id IN (SELECT invoiceid FROM tblinvoiceitems WHERE description LIKE '%Project #$projectid%' OR $ticketinvoicesquery (type='Project' AND relid='$projectid')) AND status='Paid'");
    $totalpaid = ($userid) ? formatCurrency($totalpaid) : format_as_currency($totalpaid);

    $reportdata["drilldown"][$i]["tableheadings"] = array("Task Name","Start Time","Stop Time","Duration","Task Status");

    $timerresult = select_query("mod_projecttimes","mod_projecttimes.start,mod_projecttimes.end,mod_projecttasks.task,mod_projecttasks.completed",array("mod_projecttimes.projectid"=>$projectid),"","","","mod_projecttasks ON mod_projecttimes.taskid = mod_projecttasks.id");
    while ($data2=mysql_fetch_assoc($timerresult)) {

        $rowcount = $rowtotal = 0;

        $taskid = $data2['id'];
        $task = $data2['task'];
        $taskadminid = $data2['adminid'];
        $timerstart = $data2['start'];
        $timerend = $data2['end'];
        $duration = ($timerend) ? ($timerend-$timerstart) : 0;

        $taskadmin = getAdminName($taskadminid);
        $starttime = date("d/m/Y H:i:s ",$timerstart);
        $stoptime = date("d/m/Y H:i:s ",$timerend);
        $taskstatus = ($data2['completed']) ? "Completed" : "Open";

        $totalprojectstime += $duration;
        $totaltaskstime += $duration;

        $rowcount++;
        $rowtotal += $ordertotal;

        $reportdata["drilldown"][$i]["tablevalues"][] = array($task,$starttime,$stoptime,project_management_sec2hms($duration),$taskstatus);

    }

    $reportdata["tablevalues"][$i] = array('<a href="addonmodules.php?module=project_management&m=view&projectid='.$projectid.'">'.$projectid.'</a>',$created,$projectname,$admin,$client,$duedate,$totalinvoiced,$totalpaid,project_management_sec2hms($totaltaskstime),$projectstatus);

    $i++;


}

$reportdata["footertext"] = "Total Time effort across $i projects: ".project_management_sec2hms($totalprojectstime);

function project_management_sec2hms ($sec, $padHours = false){

    if ($sec <= 0) {
        $sec = 0;
    }
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= ($padHours) ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":" : $hours. ":";
    $minutes = intval(($sec / 60) % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT);

    return $hms;

}
