<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$reportdata["title"] = "Direct Debit Processing";
$reportdata["description"] = "This report displays all Unpaid invoices assigned to the Direct Debit payment method and the associated bank account details stored for their owners ready for processing";

$reportdata["tableheadings"] = array("Invoice ID","Client Name","Invoice Date","Due Date","Subtotal","Tax","Credit","Total","Bank Name","Bank Account Type","Bank Code","Bank Account Number");

$query = "SELECT tblinvoices.*,tblclients.firstname,tblclients.lastname,tblclients.bankname,tblclients.banktype,tblclients.bankcode,tblclients.bankacct FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.paymentmethod='directdebit' AND tblinvoices.status='Unpaid' ORDER BY duedate ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {

    $id = $data["id"];
    $userid = $data["userid"];
    $client = $data["firstname"]." ".$data["lastname"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"]+$data["tax2"];
    $total = $data["total"];
    $bankname = $data["bankname"];
    $banktype = $data["banktype"];
    $bankcode = $data["bankcode"];
    $bankacct = $data["bankacct"];

    $currency = getCurrency($userid);
    $date = fromMySQLDate($date);
    $duedate = fromMySQLDate($duedate);
    $subtotal = formatCurrency($subtotal);
    $credit = formatCurrency($credit);
    $tax = formatCurrency($tax);
    $total = formatCurrency($total);

    $reportdata["tablevalues"][] = array('<a href="invoices.php?action=edit&id='.$id.'">'.$id.'</a>',$client,$date,$duedate,$subtotal,$tax,$credit,$total,$bankname,$banktype,$bankcode,$bankacct);

}

$reportdata["footertext"] = "";

?>