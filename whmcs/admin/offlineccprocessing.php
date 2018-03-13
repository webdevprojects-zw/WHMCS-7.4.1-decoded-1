<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Offline Credit Card Processing");
$aInt->title = $aInt->lang("offlineccp", "title");
$aInt->sidebar = "billing";
$aInt->icon = "offlinecc";
$aInt->requiredFiles(array( "clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions" ));
if( $processwindow ) 
{
    check_token("WHMCS.admin.default");
    $result = select_query("tblinvoices", "", array( "id" => $id ));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    $userid = $data["userid"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"];
    $total = $data["total"];
    $paymentmethod = $data["value"];
    $date = fromMySQLDate($date);
    $duedate = fromMySQLDate($duedate);
    $currency = getCurrency($userid);
    $clientsdetails = getClientsDetails($userid);
    ob_start();
    echo "\n<table width=100% cellspacing=\"4\">\n<tr><td height=\"120\" width=50% style=\"border:1px solid #cccccc\">\n\n<p align=center><b>";
    echo $aInt->lang("emailtpls", "typeinvoice");
    echo " #";
    echo $id;
    echo "</b><br>\n";
    echo $aInt->lang("mergefields", "datecreated");
    echo ": ";
    echo $date;
    echo "<br>\n";
    echo $aInt->lang("fields", "duedate");
    echo ": ";
    echo $duedate;
    echo "<br>\n";
    echo $aInt->lang("fields", "subtotal");
    echo ": ";
    echo $subtotal;
    echo "<br>\n";
    echo $aInt->lang("general", "tabcredit");
    echo ": ";
    echo $credit;
    echo "<br>\n";
    echo $aInt->lang("fields", "tax");
    echo ": ";
    echo $tax;
    echo "<br>\n";
    echo $aInt->lang("fields", "total");
    echo ": ";
    echo $total;
    echo "</p>\n\n</td><td width=50% style=\"border:1px solid #cccccc\">\n\n<p align=center><b>";
    echo $aInt->lang("clientsummary", "clientdetails");
    echo "</b><br>\n";
    echo $clientsdetails["firstname"] . " " . $clientsdetails["lastname"];
    if( $clientsdetails["companyname"] != "" ) 
    {
        echo " (" . $clientsdetails["companyname"] . ")";
    }

    echo "<br>\n";
    echo $clientsdetails["email"];
    echo "<br>\n";
    echo $clientsdetails["address1"];
    echo ", ";
    echo $clientsdetails["address2"];
    echo "<br>\n";
    echo $clientsdetails["city"];
    echo ", ";
    echo $clientsdetails["state"];
    echo ", ";
    echo $clientsdetails["postcode"];
    echo "<br>\n";
    $countries = new WHMCS\Utility\Country();
    echo $countries->getName($clientsdetails["country"]);
    echo "<br>\n";
    echo $clientsdetails["phonenumber"];
    echo "</p>\n\n</td></tr>\n</table>\n\n<p><b>";
    echo $aInt->lang("clientsummary", "ccdetails");
    echo "</b></p>\n";
    if( $cchash == "" ) 
    {
        echo "<p>";
        echo $aInt->lang("offlineccp", "entercchashmsg");
        echo "</p>\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?processwindow=true&id=";
        echo $id;
        echo "\">\n<p align=center><textarea name=\"cchash\" cols=40 rows=3></textarea><br><br>\n<input type=\"submit\" value=\"Submit\" class=\"button btn btn-default\"></p>\n</form>\n";
    }
    else
    {
        check_token("WHMCS.admin.default");
        $referrer = $_SERVER["HTTP_REFERER"];
        $pos = strpos($referrer, "?");
        if( $pos ) 
        {
            $referrer = substr($referrer, 0, $pos);
        }

        $adminfolder = $whmcs->get_admin_folder_name();
        if( App::getSystemUrl() . $adminfolder . "/offlineccprocessing.php" != $referrer ) 
        {
            echo "<p>" . $aInt->lang("global", "invalidaccessattempt") . "</p>";
            exit();
        }

        if( $cchash != $cc_encryption_hash ) 
        {
            echo $aInt->lang("offlineccp", "entercchashdie");
        }
        else
        {
            logActivity("Viewed Decrypted Credit Card Number for User ID " . $userid);
            if( $successful == "true" ) 
            {
                addInvoicePayment($id, $transid, "", "", "offlinecc");
                echo "<p align=center><a href=\"#\" onClick=\"window.opener.location.reload();window.close()\">" . $aInt->lang("addons", "closewindow") . "</a></p>";
                exit();
            }

            if( $failed == "true" ) 
            {
                sendMessage("Credit Card Payment Failed", $id);
                echo "<p align=center><a href=\"#\" onClick=\"window.opener.location.reload();window.close()\">" . $aInt->lang("addons", "closewindow") . "</a></p>";
                exit();
            }

            $data = getCCDetails($userid);
            $cardtype = $data["cardtype"];
            $cardnum = $data["fullcardnum"];
            $cardexp = $data["expdate"];
            $cardissuenum = $data["issuenumber"];
            $cardstart = $data["startdate"];
            echo $aInt->lang("fields", "cardtype") . ": " . $cardtype . "<br>" . $aInt->lang("fields", "cardnum") . ": " . $cardnum . "<br>" . $aInt->lang("fields", "expdate") . ": " . $cardexp . " (MMYY)";
            if( $cardissuenum ) 
            {
                echo "<br>" . $aInt->lang("fields", "issueno") . ": " . $cardissuenum;
            }

            if( $cardstart ) 
            {
                echo "<br>" . $aInt->lang("fields", "startdate") . ": " . $cardstart;
            }

            echo "<br><br>\n<center>\n<b>";
            echo $aInt->lang("offlineccp", "transresult");
            echo "</b><br>\n<img src=\"images/spacer.gif\" width=\"1\" height=\"5\"><br>\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?processwindow=true&id=";
            echo $id;
            echo "&successful=true\">Trans ID: <input type=\"text\" name=\"transid\" size=\"20\"> <input type=\"hidden\" name=\"cchash\" value=\"";
            echo $cchash;
            echo "\"><input type=\"submit\" value=\"Successful\" class=\"button btn btn-default\"></form>\n<img src=\"images/spacer.gif\" width=\"1\" height=\"5\"><br>\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?processwindow=true&id=";
            echo $id;
            echo "&failed=true\"><input type=\"hidden\" name=\"cchash\" value=\"";
            echo $cchash;
            echo "\"><input type=\"submit\" value=\"Failed\" class=\"button btn btn-default\"></form>\n";
        }

    }

    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->displayPopUp();
    exit();
}

ob_start();
$jscode = "function openCCDetails(id) {\nvar winl = (screen.width - 500) / 2;\nvar wint = (screen.height - 400) / 2;\nwinprops = 'height=400,width=500,top='+wint+',left='+winl+',scrollbars=no'\nwin = window.open('" . $_SERVER["PHP_SELF"] . "?processwindow=true" . generate_token("link") . "&id='+id, 'ccdetails', winprops)\nif (parseInt(navigator.appVersion) >= 4) { win.window.focus(); }\n}";
$aInt->sortableTableInit("duedate", "ASC");
$gatewaysarray = getGatewaysArray();
$query = "SELECT tblinvoices.*,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE paymentmethod='offlinecc' AND tblinvoices.status='Unpaid' ORDER BY ";
if( $orderby == "clientname" ) 
{
    $query .= "firstname " . db_escape_string($order) . ", lastname";
}
else
{
    $query .= db_escape_string($orderby);
}

$query .= " " . db_escape_string($order);
$numresults = full_query($query);
$numrows = mysql_num_rows($numresults);
$query .= " LIMIT " . (int) ($page * $limit) . "," . (int) $limit;
$result = full_query($query);
while( $data = mysql_fetch_array($result) ) 
{
    $id = $data["id"];
    $userid = $data["userid"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $total = $data["total"];
    $paymentmethod = $data["paymentmethod"];
    $paymentmethod = $gatewaysarray[$paymentmethod];
    $date = fromMySQLDate($date);
    $duedate = fromMySQLDate($duedate);
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $companyname = $data["companyname"];
    $groupid = $data["groupid"];
    $currency = getCurrency($userid);
    $total = formatCurrency($total);
    $tabledata[] = array( "<a href=\"invoices.php?action=edit&id=" . $id . "\">" . $id . "</a>", $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid), $date, $duedate, $total, "<input type=\"button\" value=\"View Processing Window\" onClick=\"openCCDetails(" . $id . ");return false\">" );
}
echo $aInt->sortableTable(array( array( "id", $aInt->lang("fields", "id") ), array( "clientname", $aInt->lang("fields", "clientname") ), array( "date", $aInt->lang("fields", "invoicedate") ), array( "duedate", $aInt->lang("fields", "duedate") ), array( "total", $aInt->lang("fields", "total") ), $aInt->lang("supportticketescalations", "actions") ), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

