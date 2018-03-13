<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Ticket Feedback Scores";
$reportdata["description"] = "This report provides a summary of scores received on a per staff member basis for a given date range";

if (!$fromdate) $fromdate = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))));
if (!$todate) $todate = getTodaysDate();

$reportdata["headertext"] = "<form method=\"post\" action=\"".''.$_SERVER['PHP_SELF'].'?'.((isset($_REQUEST['module']))?'module='.$_REQUEST['module'].'&':'').''."report=$report&currencyid=$currencyid&calculate=true\"><center>Start Date: <input type=\"text\" name=\"fromdate\" value=\"$fromdate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; End Date: <input type=\"text\" name=\"todate\" value=\"$todate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"Generate Report\" /></form>";

$reportdata["tableheadings"][] = "Staff Name";
for ( $rating = 1; $rating <= 10; $rating++ ) $reportdata["tableheadings"][] = $rating;
$reportdata["tableheadings"][] = "Total Ratings";
$reportdata["tableheadings"][] = "Average Rating";

$adminnames = $ratingstats = array();

$result = select_query("tblticketfeedback","(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblticketfeedback.adminid) AS adminname,adminid,rating,COUNT(*)","adminid>0 AND datetime>='".db_make_safe_human_date($fromdate)."' AND datetime<='".db_make_safe_human_date($todate)." 23:59:59' GROUP BY `rating`, `adminid`","adminname","ASC");
while ($data = mysql_fetch_array($result)) {
    $adminname = $data[0];
    $adminid = $data[1];
    $rating = $data[2];
    $count = $data[3];
    $adminnames[$adminid] = $adminname;
    $ratingstats[$adminid][$rating] = $count;
}

foreach ($adminnames AS $adminid=>$adminname) {

    $rowtotal = $rowcount = 0;

    $row = array();
    $row[] = '<a href="'.$_SERVER['PHP_SELF'].'?'.((isset($_REQUEST['module']))?'module='.$_REQUEST['module'].'&':'').'report=ticket_feedback_comments&'.((isset($_REQUEST['module']))?'module='.$_REQUEST['module'].'&':'').'staffid='.$adminid.'">'.$adminname.'</a>';

    for ( $rating = 1; $rating <= 10; $rating++ ) {

        $count = $ratingstats[$adminid][$rating];
        $row[] = $count;

        $rowcount += $count;
        $rowtotal += $count*$rating;

    }

    $average = round($rowtotal/$rowcount,2);

    $row[] = $rowcount;
    $row[] = $average;

    $reportdata["tablevalues"][] = $row;

    $chartdata['rows'][] = array('c'=>array(array('v'=>$adminname),array('v'=>$average,'f'=>$average)));

}

$chartdata['cols'][] = array('label'=>'Staff Name','type'=>'string');
$chartdata['cols'][] = array('label'=>'Average Rating','type'=>'number');

$args = array();
$args['colors'] = '#F9D88C,#3070CF';
$args['minyvalue'] = '0';
$args['maxyvalue'] = '10';
$args['gridlinescount'] = '11';
$args['minorgridlinescount'] = '3';
$args['ylabel'] = 'Average Rating';
$args['xlabel'] = 'Staff Name';
$args['legendpos'] = 'none';

$reportdata["headertext"] .= $chart->drawChart('Column',$chartdata,$args,'500px');
