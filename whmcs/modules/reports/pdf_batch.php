<?php

use WHMCS\Invoices;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Batch PDF Invoice Export";
$reportdata["description"] = <<<DESCRIPTION
This tool can be used to generate and download a batch export of invoices in PDF format (one per page).<br />
Typical uses for this include producing hard paper copies for mailing to clients or record keeping.
DESCRIPTION;

require("../includes/gatewayfunctions.php");

    if ($noresults) {
        infoBox("No Invoices Match Criteria","No invoices were found matching the criteria you specified");
        $reportdata["description"] .= $infobox;
    }

$reportdata["headertext"] = '

<form method="post" action="csvdownload.php?type=pdfbatch">

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
<tr><td width="20%" class="fieldlabel">Client Name</td><td class="fieldarea">'.$aInt->clientsDropDown($userid, false, 'userid', true).'</td></tr>
<tr><td class="fieldlabel">Filter By</td><td class="fieldarea"><select name="filterby"><option>Date Created</option><option>Due Date</option><option>Date Paid</option></select></td></tr>
<tr><td class="fieldlabel">Date Range</td><td class="fieldarea"><input type="text" name="datefrom" value="'.fromMySQLDate(date("Y-m-d",mktime(0,0,0,date("m")-1,date("d"),date("Y")))).'" class="datepick" /> &nbsp;&nbsp; to &nbsp;&nbsp; <input type="text" name="dateto" value="'.fromMySQLDate(date("Y-m-d")).'" class="datepick" /></td></tr>
<tr><td class="fieldlabel">Payment Methods</td><td class="fieldarea"><select name="paymentmethods[]" size="8" multiple="true">';
    $result = select_query("tblpaymentgateways","gateway,value",array("setting"=>"name"),"order","ASC");
    while($data = mysql_fetch_array($result)) {
        $dbcongateway = $data["gateway"];
        $dbconvalue = $data["value"];
        $reportdata["headertext"] .= '<option value="'.$dbcongateway.'" selected>'.$dbconvalue.'</option>';
    }
    $reportdata["headertext"] .= '</select>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">Statuses</td>
        <td class="fieldarea">
            <select name="statuses[]" size="6" multiple="true">';
    foreach (Invoices::getInvoiceStatusValues() as $invoiceStatusOption) {
        $isSelected = ($invoiceStatusOption == 'Unpaid');
        $reportdata["headertext"] .= '<option value="' . $invoiceStatusOption . '"'
            . ($isSelected ? ' selected' : '')
            . '>'
            . $aInt->lang('status', strtolower($invoiceStatusOption))
            . '</option>';
    }
    $reportdata["headertext"] .= '</select>
        </td>
    </tr>
<tr><td class="fieldlabel">Sort Order</td><td class="fieldarea"><select name="sortorder"><option>Invoice ID</option><option>Invoice Number</option><option>Date Paid</option><option>Due Date</option><option>Client ID</option><option>Client Name</option></select></td></tr>
</table>

<p align=center><input type="submit" value="Download File" class="button"></p>

</form>';

$report = '';

