<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View Credit Log");
$aInt->title = $aInt->lang("credit", "creditmanagement");
ob_start();
$jQueryCode = "";
$currency = getCurrency($userid);
$result = select_query("tblclients", "firstname,lastname,credit", array( "id" => $userid ));
$data = mysql_fetch_array($result);
$name = stripslashes($data["firstname"] . " " . $data["lastname"]);
$creditbalance = $data["credit"];
$errorMessages = array(  );
if( $action == "" ) 
{
    $validateAmountOn = array( "add", "remove" );
    if( in_array($sub, $validateAmountOn) ) 
    {
        $validate = new WHMCS\Validate();
        if( !$validate->validate("decimal", "amount", array( "credit", "invalidAmountFormat" )) ) 
        {
            $errorMessages = array_merge($validate->getErrors());
        }

    }

    if( in_array($sub, array_merge($validateAmountOn, array( "save" ))) && !validateDateInput($date) ) 
    {
        $errorMessages[] = $aInt->lang("credit", "invalidDate");
    }

    if( empty($errorMessages) ) 
    {
        if( $sub == "add" ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Manage Credits");
            insert_query("tblcredit", array( "clientid" => $userid, "date" => toMySQLDate($date), "description" => $description, "amount" => $amount ));
            update_query("tblclients", array( "credit" => "+=" . $amount ), array( "id" => (int) $userid ));
            logActivity("Added Credit - User ID: " . $userid . " - Amount: " . formatCurrency($amount), $userid);
            redir("userid=" . $userid);
        }

        if( $sub == "remove" ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Manage Credits");
            insert_query("tblcredit", array( "clientid" => $userid, "date" => toMySQLDate($date), "description" => $description, "amount" => 0 - $amount ));
            update_query("tblclients", array( "credit" => "-=" . $amount ), array( "id" => (int) $userid ));
            logActivity("Removed Credit - User ID: " . $userid . " - Amount: " . formatCurrency($amount), $userid);
            redir("userid=" . $userid);
        }

        if( $sub == "save" ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Manage Credits");
            $update = array( "date" => toMySQLDate($date), "description" => $description );
            WHMCS\Database\Capsule::table("tblcredit")->where("id", "=", $id)->update($update);
            logActivity("Edited Credit - Credit ID: " . $id . " - User ID: " . $userid, $userid);
            redir("userid=" . $userid);
        }

        if( $sub == "delete" ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Manage Credits");
            $ide = (int) $whmcs->get_req_var("ide");
            $result = select_query("tblcredit", "", array( "id" => $ide ));
            $data = mysql_fetch_array($result);
            if( $data["clientid"] == $userid ) 
            {
                $amount = $data["amount"];
                $creditbalance = $creditbalance - $amount;
                if( $creditbalance < 0 ) 
                {
                    $creditbalance = 0;
                }

                update_query("tblclients", array( "credit" => $creditbalance ), array( "id" => (int) $userid ));
                delete_query("tblcredit", array( "id" => $ide, "clientid" => $userid ));
                logActivity("Deleted Credit - Credit ID: " . $ide . " - User ID: " . $userid, $userid);
            }

            redir("userid=" . $userid);
        }

    }

}

if( !empty($errorMessages) ) 
{
    $action = ($sub == "save" ? "edit" : $sub);
    $sub = "";
}

$creditbalance = formatCurrency($creditbalance);
if( $action == "" ) 
{
    echo "\n<p>";
    echo $aInt->lang("credit", "info");
    echo "</p>\n<div style=\"float:right;\">\n    <input type=\"button\" class=\"btn btn-success\" value=\"";
    echo $aInt->lang("credit", "addcredit");
    echo "\" onClick=\"window.location='";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "&action=add'\">\n    <input type=\"button\" value=\"";
    echo $aInt->lang("credit", "removecredit");
    echo "\" onClick=\"window.location='";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "&action=remove'\"  class=\"btn btn-danger\">\n</div>\n<p>";
    echo $aInt->lang("fields", "client");
    echo ": <B>";
    echo $name;
    echo "</B> (";
    echo $aInt->lang("fields", "balance");
    echo ": ";
    echo $creditbalance;
    echo ")</p>\n<br />\n\n<script language=\"JavaScript\">\nfunction doDelete(id) {\nif (confirm(\"";
    echo addslashes($aInt->lang("credit", "deleteq"));
    echo "\")) {\nwindow.location='";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "&sub=delete&ide='+id+'";
    echo generate_token("link");
    echo "';\n}}\n</script>\n\n";
    $aInt->sortableTableInit("nopagination");
    $patterns = $replacements = array(  );
    $patterns[] = "/ Invoice #(.*?) /";
    $replacements[] = " <a href=\"invoices.php?action=edit&id=\$1\" target=\"_blank\">Invoice #\$1</a>";
    $result = select_query("tblcredit", "", array( "clientid" => $userid ), "date", "DESC");
    while( $data = mysql_fetch_array($result) ) 
    {
        $id = $data["id"];
        $date = $data["date"];
        $date = fromMySQLDate($date);
        $description = $data["description"];
        $amount = $data["amount"];
        $description = preg_replace($patterns, $replacements, $description . " ");
        $tabledata[] = array( $date, nl2br(trim($description)), formatCurrency($amount), "<a href=\"?userid=" . $userid . "&action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>" );
    }
    echo $aInt->sortableTable(array( $aInt->lang("fields", "date"), $aInt->lang("fields", "description"), $aInt->lang("fields", "amount"), "", "" ), $tabledata);
    echo "\n<p align=\"center\"><input type=\"button\" value=\"";
    echo $aInt->lang("addons", "closewindow");
    echo "\" onClick=\"window.close()\" class=\"button btn btn-default\" /></p>\n\n";
}
else
{
    if( $action == "add" || $action == "remove" ) 
    {
        checkPermission("Manage Credits");
        if( !$date ) 
        {
            $date = getTodaysDate();
        }

        if( !$amount ) 
        {
            $amount = "0.00";
        }

        if( $action == "add" ) 
        {
            $title = $aInt->lang("credit", "addcredit");
        }
        else
        {
            $title = $aInt->lang("credit", "removecredit");
        }

        $result = select_query("tblclients", "", array( "id" => $userid ));
        $data = mysql_fetch_array($result);
        $creditbalance = formatCurrency($data["credit"]);
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?userid=";
        echo $userid;
        echo "&sub=";
        echo $action;
        echo "\">\n\n<p>";
        echo $aInt->lang("fields", "client");
        echo ": <B>";
        echo $name;
        echo "</B> (";
        echo $aInt->lang("fields", "balance");
        echo ": ";
        echo $creditbalance;
        echo ")</p>\n\n<p><b>";
        echo $title;
        echo "</b></p>\n\n";
        if( !empty($errorMessages) ) 
        {
            echo infoBox($aInt->lang("global", "validationerror"), nl2br(WHMCS\Input\Sanitize::makeSafeForOutput(implode("\n", $errorMessages))), "error");
        }

        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "date");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"date\" size=\"12\" value=\"";
        echo $date;
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo "</td><td class=\"fieldarea\"><textarea name=\"description\" cols=\"75\" rows=\"4\">";
        echo $description;
        echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "amount");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" size=\"15\" value=\"";
        echo $amount;
        echo "\"></td></tr>\n</table>\n\n<p align=center><input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"button btn btn-default\"></p>\n\n</form>\n\n";
    }
    else
    {
        if( $action == "edit" ) 
        {
            checkPermission("Manage Credits");
            $result = select_query("tblcredit", "", array( "id" => $id ));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $date = $data["date"];
            $date = fromMySQLDate($date);
            $description = $data["description"];
            $amount = $data["amount"];
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?userid=";
            echo $userid;
            echo "&sub=save&id=";
            echo $id;
            echo "\">\n    <p>\n        <b>";
            echo $aInt->lang("credit", "addcredit");
            echo "</b>\n    </p>\n    ";
            if( !empty($errorMessages) ) 
            {
                echo infoBox($aInt->lang("global", "validationerror"), nl2br(WHMCS\Input\Sanitize::makeSafeForOutput(implode("\n", $errorMessages))), "error");
            }

            echo "    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
            echo $aInt->lang("fields", "date");
            echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"date\" size=\"12\" value=\"";
            echo $date;
            echo "\">\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
            echo $aInt->lang("fields", "description");
            echo "            </td>\n            <td class=\"fieldarea\">\n                <textarea name=\"description\" cols=\"75\" rows=\"4\">";
            echo $description;
            echo "</textarea>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
            echo $aInt->lang("fields", "amount");
            echo "            </td>\n            <td class=\"fieldarea\">\n                <div data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"";
            echo $aInt->lang("clientsummary", "useButtonsToAffectAmount");
            echo "\">\n                    <input type=\"text\" name=\"amount\" size=\"15\" value=\"";
            echo $amount;
            echo "\" disabled>\n                    <span class=\"bg-warning\"><a href=\"http://docs.whmcs.com/Transactions#Managing_Credit\" target=\"_blank\">";
            echo $aInt->lang("clientsummary", "cannotEditAmount");
            echo "</a></span>\n                </div>\n            </td>\n        </tr>\n    </table>\n    <p align=center>\n        <input type=\"submit\" value=\"";
            echo $aInt->lang("global", "savechanges");
            echo "\" class=\"button btn btn-default\">\n    </p>\n</form>\n\n";
            $jQueryCode .= "jQuery('[data-toggle=\"tooltip\"]').tooltip();";
        }

    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jQueryCode;
$aInt->displayPopUp();

