<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Sales Tax Liability";
$reportdata["description"] = "This report shows sales tax liability for the selected period";

$reportdata["currencyselections"] = true;

$query = "select year(min(date)) as minimum, year(max(date)) as maximum from tblaccounts;";
$result = full_query($query);
$data = mysql_fetch_array($result);
$minyear = $data['minimum'];
$maxyear = $data['maximum'];

if (!$startdate) {
    $startdate = fromMySQLDate(date('Y-m-d'));
}
if (!$enddate) {
    $enddate = fromMySQLDate(date('Y-m-d'));
}

$queryStartDate = db_make_safe_human_date($startdate);
$queryEndDate = db_make_safe_human_date($enddate);
$currencyID = (int) $currencyid;

$reportdata["headertext"] = "<form method=\"post\" action=\"?report=$report&currencyid=$currencyid&calculate=true\"><center>Start Date: <input type=\"text\" name=\"startdate\" value=\"$startdate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; End Date: <input type=\"text\" name=\"enddate\" value=\"$enddate\" class=\"datepick\" /> &nbsp;&nbsp;&nbsp; <input type=\"submit\" value=\"Generate Report\"></form>";

if ($calculate) {

    $query = <<<QUERY
SELECT COUNT(*), SUM(total), SUM(tblinvoices.credit), SUM(tax), SUM(tax2)
FROM tblinvoices
INNER JOIN tblclients ON tblclients.id = tblinvoices.userid
WHERE datepaid >= '{$queryStartDate}'
    AND datepaid <= '{$queryEndDate} 23:59:59'
    AND tblinvoices.status = 'Paid'
    AND currency = {$currencyID}
    AND (SELECT count(tblinvoiceitems.id)
        FROM tblinvoiceitems
        WHERE invoiceid = tblinvoices.id
            AND (type = 'AddFunds' OR type = 'Invoice')
        ) = 0;
QUERY;
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $numinvoices = $data[0];
    $total = $data[1] + $data[2];
    $tax = $data[3];
    $tax2 = $data[4];

    if (!$total) $total="0.00";
    if (!$tax) $tax="0.00";
    if (!$tax2) $tax2="0.00";

    $reportdata["headertext"] .= "<br>$numinvoices Invoices Found<br><B>Total Invoiced:</B> ".formatCurrency($total)." &nbsp; <B>Tax Level 1 Liability:</B> ".formatCurrency($tax)." &nbsp; <B>Tax Level 2 Liability:</B> ".formatCurrency($tax2);
}

$reportdata["headertext"] .= "</center>";

$reportdata["tableheadings"] = array(
    $aInt->lang('fields', 'invoiceid'),
    $aInt->lang('fields', 'clientname'),
    $aInt->lang('fields', 'invoicedate'),
    $aInt->lang('fields', 'datepaid'),
    $aInt->lang('fields', 'subtotal'),
    $aInt->lang('fields', 'tax'),
    $aInt->lang('fields', 'credit'),
    $aInt->lang('fields', 'total'),
);

$query = <<<QUERY
SELECT tblinvoices.*, tblclients.firstname, tblclients.lastname
FROM tblinvoices
INNER JOIN tblclients ON tblclients.id = tblinvoices.userid
WHERE datepaid >= '{$queryStartDate}'
    AND datepaid <= '{$queryEndDate} 23:59:59'
    AND tblinvoices.status = 'Paid'
    AND currency = {$currencyID}
    AND (SELECT count(tblinvoiceitems.id)
        FROM tblinvoiceitems
        WHERE invoiceid = tblinvoices.id
            AND (type = 'AddFunds' OR type = 'Invoice')
        ) = 0
ORDER BY date ASC;
QUERY;
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["userid"];
    $client = $data["firstname"]." ".$data["lastname"];
    $date = fromMySQLDate($data["date"]);
    $datepaid = fromMySQLDate($data["datepaid"]);
    $currency = getCurrency($userid);
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"]+$data["tax2"];
    $total = $data["total"] + $credit;
    $reportdata["tablevalues"][] = array("$id","$client","$date","$datepaid","$subtotal","$tax","$credit","$total");
}

$data["footertext"]="This report excludes invoices that affect a clients credit balance "
    . "since this income will be counted and reported when it is applied to invoices for products/services.";
