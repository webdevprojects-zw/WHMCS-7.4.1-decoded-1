<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Promotions Usage Report";
$reportdata["description"] = "This report shows usage statistics for each promotional code.";

if (!$datefrom) $datefrom = fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))));
if (!$dateto) $dateto = fromMySQLDate(date("Y-m-d"));

$reportdata["headertext"] = <<<EOF
<form method="post" action="reports.php?report=$report">
<table align="center">
<tr><td>Date From:</td><td><input type="text" name="datefrom" value="$datefrom" class="datepick" /></td><td width="20"></td><td>Date To:</td><td><input type="text" name="dateto" value="$dateto" class="datepick" /></td><td width="20"></td><td><input type="submit" value="Submit" /></tr>
</table>
</form>
EOF;

$reportdata["tableheadings"] = array("Coupon Code","Discount Type","Value","Recurring","Notes","Usage Count","Total Revenue");

$i = 0;

$result = select_query("tblpromotions","","","code","ASC");
while($data = mysql_fetch_array($result)) {

    $code = $data["code"];
    $type = $data["type"];
    $value = $data["value"];
    $recurring = $data["recurring"];
    $notes = $data["notes"];

    $rowcount = $rowtotal = 0;

    $reportdata["drilldown"][$i]["tableheadings"] = array("Order ID","Order Date","Order Number","Order Total","Order Status");

    $result2 = select_query("tblorders","","promocode='".db_escape_string($code)."' AND date>='".db_make_safe_human_date($datefrom)."' AND date<='".db_make_safe_human_date($dateto)."'","id","ASC");
    while ($data = mysql_fetch_array($result2)) {

        $orderid = $data['id'];
        $ordernum = $data['ordernum'];
        $orderdate = $data['date'];
        $ordertotal = $data['amount'];
        $orderstatus = $data['status'];

        $rowcount++;
        $rowtotal += $ordertotal;

        $reportdata["drilldown"][$i]["tablevalues"][] = array('<a href="orders.php?action=view&id='.$orderid.'">'.$orderid.'</a>',fromMySQLDate($orderdate),$ordernum,$ordertotal,$orderstatus);

    }

    $reportdata["tablevalues"][$i] = array($code,$type,$value,$recurring,$notes,$rowcount,format_as_currency($rowtotal));

    $i++;

}

?>