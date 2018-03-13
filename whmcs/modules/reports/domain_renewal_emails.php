<?php

use WHMCS\Input\Sanitize;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!function_exists('getRegistrarsDropdownMenu')) {
    require(ROOTDIR . '/includes/registrarfunctions.php');
}

$whmcs = App::self();

$reportdata["title"] = $aInt->lang('reports', 'domainRenewalEmailsTitle');

$userID = $whmcs->get_req_var('userid');
$domain = $whmcs->get_req_var('domain');
$dateFrom = $whmcs->get_req_var('dateFrom') ? toMySQLDate($whmcs->get_req_var('dateFrom')) : '';
$dateTo = $whmcs->get_req_var('dateTo') ? toMySQLDate($whmcs->get_req_var('dateTo')) : '';
$formDateFrom = $dateFrom ? fromMySQLDate($dateFrom) : '';
$formDateTo = $dateTo ? fromMySQLDate($dateTo) : '';
$registrar = $whmcs->get_req_var('registrar');
$print = $whmcs->get_req_var('print');
/**
 * Replace the "None" string with the "Any" string
 */
$registrarList = str_replace(
    $aInt->lang('global', 'none'),
    $aInt->lang('global', 'any'),
    getRegistrarsDropdownMenu($registrar)
);

$reportdata["description"] = $aInt->lang('reports', 'domainRenewalEmailsDescription');

$reportHeader = '';
if (!$print) {
    $reportHeader = <<<REPORT_HEADER
<form method="post" action="reports.php?report=domain_renewal_emails">
{$aInt->lang('fields', 'clientid')}: {$aInt->clientsDropDown($userID, '', 'userid', true)}
{$aInt->lang('fields', 'domain')}: <input type="text" name="domain" value="{$domain}" size="30" />
{$aInt->lang('fields', 'registrar')}: {$registrarList}
{$aInt->lang('fields', 'daterange')}:
<input type="text" name="dateFrom" value="{$formDateFrom}" class="datepick" />
&nbsp;&nbsp;-> <input type="text" name="dateTo" value="{$formDateTo}" class="datepick" />
&nbsp;&nbsp;({$aInt->lang('reports', 'leaveBlankAll')})&nbsp;
<input type="submit" value="{$aInt->lang('global', 'filter')}" />
</form>
REPORT_HEADER;
}
$reportdata["headertext"] = $reportHeader;

$reportdata["tableheadings"] = array(
    $aInt->lang('fields', 'client'),
    $aInt->lang('fields', 'domain'),
    $aInt->lang('fields', 'dateSent'),
    $aInt->lang('domains', 'reminder'),
    $aInt->lang('emails', 'recipients'),
    $aInt->lang('domains', 'sent'),
);

$typeMap = array(
    1 => $aInt->lang('domains', 'firstReminder'),
    2 => $aInt->lang('domains', 'secondReminder'),
    3 => $aInt->lang('domains', 'thirdReminder'),
    4 => $aInt->lang('domains', 'fourthReminder'),
    5 => $aInt->lang('domains', 'fifthReminder'),
);

# Report Footer Text - this gets displayed below the report table of data
$data["footertext"] = "";

$table = "tbldomainreminders";
$fields = "tbldomainreminders.id AS reminder_id,
           tbldomainreminders.date,
           tbldomainreminders.type,
           tbldomainreminders.days_before_expiry,
           tbldomainreminders.recipients,
           tblclients.firstname,
           tblclients.lastname,
           tblclients.companyname,
           tbldomains.domain
";
$sort = "reminder_id";
$sortOrder = "DESC";
$join = "tbldomains ON (tbldomainreminders.domain_id = tbldomains.id) "
    . "JOIN tblclients ON (tbldomains.userid = tblclients.id)";

$where = array();
if ($userID) {
    $where['tblclients.id'] = (int) $userID;
}
if ($domain) {
    $where['tbldomains.domain'] = Sanitize::encode($domain);
}
if ($dateFrom && !$dateTo) {
    $where['date'] = array('sqltype' => '>=', 'value' => str_replace('-', '', $dateFrom));
}
if ($dateTo && !$dateFrom) {
    $where['date'] = array('sqltype' => '<=', 'value' => str_replace('-', '', $dateTo));
}
if ($registrar) {
    $where['tbldomains.registrar'] = $registrar;
}

$result = select_query($table, $fields, $where, $sort, $sortOrder, '', $join);
while (($data = mysql_fetch_array($result))) {
    if ((($dateFrom && $dateFrom != '') && ($dateTo && $dateTo != ''))) {
        $from = new DateTime(str_replace('-', '', $dateFrom));
        $to = new DateTime(str_replace('-', '', $dateTo));
        $dbDate = new DateTime(str_replace('-', '', $data['date']));
        if (($from > $dbDate) || ($to < $dbDate)) {
            continue;
        }
    }
    $client = sprintf(
        '%s %s%s',
        $data['firstname'],
        $data['lastname'],
        ($data['companyname']) ? " ({$data['companyname']})": '');
    $domain = $data['domain'];
    $date = $data['date'];
    $type = $typeMap[$data['type']];
    $recipients = $data['recipients'];
    $days_before_expiry = sprintf($aInt->lang('domains', 'beforeExpiry'), $data['days_before_expiry']);
    if ($data['days_before_expiry'] < 0) {
        $days_before_expiry = sprintf($aInt->lang('domains', 'afterExpiry'), $data['days_before_expiry'] * -1);
    }
    $reportdata["tablevalues"][] = array($client, $domain, $date, $type, $recipients, $days_before_expiry);
}
