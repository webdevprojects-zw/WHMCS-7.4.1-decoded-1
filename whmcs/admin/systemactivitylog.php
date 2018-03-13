<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View Activity Log");
$aInt->title = $aInt->lang("system", "activitylog");
$aInt->sidebar = "utilities";
$aInt->icon = "logs";
ob_start();
echo $aInt->beginAdminTabs(array( $aInt->lang("global", "searchfilter") ));
echo "\n<form method=\"post\" action=\"systemactivitylog.php\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "date");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"date\" value=\"";
echo $whmcs->get_req_var("date");
echo "\" class=\"datepick\"></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "username");
echo "</td><td class=\"fieldarea\"><select name=\"username\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$query = "SELECT DISTINCT user FROM tblactivitylog ORDER BY user ASC";
$result = full_query($query);
while( $data = mysql_fetch_array($result) ) 
{
    $user = $data["user"];
    echo "<option";
    if( $user == $whmcs->get_req_var("username") ) 
    {
        echo " selected";
    }

    echo ">" . $user . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "description");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" value=\"";
echo $whmcs->get_req_var("description");
echo "\" size=\"80\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "ipaddress");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ipaddress\" value=\"";
echo $whmcs->get_req_var("ipaddress");
echo "\" size=\"20\"></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("system", "filterlog");
echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br />\n\n";
$aInt->sortableTableInit("date");
$log = new WHMCS\Log\Activity();
$log->prune();
$log->setCriteria(array( "date" => $whmcs->get_req_var("date"), "username" => $whmcs->get_req_var("username"), "description" => $whmcs->get_req_var("description"), "ipaddress" => $whmcs->get_req_var("ipaddress") ));
$numrows = $log->getTotalCount();
$tabledata = array(  );
$logs = $log->getLogEntries($whmcs->get_req_var("page"));
foreach( $logs as $entry ) 
{
    $tabledata[] = array( $entry["date"], "<div align=\"left\">" . $entry["description"] . "</div>", $entry["username"], $entry["ipaddress"] );
}
echo $aInt->sortableTable(array( $aInt->lang("fields", "date"), $aInt->lang("fields", "description"), $aInt->lang("fields", "username"), $aInt->lang("fields", "ipaddress") ), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

