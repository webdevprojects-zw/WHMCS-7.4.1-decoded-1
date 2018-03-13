<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Credits Reviewer";
$reportdata["description"] = "This report allows you to review all the credits issued to clients between 2 dates you specify";

$reportdata["headertext"] = '<form method="post" action="?report='.$report.'">
<p align="center">Start Date: <input type="text" name="startdate" value="'.$startdate.'" class="datepick" /> End Date: <input type="text" name="enddate" value="'.$enddate.'" class="datepick" /> <input type="submit" value="Generate Report"></p>
</form>';

$reportdata["tableheadings"] = array("Credit ID","Client Name","Date","Description","Amount");

if ($startdate && $enddate) {

$query = "SELECT tblcredit.*,tblclients.firstname,tblclients.lastname FROM tblcredit INNER JOIN tblclients ON tblclients.id=tblcredit.clientid WHERE tblcredit.date BETWEEN '".db_make_safe_human_date($startdate)."' AND '".db_make_safe_human_date($enddate)."'";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["clientid"];
    $clientname = $data["firstname"]." ".$data["lastname"];
    $date = fromMySQLDate($data["date"]);
    $description = $data["description"];
    $amount = $data["amount"];
    $currency = getCurrency($userid);
    $amount = formatCurrency($amount);
    $reportdata["tablevalues"][] = array($id,'<a href="clientssummary.php?userid='.$userid.'">'.$clientname.'</a>',$date,nl2br($description),$amount);
}

}

$data["footertext"] = '';

?>