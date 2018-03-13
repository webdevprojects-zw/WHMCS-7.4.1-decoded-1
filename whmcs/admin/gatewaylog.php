<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View Gateway Log");
$aInt->title = $aInt->lang("gatewaytranslog", "gatewaytranslogtitle");
$aInt->sidebar = "billing";
$aInt->icon = "logs";
ob_start();
echo $aInt->beginAdminTabs(array( $aInt->lang("global", "searchfilter") ));
if( !count($_REQUEST) ) 
{
    $startdate = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y"))));
    $enddate = getTodaysDate();
}

echo "\n<form method=\"post\" action=\"gatewaylog.php\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "daterange");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"startdate\" value=\"";
echo $startdate;
echo "\" class=\"form-control date-picker\" /> ";
echo $aInt->lang("global", "to");
echo " &nbsp; <input type=\"text\" name=\"enddate\" value=\"";
echo $enddate;
echo "\" class=\"form-control date-picker\" /></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("gatewaytranslog", "gateway");
echo "</td><td class=\"fieldarea\"><select name=\"filtergateway\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$query = "SELECT DISTINCT gateway FROM tblgatewaylog ORDER BY gateway ASC";
$result = full_query($query);
while( $data = mysql_fetch_array($result) ) 
{
    $gateway = $data["gateway"];
    echo "<option";
    if( $gateway == $filtergateway ) 
    {
        echo " selected";
    }

    echo ">" . $gateway . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("gatewaytranslog", "debugdata");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"filterdebugdata\" class=\"form-control input-300\" value=\"";
echo $filterdebugdata;
echo "\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "result");
echo "</td><td class=\"fieldarea\"><select name=\"filterresult\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$query = "SELECT DISTINCT result FROM tblgatewaylog ORDER BY result ASC";
$result = full_query($query);
while( $data = mysql_fetch_array($result) ) 
{
    $resultval = $data["result"];
    echo "<option";
    if( $resultval == $filterresult ) 
    {
        echo " selected";
    }

    echo ">" . $resultval . "</option>";
}
echo "</select></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("gatewaytranslog", "filter");
echo "\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br />\n\n";
$aInt->sortableTableInit("id", "DESC");
$where = array(  );
if( $filterdebugdata ) 
{
    $where[] = "data LIKE '%" . db_escape_string(WHMCS\Input\Sanitize::decode($filterdebugdata)) . "%'";
}

if( $startdate ) 
{
    $where[] = "date>='" . toMySQLDate($startdate) . " 00:00:00'";
}

if( $enddate ) 
{
    $where[] = "date<='" . toMySQLDate($enddate) . " 23:59:59'";
}

if( $filtergateway ) 
{
    $where[] = "gateway='" . db_escape_string($filtergateway) . "'";
}

if( $filterresult ) 
{
    $where[] = "result='" . db_escape_string($filterresult) . "'";
}

$result = select_query("tblgatewaylog", "COUNT(*)", implode(" AND ", $where), "id", "DESC");
$data = mysql_fetch_array($result);
$numrows = $data[0];
$result = select_query("tblgatewaylog", "", implode(" AND ", $where), "id", "DESC", $page * $limit . "," . $limit);
while( $data = mysql_fetch_array($result) ) 
{
    $id = $data["id"];
    $date = $data["date"];
    $gateway = WHMCS\Input\Sanitize::makeSafeForOutput($data["gateway"]);
    $data2 = WHMCS\Input\Sanitize::makeSafeForOutput($data["data"]);
    $res = WHMCS\Input\Sanitize::makeSafeForOutput($data["result"]);
    $date = fromMySQLDate($date, "time");
    $tabledata[] = array( "<div class=\"text-center\">" . $date . "</div>", "<div class=\"text-center\">" . $gateway . "</div>", "<textarea rows=\"6\" class=\"form-control\">" . $data2 . "</textarea>", "<div class=\"text-center\"><strong>" . $res . "</strong></div>" );
}
echo $aInt->sortableTable(array( $aInt->lang("fields", "date"), $aInt->lang("gatewaytranslog", "gateway"), $aInt->lang("gatewaytranslog", "debugdata"), $aInt->lang("fields", "result") ), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->display();

