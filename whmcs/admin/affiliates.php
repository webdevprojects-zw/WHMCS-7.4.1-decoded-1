<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Manage Affiliates");
$aInt->title = $aInt->lang("affiliates", "title");
$aInt->sidebar = "clients";
$aInt->icon = "affiliates";
$aInt->helplink = "Affiliates";
$aInt->requiredFiles(array( "invoicefunctions", "gatewayfunctions" ));
if( $action == "save" ) 
{
    check_token("WHMCS.admin.default");
    update_query("tblaffiliates", array( "paytype" => $paymenttype, "payamount" => $payamount, "onetime" => $onetime, "visitors" => $visitors, "balance" => $balance, "withdrawn" => $withdrawn ), array( "id" => $id ));
    logActivity("Affiliate ID " . $id . " Details Updated");
    redir("action=edit&id=" . $id);
}

if( $action == "deletecommission" ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblaffiliatespending", array( "id" => $cid ));
    redir("action=edit&id=" . $id);
}

if( $action == "deletehistory" ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblaffiliateshistory", array( "id" => $hid ));
    redir("action=edit&id=" . $id);
}

if( $action == "deletereferral" ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblaffiliatesaccounts", array( "id" => $affaccid ));
    redir("action=edit&id=" . $id);
}

if( $action == "deletewithdrawal" ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblaffiliateswithdrawals", array( "id" => $wid ));
    redir("action=edit&id=" . $id);
}

if( $action == "addcomm" ) 
{
    check_token("WHMCS.admin.default");
    $amount = format_as_currency($amount);
    insert_query("tblaffiliateshistory", array( "affiliateid" => $id, "date" => toMySQLDate($date), "affaccid" => $refid, "description" => $description, "amount" => $amount ));
    update_query("tblaffiliates", array( "balance" => "+=" . $amount ), array( "id" => (int) $id ));
    redir("action=edit&id=" . $id);
}

if( $action == "withdraw" ) 
{
    check_token("WHMCS.admin.default");
    insert_query("tblaffiliateswithdrawals", array( "affiliateid" => $id, "date" => "now()", "amount" => $amount ));
    update_query("tblaffiliates", array( "balance" => "-=" . $amount, "withdrawn" => "+=" . $amount ), array( "id" => (int) $id ));
    if( $payouttype == "1" ) 
    {
        $result = select_query("tblaffiliates", "", array( "id" => (int) $id ));
        $data = mysql_fetch_array($result);
        $id = (int) $data["id"];
        $clientid = (int) $data["clientid"];
        addTransaction($clientid, "", "Affiliate Commissions Withdrawal Payout", "0", "0", $amount, $paymentmethod, $transid);
    }
    else
    {
        if( $payouttype == "2" ) 
        {
            $result = select_query("tblaffiliates", "", array( "id" => (int) $id ));
            $data = mysql_fetch_array($result);
            $id = (int) $data["id"];
            $clientid = (int) $data["clientid"];
            insert_query("tblcredit", array( "clientid" => $clientid, "date" => "now()", "description" => "Affiliate Commissions Withdrawal", "amount" => $amount ));
            update_query("tblclients", array( "credit" => "+=" . $amount ), array( "id" => $clientid ));
            full_query($query);
            logActivity("Processed Affiliate Commissions Withdrawal to Credit Balance - User ID: " . $clientid . " - Amount: " . $amount);
        }

    }

    redir("action=edit&id=" . $id);
}

if( $sub == "delete" ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblaffiliates", array( "id" => $ide ));
    logActivity("Affiliate " . $ide . " Deleted");
    redir();
}

ob_start();
if( $action == "" ) 
{
    $aInt->sortableTableInit("clientname", "ASC");
    $query = "FROM `tblaffiliates` INNER JOIN tblclients ON tblclients.id=tblaffiliates.clientid WHERE tblaffiliates.id!=''";
    if( $client ) 
    {
        $query .= " AND concat(firstname,' ',lastname) LIKE '%" . db_escape_string($client) . "%'";
    }

    if( $visitors ) 
    {
        $visitorstype = ($visitorstype == "greater" ? ">" : "<");
        $query .= " AND visitors " . $visitorstype . " '" . db_escape_string($visitors) . "'";
    }

    if( $balance ) 
    {
        $balancetype = ($balancetype == "greater" ? ">" : "<");
        $query .= " AND balance " . $balancetype . " '" . db_escape_string($balance) . "'";
    }

    if( $withdrawn ) 
    {
        $withdrawntype = ($withdrawntype == "greater" ? ">" : "<");
        $query .= " AND withdrawn " . $withdrawntype . " '" . db_escape_string($withdrawn) . "'";
    }

    $result = full_query("SELECT COUNT(tblaffiliates.id) " . $query);
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
    $aInt->deleteJSConfirm("doDelete", "affiliates", "deletesure", "affiliates.php?sub=delete&ide=");
    echo $aInt->beginAdminTabs(array( $aInt->lang("global", "searchfilter") ));
    echo "\n<form action=\"";
    echo $whmcs->getPhpSelf();
    echo "\" method=\"get\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "clientname");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"client\" class=\"form-control input-250\" value=\"";
    echo $client;
    echo "\"></td><td width=\"10%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "balance");
    echo "</td><td class=\"fieldarea\"><select name=\"balancetype\" class=\"form-control select-inline\"><option value=\"greater\">";
    echo $aInt->lang("affiliates", "greaterthan");
    echo "<option>";
    echo $aInt->lang("affiliates", "lessthan");
    echo "</select> <input type=\"text\" name=\"balance\" class=\"form-control input-100 input-inline\" value=\"";
    echo $balance;
    echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("affiliates", "visitorsref");
    echo "</td><td class=\"fieldarea\"><select name=\"visitorstype\" class=\"form-control select-inline\"><option value=\"greater\">";
    echo $aInt->lang("affiliates", "greaterthan");
    echo "<option>";
    echo $aInt->lang("affiliates", "lessthan");
    echo "</select> <input type=\"text\" name=\"visitors\" class=\"form-control input-100 input-inline\" value=\"";
    echo $visitors;
    echo "\"></td><td class=\"fieldlabel\">";
    echo $aInt->lang("affiliates", "withdrawn");
    echo "</td><td class=\"fieldarea\"><select name=\"withdrawntype\" class=\"form-control select-inline\"><option value=\"greater\">";
    echo $aInt->lang("affiliates", "greaterthan");
    echo "<option>";
    echo $aInt->lang("affiliates", "lessthan");
    echo "</select> <input type=\"text\" name=\"withdrawn\" class=\"form-control input-100 input-inline\" value=\"";
    echo $withdrawn;
    echo "\"></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "search");
    echo "\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
    echo $aInt->endAdminTabs();
    echo "\n<br>\n\n";
    if( !($orderby == "id" || $orderby == "date" || $orderby == "clientname" || $orderby == "visitors" || $orderby == "balance" || $orderby == "withdrawn") ) 
    {
        $orderby = "clientname";
    }

    $query .= " ORDER BY ";
    $query .= ($orderby == "clientname" ? "tblclients.firstname " . $order . ",tblclients.lastname" : $orderby);
    $query .= " " . $order;
    $query = "SELECT tblaffiliates.*,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid,tblclients.currency,(SELECT COUNT(*) FROM tblaffiliatesaccounts WHERE tblaffiliatesaccounts.affiliateid=tblaffiliates.id) AS signups " . $query . " LIMIT " . (int) ($page * $limit) . "," . (int) $limit;
    $result = full_query($query);
    while( $data = mysql_fetch_array($result) ) 
    {
        $id = $data["id"];
        $date = $data["date"];
        $userid = $data["clientid"];
        $visitors = $data["visitors"];
        $balance = $data["balance"];
        $withdrawn = $data["withdrawn"];
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $companyname = $data["companyname"];
        $groupid = $data["groupid"];
        $currency = $data["currency"];
        $signups = $data["signups"];
        $currency = getCurrency("", $currency);
        $balance = formatCurrency($balance);
        $withdrawn = formatCurrency($withdrawn);
        $date = fromMySQLDate($date);
        $tabledata[] = array( "<input type=\"checkbox\" name=\"selectedclients[]\" value=\"" . $id . "\" class=\"checkall\" />", "<a href=\"affiliates.php?action=edit&id=" . $id . "\">" . $id . "</a>", $date, $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid), $visitors, $signups, $balance, $withdrawn, "<a href=\"?action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>" );
    }
    $tableformurl = "sendmessage.php?type=affiliate&multiple=true";
    $tableformbuttons = "<input type=\"submit\" value=\"" . $aInt->lang("global", "sendmessage") . "\" class=\"button btn btn-default\">";
    echo $aInt->sortableTable(array( "checkall", array( "id", $aInt->lang("fields", "id") ), array( "date", $aInt->lang("affiliates", "signupdate") ), array( "clientname", $aInt->lang("fields", "clientname") ), array( "visitors", $aInt->lang("affiliates", "visitorsref") ), $aInt->lang("affiliates", "signups"), array( "balance", $aInt->lang("fields", "balance") ), array( "withdrawn", $aInt->lang("affiliates", "withdrawn") ), "", "" ), $tabledata, $tableformurl, $tableformbuttons);
}
else
{
    if( $action == "edit" ) 
    {
        if( $pay == "true" ) 
        {
            $error = AffiliatePayment($affaccid, "");
            if( $error ) 
            {
                infoBox($aInt->lang("affiliates", "paymentfailed"), $error);
            }
            else
            {
                infoBox($aInt->lang("affiliates", "paymentsuccess"), $aInt->lang("affiliates", "paymentsuccessdetail"));
            }

        }

        echo $infobox;
        $result = select_query("tblaffiliates", "", array( "id" => $id ));
        $data = mysql_fetch_array($result);
        $id = $data["id"];
        if( !$id ) 
        {
            $aInt->gracefulExit("Invalid Affiliate ID. Please Try Again...");
        }

        $date = $data["date"];
        $affiliateClientID = $data["clientid"];
        $visitors = $data["visitors"];
        $balance = $data["balance"];
        $withdrawn = $data["withdrawn"];
        $paymenttype = $data["paytype"];
        $payamount = $data["payamount"];
        $onetime = $data["onetime"];
        $result = select_query("tblclients", "", array( "id" => $affiliateClientID ));
        $data = mysql_fetch_array($result);
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $result = select_query("tblaffiliatesaccounts", "COUNT(id)", array( "affiliateid" => $id ));
        $data = mysql_fetch_array($result);
        $signups = $data[0];
        $result = select_query("tblaffiliatespending", "COUNT(*),SUM(tblaffiliatespending.amount)", array( "affiliateid" => $id ), "clearingdate", "DESC", "", "tblaffiliatesaccounts ON tblaffiliatesaccounts.id=tblaffiliatespending.affaccid INNER JOIN tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid");
        $data = mysql_fetch_array($result);
        list($pendingcommissions, $pendingcommissionsamount) = $data;
        $currency = getCurrency($affiliateClientID);
        $date = fromMySQLDate($date);
        $pendingcommissionsamount = formatCurrency($pendingcommissionsamount);
        $conversionrate = round($signups / $visitors * 100, 2);
        $aInt->deleteJSConfirm("doAccDelete", "affiliates", "refdeletesure", "affiliates.php?action=deletereferral&id=" . $id . "&affaccid=");
        $aInt->deleteJSConfirm("doPendingCommissionDelete", "affiliates", "pendeletesure", "affiliates.php?action=deletecommission&id=" . $id . "&cid=");
        $aInt->deleteJSConfirm("doAffHistoryDelete", "affiliates", "pytdeletesure", "affiliates.php?action=deletehistory&id=" . $id . "&hid=");
        $aInt->deleteJSConfirm("doWithdrawHistoryDelete", "affiliates", "witdeletesure", "affiliates.php?action=deletewithdrawal&id=" . $id . "&wid=");
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=save&id=";
        echo $id;
        echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "id");
        echo "</td><td class=\"fieldarea\">";
        echo $id;
        echo "</td><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "signupdate");
        echo "</td><td class=\"fieldarea\">";
        echo $date;
        echo "</td></tr>\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "clientname");
        echo "</td><td class=\"fieldarea\"><a href=\"clientssummary.php?userid=";
        echo $affiliateClientID;
        echo "\">";
        echo (string) $firstname . " " . $lastname;
        echo "</a></td><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "pendingcommissions");
        echo "</td><td class=\"fieldarea\">";
        echo $pendingcommissionsamount;
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "commissiontype");
        echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"paymenttype\" value=\"\"";
        if( $paymenttype == "" ) 
        {
            echo " checked";
        }

        echo "> ";
        echo $aInt->lang("affiliates", "usedefault");
        echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"paymenttype\" value=\"percentage\"";
        if( $paymenttype == "percentage" ) 
        {
            echo " checked";
        }

        echo "> ";
        echo $aInt->lang("affiliates", "percentage");
        echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"paymenttype\" value=\"fixed\"";
        if( $paymenttype == "fixed" ) 
        {
            echo " checked";
        }

        echo "> ";
        echo $aInt->lang("affiliates", "fixedamount");
        echo "</label></td><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "availablebalance");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"balance\" size=10 value=\"";
        echo $balance;
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "commissionamount");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"payamount\" size=10 value=\"";
        echo $payamount;
        echo "\"> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"onetime\" id=\"onetime\" value=\"1\"";
        if( $onetime ) 
        {
            echo " checked";
        }

        echo " /> Pay One Time Only</label></td><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "withdrawnamount");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"withdrawn\" size=10 value=\"";
        echo $withdrawn;
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "visitorsref");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"visitors\" size=5 value=\"";
        echo $visitors;
        echo "\"></td><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "conversionrate");
        echo "</td><td class=\"fieldarea\">";
        echo $conversionrate;
        echo "%</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
        echo $aInt->beginAdminTabs(array( $aInt->lang("affiliates", "referredsignups"), $aInt->lang("affiliates", "pendingcommissions") . " (" . $pendingcommissions . ")", $aInt->lang("affiliates", "commissionshistory"), $aInt->lang("affiliates", "withdrawalshistory") ), true);
        $aInt->sortableTableInit("regdate", "DESC");
        $tabledata = array(  );
        $mysql_errors = true;
        $numrows = get_query_val("tblaffiliatesaccounts", "COUNT(*)", array( "tblaffiliatesaccounts.affiliateid" => $id ), "", "", "", "tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid");
        if( !($orderby == "id" || $orderby == "regdate" || $orderby == "clientname" || $orderby == "name" || $orderby == "lastpaid" || $orderby == "domainstatus") ) 
        {
            $orderby = "regdate";
        }

        $result = select_query("tblaffiliatesaccounts", "tblaffiliatesaccounts.id,tblaffiliatesaccounts.lastpaid,tblaffiliatesaccounts.relid, concat(tblclients.firstname,' ',tblclients.lastname,'|||',tblclients.currency) as clientname,tblproducts.name,tblhosting.userid,tblhosting.domainstatus,tblhosting.domain,tblhosting.amount,tblhosting.firstpaymentamount,tblhosting.regdate,tblhosting.billingcycle", array( "tblaffiliatesaccounts.affiliateid" => $id ), (string) $orderby, (string) $order, $page * $limit . "," . $limit, "tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid");
        while( $data = mysql_fetch_array($result) ) 
        {
            $affaccid = $data["id"];
            $lastpaid = $data["lastpaid"];
            $relid = $data["relid"];
            $clientname = $data["clientname"];
            $clientname = explode("|||", $clientname, 2);
            list($clientname, $referralCurrency) = $clientname;
            $userid = $data["userid"];
            $firstpaymentamount = $data["firstpaymentamount"];
            $amount = $data["amount"];
            $domain = $data["domain"];
            $date = $data["regdate"];
            $product = $data["name"];
            $billingcycle = $data["billingcycle"];
            $status = $data["domainstatus"];
            $currency = getCurrency($affiliateClientID);
            $commission = calculateAffiliateCommission($id, $relid, $lastpaid);
            $commission = formatCurrency($commission);
            $currency = getCurrency("", $referralCurrency);
            if( $billingcycle == "Free" || $billingcycle == "Free Account" ) 
            {
                $amountdesc = "Free";
            }
            else
            {
                if( $billingcycle == "One Time" ) 
                {
                    $amountdesc = formatCurrency($firstpaymentamount) . " " . $billingcycle;
                }
                else
                {
                    $amountdesc = ($firstpaymentamount != $amount ? formatCurrency($firstpaymentamount) . " " . $aInt->lang("affiliates", "initiallythen") . " " : "");
                    $amountdesc .= formatCurrency($amount) . " " . $billingcycle;
                }

            }

            $date = fromMySQLDate($date);
            if( !$domain ) 
            {
                $domain = "";
            }

            if( $lastpaid == "0000-00-00" ) 
            {
                $lastpaid = $aInt->lang("affiliates", "never");
            }
            else
            {
                $lastpaid = fromMySQLDate($lastpaid);
            }

            $tabledata[] = array( $affaccid, $date, "<a href=\"clientssummary.php?userid=" . $userid . "\">" . $clientname . "</a>", "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $product . "</a><br>" . $amountdesc, $commission, $lastpaid, $status, "<a href=\"affiliates.php?action=edit&id=" . $id . "&pay=true&affaccid=" . $affaccid . "\">" . $aInt->lang("affiliates", "manual") . "<br>" . $aInt->lang("affiliates", "payout") . "</a>", "<a href=\"#\" onClick=\"doAccDelete('" . $affaccid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
        }
        echo $aInt->sortableTable(array( array( "id", $aInt->lang("fields", "id") ), array( "regdate", $aInt->lang("affiliates", "signupdate") ), array( "clientname", $aInt->lang("fields", "clientname") ), array( "name", $aInt->lang("fields", "product") ), $aInt->lang("affiliates", "commission"), array( "lastpaid", $aInt->lang("affiliates", "lastpaid") ), array( "domainstatus", $aInt->lang("affiliates", "productstatus") ), " ", "" ), $tabledata, $tableformurl, $tableformbuttons);
        echo $aInt->nextAdminTab();
        $currency = getCurrency($affiliateClientID);
        $aInt->sortableTableInit("nopagination");
        $tabledata = array(  );
        $result = select_query("tblaffiliatespending", "tblaffiliatespending.id,tblaffiliatespending.affaccid,tblaffiliatespending.amount,tblaffiliatespending.clearingdate,tblaffiliatesaccounts.relid,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblproducts.name,tblhosting.userid,tblhosting.domainstatus,tblhosting.billingcycle", array( "affiliateid" => $id ), "clearingdate", "ASC", "", "tblaffiliatesaccounts ON tblaffiliatesaccounts.id=tblaffiliatespending.affaccid INNER JOIN tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid");
        while( $data = mysql_fetch_array($result) ) 
        {
            $pendingid = $data["id"];
            $affaccid = $data["affaccid"];
            $amount = $data["amount"];
            $clearingdate = $data["clearingdate"];
            $relid = $data["relid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $userid = $data["userid"];
            $product = $data["name"];
            $billingcycle = $data["billingcycle"];
            $status = $data["domainstatus"];
            $clearingdate = fromMySQLDate($clearingdate);
            $amount = formatCurrency($amount);
            $tabledata[] = array( $affaccid, $aInt->outputClientLink($userid, $firstname, $lastname, $companyname), "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $product . "</a>", $status, $amount, $clearingdate, "<a href=\"#\" onClick=\"doPendingCommissionDelete('" . $pendingid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
        }
        echo $aInt->sortableTable(array( $aInt->lang("affiliates", "refid"), $aInt->lang("fields", "clientname"), $aInt->lang("fields", "product"), $aInt->lang("affiliates", "productstatus"), $aInt->lang("fields", "amount"), $aInt->lang("affiliates", "clearingdate"), "" ), $tabledata);
        echo $aInt->nextAdminTab();
        $aInt->sortableTableInit("nopagination");
        $tabledata = array(  );
        $result = select_query("tblaffiliateshistory", "tblaffiliateshistory.*,(SELECT CONCAT(tblclients.id,'|||',tblclients.firstname,'|||',tblclients.lastname,'|||',tblclients.companyname,'|||',tblproducts.name,'|||',tblhosting.id,'|||',tblhosting.billingcycle,'|||',tblhosting.domainstatus) FROM tblaffiliatesaccounts INNER JOIN tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid WHERE tblaffiliatesaccounts.id=tblaffiliateshistory.affaccid) AS referraldata", array( "affiliateid" => $id ), "date", "DESC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $historyid = $data["id"];
            $date = $data["date"];
            $affaccid = $data["affaccid"];
            $description = $data["description"];
            $amount = $data["amount"];
            $referraldata = $data["referraldata"];
            $referraldata = explode("|||", $referraldata);
            $userid = $firstname = $lastname = $companyname = $product = $relid = $billingcycle = $status = "";
            if( $affaccid ) 
            {
                list($userid, $firstname, $lastname, $companyname, $product, $relid, $billingcycle, $status) = $referraldata;
            }

            $date = fromMySQLDate($date);
            $amount = formatCurrency($amount);
            if( !$description ) 
            {
                $description = "&nbsp;";
            }

            $tabledata[] = array( $date, $affaccid, $aInt->outputClientLink($userid, $firstname, $lastname, $companyname), "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $product . "</a>", $status, $description, $amount, "<a href=\"#\" onClick=\"doAffHistoryDelete('" . $historyid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
        }
        echo $aInt->sortableTable(array( $aInt->lang("fields", "date"), $aInt->lang("affiliates", "refid"), $aInt->lang("fields", "clientname"), $aInt->lang("fields", "product"), $aInt->lang("affiliates", "productstatus"), "Description", $aInt->lang("fields", "amount"), "" ), $tabledata);
        echo "\n<br />\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=addcomm&id=";
        echo $id;
        echo "\">\n<p align=\"left\"><b>Add Manual Commission Entry</b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "date");
        echo ":</td><td class=\"fieldarea\"><input type=\"text\" name=\"date\" value=\"";
        echo getTodaysDate();
        echo "\" class=\"datepick\" /></td></tr>\n<tr><td class=\"fieldlabel\">Related Referral:</td><td class=\"fieldarea\"><select name=\"refid\" class=\"form-control select-inline\"><option value=\"\">None</option>";
        $result = select_query("tblaffiliatesaccounts", "tblaffiliatesaccounts.*,(SELECT CONCAT(tblclients.firstname,'|||',tblclients.lastname,'|||',tblhosting.userid,'|||',tblproducts.name,'|||',tblhosting.domainstatus,'|||',tblhosting.domain,'|||',tblhosting.amount,'|||',tblhosting.regdate,'|||',tblhosting.billingcycle) FROM tblhosting INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid WHERE tblhosting.id=tblaffiliatesaccounts.relid) AS referraldata", array( "affiliateid" => $id ));
        while( $data = mysql_fetch_array($result) ) 
        {
            $affaccid = $data["id"];
            $lastpaid = $data["lastpaid"];
            $relid = $data["relid"];
            $referraldata = $data["referraldata"];
            $referraldata = explode("|||", $referraldata);
            list($firstname, $lastname, $userid, $product, $status, $domain, $amount, $date, $billingcycle) = $referraldata;
            if( !$domain ) 
            {
                $domain = "";
            }

            if( $lastpaid == "0000-00-00" ) 
            {
                $lastpaid = $aInt->lang("affiliates", "never");
            }
            else
            {
                $lastpaid = fromMySQLDate($lastpaid);
            }

            echo "<option value=\"" . $affaccid . "\">ID " . $affaccid . " - " . $firstname . " " . $lastname . " - " . $product . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo ":</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" size=\"45\" /> (Optional)</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "amount");
        echo ":</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" size=\"10\" value=\"0.00\" /></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "submit");
        echo "\" class=\"btn btn-primary\" />\n</div>\n</form>\n\n";
        echo $aInt->nextAdminTab();
        $aInt->sortableTableInit("nopagination");
        $tabledata = array(  );
        $result = select_query("tblaffiliateswithdrawals", "", array( "affiliateid" => $id ), "id", "DESC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $historyid = $data["id"];
            $date = $data["date"];
            $amount = $data["amount"];
            $date = fromMySQLDate($date);
            $amount = formatCurrency($amount);
            $tabledata[] = array( $date, $amount, "<a href=\"#\" onClick=\"doWithdrawHistoryDelete('" . $historyid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
        }
        echo $aInt->sortableTable(array( $aInt->lang("fields", "date"), $aInt->lang("fields", "amount"), "" ), $tabledata);
        echo "\n<br />\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=withdraw&id=";
        echo $id;
        echo "\">\n<p align=\"left\"><b>";
        echo $aInt->lang("affiliates", "makepayout");
        echo "</b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "amount");
        echo ":</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" size=\"10\" value=\"";
        echo $balance;
        echo "\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "payouttype");
        echo ":</td><td class=\"fieldarea\"><select name=\"payouttype\" class=\"form-control select-inline\"><option value=\"1\">";
        echo $aInt->lang("affiliates", "transactiontoclient");
        echo "</option><option value=\"2\">";
        echo $aInt->lang("affiliates", "addtocredit");
        echo "</option><option>";
        echo $aInt->lang("affiliates", "withdrawalsonly");
        echo "</option></select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "transid");
        echo ":</td><td class=\"fieldarea\"><input type=\"text\" name=\"transid\" size=\"25\" /> (";
        echo $aInt->lang("affiliates", "transactiontoclientinfo");
        echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "paymentmethod");
        echo ":</td><td class=\"fieldarea\">";
        echo paymentMethodsSelection($aInt->lang("global", "na"));
        echo "</td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "submit");
        echo "\" class=\"btn btn-primary\" />\n</div>\n</form>\n\n";
        echo $aInt->endAdminTabs();
    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

