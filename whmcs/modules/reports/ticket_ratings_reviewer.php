<?php

use WHMCS\View\Markup\Markup;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

if (!$rating) {
    $rating = '1';
}
if (!$startdate) {
    $startdate = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))));
}
if (!$enddate) {
    $enddate = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
}

$rsel[$rating] = ' selected';

$markup = new Markup;

$query = "SELECT tblticketreplies.*,tbltickets.tid AS ticketid FROM tblticketreplies INNER JOIN tbltickets ON tbltickets.id=tblticketreplies.tid WHERE tblticketreplies.admin!='' AND tblticketreplies.rating='".(int)$rating."' AND tblticketreplies.date BETWEEN '".db_make_safe_human_date($startdate)."' AND '".db_make_safe_human_date($enddate)."' ORDER BY date DESC";
$result = full_query($query);
$num_rows = mysql_num_rows($result);

$reportdata["title"] = "Support Ticket Ratings Reviewer";
$reportdata["description"] = "This report is showing all $num_rows ticket replies rated $rating between $startdate & $enddate for review";

$reportdata["headertext"] = '<form method="post" action="reports.php?report=ticket_ratings_reviewer">
<p align="center"><b>Filter:</b> Rating: <select name="rating"><option' . $rsel[1] . '>1</option><option' . $rsel[2] . '>2</option><option' . $rsel[3] . '>3</option><option' . $rsel[4] . '>4</option><option' . $rsel[5] . '>5</option></select> Between Dates: <input type="text" name="startdate" value="' . $startdate . '" class="datepick" /> and <input type="text" name="enddate" value="' . $enddate . '" class="datepick" /> <input type="submit" value="Filter List" /></p>
</form>';

$reportdata["tableheadings"] = array(
    "Ticket #",
    "Date",
    "Message",
    "Admin",
    "Rating",
);

while ($data = mysql_fetch_array($result)) {
    $tid = $data["tid"];
    $ticketid = $data["ticketid"];
    $date = $data["date"];
    $message = $data["message"];
    $admin = $data["admin"];
    $rating = $data["rating"];
    $editor = $data["editor"];

    $date = fromMySQLDate($date, true);

    $markupFormat = $markup->determineMarkupEditor('ticket_reply', $editor);
    $message = $markup->transform($message, $markupFormat);

    $reportdata["tablevalues"][] = array(
        '<a href="supporttickets.php?action=viewticket&id='.$tid.'">' . $ticketid . '</a>',
        $date,
        '<div align="left">' . $message . '</div>',
        $admin,
        $rating,
    );
}
