<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$whmcs->load_function("clientarea");
$GATEWAY = getGatewayVariables("worldpayfuturepay");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

$invoiceid = (int) $_POST["cartId"];
$futurepayid = mysql_real_escape_string($_POST["futurePayId"]);
$transid = mysql_real_escape_string($_POST["transId"]);
$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
initialiseClientArea($_LANG["ordercheckout"], "", $_LANG["ordercheckout"]);
$templateName = $whmcs->getClientAreaTemplate()->getName();
$templateVars = $smarty->getTemplateVars();
$templateVars["primarySidebar"] = Menu::primarySidebar("support");
$templateVars["secondarySidebar"] = Menu::secondarySidebar("support");
echo processSingleTemplate("/templates/" . $templateName . "/header.tpl", $templateVars);
echo "<WPDISPLAY ITEM=\"banner\">";
$result = select_query("tblinvoices", "", array( "id" => $invoiceid ));
$data = mysql_fetch_array($result);
$userid = $data["userid"];
if( $_POST["transStatus"] == "Y" ) 
{
    logTransaction($GATEWAY["paymentmethod"], $_POST, "Successful");
    update_query("tblclients", array( "gatewayid" => $futurepayid ), array( "id" => $userid ));
    addInvoicePayment($invoiceid, $transid, "", "", "worldpayfuturepay");
    echo "<p align=\"center\"><a href=\"" . $CONFIG["SystemURL"] . "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true\">Click here to return to " . $CONFIG["CompanyName"] . "</a></p>";
}
else
{
    logTransaction($GATEWAY["paymentmethod"], $_POST, "Unsuccessful");
    echo "<p align=\"center\"><a href=\"" . $CONFIG["SystemURL"] . "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true\">Click here to return to " . $CONFIG["CompanyName"] . "</a></p>";
}

echo processSingleTemplate("/templates/" . $templateName . "/footer.tpl", $templateVars);

