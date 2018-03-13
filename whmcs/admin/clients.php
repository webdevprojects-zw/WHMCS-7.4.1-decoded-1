<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("List Clients");
$aInt->title = $aInt->lang("clients", "viewsearch");
$aInt->sidebar = "clients";
$aInt->icon = "clients";
$limitClientId = 0;
$licensing = DI::make("license");
if( $licensing->isClientLimitsEnabled() ) 
{
    $limitClientId = $licensing->getClientBoundaryId();
}

$name = "clients";
$orderby = "id";
$sort = "DESC";
$pageObj = new WHMCS\Pagination($name, $orderby, $sort);
$pageObj->digestCookieData();
$tbl = new WHMCS\ListTable($pageObj);
$tbl->setColumns(array( "checkall", array( "id", $aInt->lang("fields", "id") ), array( "firstname", $aInt->lang("fields", "firstname") ), array( "lastname", $aInt->lang("fields", "lastname") ), array( "companyname", $aInt->lang("fields", "companyname") ), array( "email", $aInt->lang("fields", "email") ), $aInt->lang("fields", "services"), array( "datecreated", $aInt->lang("fields", "created") ), array( "status", $aInt->lang("fields", "status") ) ));
$clientsModel = new WHMCS\Clients($pageObj);
$filters = new WHMCS\Filter();
ob_start();
echo $aInt->beginAdminTabs(array( $aInt->lang("global", "searchfilter") ));
$userid = $filters->get("userid");
$country = $filters->get("country");
echo "\n<form action=\"clients.php\" method=\"post\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "clientname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"clientname\" class=\"form-control input-250\" value=\"";
echo $clientname = $filters->get("clientname");
echo "\" /></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "companyname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"companyname\" class=\"form-control input-250\" value=\"";
echo $companyname = $filters->get("companyname");
echo "\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "email");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" class=\"form-control input-300\" value=\"";
echo $email = $filters->get("email");
echo "\" /></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "address");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"address\" class=\"form-control input-250\" value=\"";
echo $address = $filters->get("address");
echo "\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "status");
echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option><option value=\"Active\"";
$status = $filters->get("status");
if( $status == "Active" ) 
{
    echo " selected";
}

echo ">";
echo $aInt->lang("status", "active");
echo "</option><option value=\"Inactive\"";
if( $status == "Inactive" ) 
{
    echo " selected";
}

echo ">";
echo $aInt->lang("status", "inactive");
echo "</option><option value=\"Closed\"";
if( $status == "Closed" ) 
{
    echo " selected";
}

echo ">";
echo $aInt->lang("status", "closed");
echo "</option></select></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "state");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"state\" class=\"form-control input-250\" value=\"";
echo $state = $filters->get("state");
echo "\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "clientgroup");
echo "</td><td class=\"fieldarea\"><select name=\"clientgroup\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$clientgroup = $filters->get("clientgroup");
foreach( $clientsModel->getGroups() as $id => $values ) 
{
    echo "<option value=\"" . $id . "\"";
    if( $id == $clientgroup ) 
    {
        echo " selected";
    }

    echo ">" . $values["name"] . "</option>";
}
echo "</select></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "phonenumber");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"phonenumber\" class=\"form-control input-250\" value=\"";
echo $phonenumber = $filters->get("phonenumber");
echo "\" /></td></tr>\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("currencies", "currency");
echo "</td><td class=\"fieldarea\"><select name=\"currency\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$currency = $filters->get("currency");
$result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
while( $data = mysql_fetch_assoc($result) ) 
{
    echo "<option value=\"" . $data["id"] . "\"";
    if( $currency == $data["id"] ) 
    {
        echo " selected";
    }

    echo ">" . $data["code"] . "</option>";
}
echo "</select></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "cardlast4");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"cardlastfour\" class=\"form-control input-100\" value=\"";
echo $cardlastfour = $filters->get("cardlastfour");
echo "\" /></td></tr>\n";
$customfields = $filters->get("customfields");
$result = select_query("tblcustomfields", "id,fieldname", array( "type" => "client" ));
while( $data = mysql_fetch_array($result) ) 
{
    $fieldid = $data["id"];
    $fieldname = $data["fieldname"];
    echo "<tr><td class=\"fieldlabel\">" . $fieldname . "</td><td class=\"fieldarea\" colspan=\"3\"><input type=\"text\" name=\"customfields[" . $fieldid . "]\" value=\"" . $customfields[$fieldid] . "\" class=\"form-control input-250\" /></td></tr>";
}
echo "</table>\n<div class=\"btn-container\"><input type=\"submit\" id=\"search-clients\" value=\"";
echo $aInt->lang("global", "search");
echo "\" class=\"button btn btn-default\"></div>\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br />\n\n";
$filters->store();
$criteria = array( "userid" => $userid, "clientname" => $clientname, "companyname" => $companyname, "email" => $email, "address" => $address, "country" => $country, "status" => $status, "state" => $state, "clientgroup" => $clientgroup, "phonenumber" => $phonenumber, "currency" => $currency, "cardlastfour" => $cardlastfour, "customfields" => $customfields );
$clientsModel->execute($criteria);
$numresults = $pageObj->getNumResults();
if( $filters->isActive() && $numresults == 1 ) 
{
    $client = $pageObj->getOne();
    redir("userid=" . $client["id"], "clientssummary.php");
}
else
{
    $clientlist = $pageObj->getData();
    foreach( $clientlist as $client ) 
    {
        $clientId = $client["id"];
        $linkopen = "<a href=\"clientssummary.php?userid=" . $client["id"] . "\"" . (($client["groupcolor"] ? " style=\"background-color:" . $client["groupcolor"] . "\"" : "")) . ">";
        $linkclose = "</a>";
        $checkbox = "<input type=\"checkbox\" name=\"selectedclients[]\" value=\"" . $client["id"] . "\" class=\"checkall\" />";
        if( 0 < $limitClientId && $limitClientId <= $clientId ) 
        {
            $checkbox = array( "trAttributes" => array( "class" => "grey-out" ), "output" => $checkbox );
        }

        $tbl->addRow(array( $checkbox, $linkopen . $client["id"] . $linkclose, $linkopen . $client["firstname"] . $linkclose, $linkopen . $client["lastname"] . $linkclose, $client["companyname"], "<a href=\"mailto:" . $client["email"] . "\">" . $client["email"] . "</a>", $client["services"] . " (" . $client["totalservices"] . ")", $client["datecreated"], "<span class=\"label " . strtolower($client["status"]) . "\">" . $client["status"] . "</span>" ));
    }
    $tbl->setMassActionURL("sendmessage.php?type=general&multiple=true");
    $tbl->setMassActionBtns("<input type=\"submit\" value=\"" . $aInt->lang("global", "sendmessage") . "\" class=\"btn btn-default\" />");
    echo $tbl->output();
    unset($clientlist);
    unset($clientsModel);
}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

