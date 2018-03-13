<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Ticket Tags Overview";
$reportdata["description"] = "This report provides an overview of ticket tags assigned to tickets for a given date range";

if (!$fromdate) $fromdate = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m")-1,date("d"),date("Y"))));
if (!$todate) $todate = getTodaysDate();

$reportdata["headertext"] = "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?report=$report\"><center>Start Date: <input type=\"text\" name=\"fromdate\" value=\"$fromdate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; End Date: <input type=\"text\" name=\"todate\" value=\"$todate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"Generate Report\" /></form>";

$reportdata["tableheadings"][] = "Tag";
$reportdata["tableheadings"][] = "Count";

$result = full_query("SELECT `tag`, COUNT(*) AS `count` FROM `tbltickettags` INNER JOIN tbltickets ON tbltickets.id=tbltickettags.ticketid WHERE tbltickets.date>='".db_make_safe_human_date($fromdate)." 00:00:00' AND tbltickets.date<='".db_make_safe_human_date($todate)." 23:59:59' GROUP BY tbltickettags.tag ORDER BY `count` DESC");
while ($data = mysql_fetch_array($result)) {
    $tag = $data[0];
    $count = $data[1];

    $reportdata["tablevalues"][] = array($tag,$count);

    $chartdata['rows'][] = array('c'=>array(array('v'=>$tag),array('v'=>(int)$count,'f'=>$count)));

}

$chartdata['cols'][] = array('label'=>'Tag','type'=>'string');
$chartdata['cols'][] = array('label'=>'Count','type'=>'number');

$args = array();
$args['legendpos'] = 'right';

$reportdata["headertext"] .= $chart->drawChart('Pie',$chartdata,$args,'300px');
