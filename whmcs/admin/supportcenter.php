<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Support Center Overview");
$aInt->title = $aInt->lang("support", "supportoverview");
$aInt->sidebar = "support";
$aInt->icon = "support";
$aInt->helplink = "Support Center";
$aInt->requiredFiles(array( "ticketfunctions", "reportfunctions" ));
ob_start();
echo "\n<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n<div style=\"background-color:#f6f6f6;padding:5px 15px;\">Displaying Overview For: <select name=\"period\" class=\"form-control select-inline\" onchange=\"submit()\"><option>Today</option><option" . (($period == "Yesterday" ? " selected" : "")) . ">Yesterday</option><option" . (($period == "This Week" ? " selected" : "")) . ">This Week</option><option" . (($period == "This Month" ? " selected" : "")) . ">This Month</option><option" . (($period == "Last Month" ? " selected" : "")) . ">Last Month</option></select></div>\n</form>\n\n<div style=\"border:2px solid #f6f6f6;border-top:0;\">";
$chart = new WHMCSChart();
if( $period == "Yesterday" ) 
{
    $date = "date LIKE '" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"))) . "%'";
}
else
{
    if( $period == "This Week" ) 
    {
        $last_monday = strtotime("last monday");
        $next_sunday = strtotime("next sunday");
        $date = "date>='" . date("Y-m-d", $last_monday) . "' AND date<='" . date("Y-m-d", $next_sunday) . " 23:59:59'";
    }
    else
    {
        if( $period == "This Month" ) 
        {
            $date = "date LIKE '" . date("Y-m-") . "%'";
        }
        else
        {
            if( $period == "Last Month" ) 
            {
                $date = "date LIKE '" . date("Y-m-", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))) . "%'";
            }
            else
            {
                $date = "date LIKE '" . date("Y-m-d") . "%'";
            }

        }

    }

}

$newtickets = get_query_val("tbltickets", "COUNT(id)", (string) $date);
$clientreplies = get_query_val("tblticketreplies", "COUNT(id)", (string) $date . " AND admin=''");
$staffreplies = get_query_val("tblticketreplies", "COUNT(id)", (string) $date . " AND admin!=''");
$hours = array(  );
$maxHour = (!$period || $period == "Today" ? date("H") : 23);
for( $hour = 0; $hour <= $maxHour; $hour++ ) 
{
    $hours[str_pad($hour, 2, 0, STR_PAD_LEFT)] = 0;
}
$replytimes = array( 1 => "0", 2 => 0, 4 => "0", 8 => "0", 16 => "0", 24 => "0" );
$avefirstresponse = "0";
$avefirstresponsecount = "0";
$opennoreply = "0";
$result = full_query("SELECT id,date,(SELECT date FROM tblticketreplies WHERE tblticketreplies.tid=tbltickets.id AND admin!='' LIMIT 1) FROM tbltickets WHERE " . $date . " ORDER BY id ASC");
while( $data = mysql_fetch_array($result) ) 
{
    list($ticketid, $dateopened, $datefirstreply) = $data;
    $datehour = substr($dateopened, 11, 2);
    $hours[$datehour]++;
    if( !$datefirstreply ) 
    {
        $opennoreply++;
    }
    else
    {
        $timetofirstreply = strtotime($datefirstreply) - strtotime($dateopened);
        $timetofirstreply = round($timetofirstreply / (60 * 60), 2);
        $avefirstresponse += $timetofirstreply;
        $avefirstresponsecount++;
        if( $timetofirstreply <= 1 ) 
        {
            $replytimes[1]++;
        }
        else
        {
            if( 1 < $timetofirstreply && $timetofirstreply <= 4 ) 
            {
                $replytimes[2]++;
            }
            else
            {
                if( 4 < $timetofirstreply && $timetofirstreply <= 8 ) 
                {
                    $replytimes[4]++;
                }
                else
                {
                    if( 8 < $timetofirstreply && $timetofirstreply <= 16 ) 
                    {
                        $replytimes[8]++;
                    }
                    else
                    {
                        if( 16 < $timetofirstreply && $timetofirstreply <= 24 ) 
                        {
                            $replytimes[16]++;
                        }
                        else
                        {
                            $replytimes[24]++;
                        }

                    }

                }

            }

        }

    }

}
$avefirstresponse = (0 < $avefirstresponsecount ? round($avefirstresponse / $avefirstresponsecount, 2) : "-");
$avereplieschartdata = array(  );
$avereplieschartdata["cols"][] = array( "label" => "Timeframe", "type" => "string" );
$avereplieschartdata["cols"][] = array( "label" => "Number of Tickets", "type" => "number" );
if( 0 < $replytimes[1] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "0-1 Hours" ), array( "v" => $replytimes[1], "f" => $replytimes[1] ) ) );
}

if( 0 < $replytimes[2] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "1-4 Hours" ), array( "v" => $replytimes[2], "f" => $replytimes[2] ) ) );
}

if( 0 < $replytimes[4] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "4-8 Hours" ), array( "v" => $replytimes[4], "f" => $replytimes[2] ) ) );
}

if( 0 < $replytimes[8] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "8-16 Hours" ), array( "v" => $replytimes[8], "f" => $replytimes[8] ) ) );
}

if( 0 < $replytimes[16] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "16-24 Hours" ), array( "v" => $replytimes[16], "f" => $replytimes[16] ) ) );
}

if( 0 < $replytimes[24] ) 
{
    $avereplieschartdata["rows"][] = array( "c" => array( array( "v" => "24+ Hours" ), array( "v" => $replytimes[24], "f" => $replytimes[24] ) ) );
}

$averepliesargs = array(  );
$averepliesargs["title"] = "Average First Reply Time";
$averepliesargs["legendpos"] = "right";
$hourschartdata = array(  );
$hourschartdata["cols"][] = array( "label" => "Timeframe", "type" => "string" );
$hourschartdata["cols"][] = array( "label" => "Number of Tickets", "type" => "number" );
foreach( $hours as $hour => $count ) 
{
    $hourschartdata["rows"][] = array( "c" => array( array( "v" => $hour ), array( "v" => $count, "f" => $count ) ) );
}
$hoursargs = array(  );
$hoursargs["title"] = "Tickets Submitted by Hour";
$hoursargs["xlabel"] = "Number of Tickets Submitted";
$hoursargs["ylabel"] = "Hour";
$hoursargs["legendpos"] = "none";
echo "<style>\n.ticketstatbox {\n    margin: 20px 10px 0;\n    width: 150px;\n    padding: 20px;\n    font-size: 14px;\n    text-align: center;\n    background-color: #FEFAEB;\n    -moz-border-radius: 10px;\n    -webkit-border-radius: 10px;\n    -o-border-radius: 10px;\n    border-radius: 10px;\n}\n.ticketstatbox .stat {\n    font-size: 24px;\n    color: #000066;\n}\n</style>\n<table align=\"center\">\n<tr>\n<td>\n<div class=\"ticketstatbox\">\nNew Tickets\n<div class=\"stat\">" . $newtickets . "</div>\n</div>\n</td>\n<td>\n<div class=\"ticketstatbox\">\nClient Replies\n<div class=\"stat\">" . $clientreplies . "</div>\n</div>\n</td>\n<td>\n<div class=\"ticketstatbox\">\nStaff Replies\n<div class=\"stat\">" . $staffreplies . "</div>\n</div>\n</td>\n<td>\n<div class=\"ticketstatbox\">\nTickets Without Reply\n<div class=\"stat\">" . $opennoreply . "</div>\n</div>\n</td><td>\n<div class=\"ticketstatbox\">\nAverage First Response\n<div class=\"stat\">" . ((is_numeric($avefirstresponse) ? $avefirstresponse . " Hours" : "N/A")) . "</div>\n</div>\n</td>\n</tr>\n</table>";
echo "<table width=\"100%\"><tr><td width=\"40%\">";
echo $chart->drawChart("Pie", $avereplieschartdata, $averepliesargs, "500px", "100%");
echo "</td><td width=\"60%\">";
echo $chart->drawChart("Bar", $hourschartdata, $hoursargs, "600px", "100%");
echo "</td></tr></table></div>";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

