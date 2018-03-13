<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Client Sources";
$reportdata["description"] = "This report provides a summary of the answers clients have given to the How Did You Find Us? or Where did you hear about us? custom field signup question";

if (!$fromdate) $fromdate = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))));
if (!$todate) $todate = getTodaysDate();

$customfieldid = get_query_val("tblcustomfields","id",array("type"=>"client","fieldname"=>"How did you find us?"));
if (!$customfieldid) $customfieldid = get_query_val("tblcustomfields","id",array("type"=>"client","fieldname"=>"Where did you hear about us?"));

if (!$customfieldid && isset($_REQUEST['fieldname']) && isset($_REQUEST['options'])) {
    check_token('WHMCS.admin.default');
    $customfieldid = insert_query("tblcustomfields",array("type"=>"client","fieldname"=>$_REQUEST['fieldname'],"fieldtype"=>"dropdown","fieldoptions"=>$_REQUEST['options'],"showorder"=>"on"));
}

if (!$customfieldid) $reportdata["headertext"] = '<div style="margin:50px auto;width:50%;padding:15px;border:1px dashed #ccc;text-align:center;font-size:14px;">This report requires you to setup a custom field shown during the signup process with a name of "How Did You Find Us?" or "Where did you hear about us?" in order to collect this data from customers.<br /><br />You don\'t appear to have the custom field setup yet so we can do this now:<br /><br /><form method="post" action="reports.php?report=client_sources">Field Name: <select name="fieldname"><option>How did you find us?</option><option>Where did you hear about us?</option></select><br />Options: <input type="text" name="options" value="Google,Bing,Other Search Engine,Web Hosting Talk,Friend,Advertisement,Other" style="width:70%;" /><br /><br /><input type="submit" value="Create &raquo;" class="btn btn-primary" /></form></div>';
else $reportdata["headertext"] = "<form method=\"post\" action=\"?report=$report&currencyid=$currencyid&calculate=true\"><center><strong>Signup Date Range</strong> &nbsp;&nbsp;&nbsp; Start Date: <input type=\"text\" name=\"fromdate\" value=\"$fromdate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; End Date: <input type=\"text\" name=\"todate\" value=\"$todate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"Generate Report\" /></form>";

$reportdata["tableheadings"][] = "Referral Location";
$reportdata["tableheadings"][] = "Count";

$result = select_query("tblcustomfieldsvalues","value,COUNT(*) AS `rows`","fieldid=".(int)$customfieldid." AND datecreated>='".db_make_safe_human_date($fromdate)."' AND datecreated<='".db_make_safe_human_date($todate)."' GROUP BY `value`","value","ASC","","tblclients ON tblclients.id=tblcustomfieldsvalues.relid");
while ($data = mysql_fetch_array($result)) {

    $reportdata["tablevalues"][] = array($data[0],$data[1]);

    $chartdata['rows'][] = array('c'=>array(array('v'=>$data[0]),array('v'=>$data[1],'f'=>$data[1])));

}

$chartdata['cols'][] = array('label'=>'Referral Location','type'=>'string');
$chartdata['cols'][] = array('label'=>'Count','type'=>'number');

$args = array();
$args['legendpos'] = 'right';

if ($customfieldid) $reportdata["footertext"] = $chart->drawChart('Pie',$chartdata,$args,'300px');
