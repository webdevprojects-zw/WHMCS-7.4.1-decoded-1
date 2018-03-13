<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Configure Tax Setup");
$aInt->title = $aInt->lang("taxconfig", "taxrulestitle");
$aInt->sidebar = "config";
$aInt->icon = "taxrules";
$aInt->helplink = "Tax/VAT";
$aInt->requireAuthConfirmation();
$action = $whmcs->get_req_var("action");
ob_start();
if( $action == "save" ) 
{
    check_token("WHMCS.admin.default");
    $taxenabled = $whmcs->get_req_var("taxenabled");
    $taxtype = $whmcs->get_req_var("taxtype");
    $taxdomains = $whmcs->get_req_var("taxdomains");
    $taxbillableitems = $whmcs->get_req_var("taxbillableitems");
    $taxlatefee = $whmcs->get_req_var("taxlatefee");
    $taxcustominvoices = $whmcs->get_req_var("taxcustominvoices");
    $taxl2compound = $whmcs->get_req_var("taxl2compound");
    $taxinclusivededuct = $whmcs->get_req_var("taxinclusivededuct");
    $save_arr = array( "TaxEnabled" => $taxenabled, "TaxType" => $taxtype, "TaxDomains" => $taxdomains, "TaxBillableItems" => $taxbillableitems, "TaxLateFee" => $taxlatefee, "TaxCustomInvoices" => $taxcustominvoices, "TaxL2Compound" => $taxl2compound, "TaxInclusiveDeduct" => $taxinclusivededuct );
    if( $taxenabled != WHMCS\Config\Setting::getValue("TaxEnabled") ) 
    {
        if( $taxenabled ) 
        {
            logAdminActivity("Tax Configuration: Tax Support Enabled");
        }
        else
        {
            logAdminActivity("Tax Configuration: Tax Support Disabled");
        }

    }

    $changes = array(  );
    foreach( $save_arr as $k => $v ) 
    {
        if( $k != "TaxEnabled" && WHMCS\Config\Setting::getValue($k) != $v ) 
        {
            $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z][0-9])(?=[A-Z][a-z])/x";
            $friendlySettingParts = preg_split($regEx, $k);
            $friendlySetting = implode(" ", $friendlySettingParts);
            if( $k == "TaxType" ) 
            {
                $changes[] = (string) $friendlySetting . " Set to '" . $v . "'";
            }
            else
            {
                if( $v == "on" ) 
                {
                    $changes[] = (string) $friendlySetting . " Enabled";
                }
                else
                {
                    $changes[] = (string) $friendlySetting . " Disabled";
                }

            }

        }

        WHMCS\Config\Setting::setValue($k, $v);
    }
    if( $changes ) 
    {
        logAdminActivity("Tax Configuration: " . implode(". ", $changes) . ".");
    }

    redir("saved=true");
}

if( $action == "add" ) 
{
    check_token("WHMCS.admin.default");
    $name = $whmcs->get_req_var("name");
    $state = $whmcs->get_req_var("state");
    $country = $whmcs->get_req_var("country");
    $taxrate = $whmcs->get_req_var("taxrate");
    $level = (int) $whmcs->get_req_var("level");
    $countrytype = $whmcs->get_req_var("countrytype");
    $statetype = $whmcs->get_req_var("statetype");
    if( $countrytype == "any" && $statetype != "any" ) 
    {
        infoBox($aInt->lang("global", "validationerror"), $aInt->lang("taxconfig", "taxvalidationerrorcountry"));
        $validationerror = true;
    }
    else
    {
        if( $countrytype == "any" ) 
        {
            $country = "";
        }

        if( $statetype == "any" ) 
        {
            $state = "";
        }

        logAdminActivity("Tax Configuration: Level " . $level . " Rule Added: " . $name);
        insert_query("tbltax", array( "level" => $level, "name" => $name, "state" => $state, "country" => $country, "taxrate" => $taxrate ));
        redir();
    }

}

if( $action == "delete" ) 
{
    check_token("WHMCS.admin.default");
    $id = (int) $whmcs->get_req_var("id");
    $taxRule = Illuminate\Database\Capsule\Manager::table("tbltax")->find($id);
    logAdminActivity("Tax Configuration: Level " . $taxRule->level . " Rule Deleted: " . $taxRule->name);
    delete_query("tbltax", array( "id" => $id ));
    redir();
}

$result = select_query("tblconfiguration", "", "");
while( $data = mysql_fetch_array($result) ) 
{
    $setting = $data["setting"];
    $value = $data["value"];
    $CONFIG[(string) $setting] = (string) $value;
}
if( $saved ) 
{
    infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"));
}

echo $infobox;
$aInt->deleteJSConfirm("doDelete", "taxconfig", "delsuretaxrule", "?action=delete&id=");
echo "\n<p>";
echo $aInt->lang("taxconfig", "taxrulesconfigheredesc");
echo "</p>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=save\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("taxconfig", "taxsupportenabled");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxenabled\" id=\"taxenabled\"";
if( $CONFIG["TaxEnabled"] ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxsupportenableddesc");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("taxconfig", "taxtype");
echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"taxtype\" value=\"Exclusive\"";
if( $CONFIG["TaxType"] == "Exclusive" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxtypeexclusive");
echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"taxtype\" value=\"Inclusive\"";
if( $CONFIG["TaxType"] == "Inclusive" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxtypeinclusive");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("taxconfig", "taxappliesto");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxdomains\"";
if( $CONFIG["TaxDomains"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxdomains");
echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxbillableitems\"";
if( $CONFIG["TaxBillableItems"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxbillableitems");
echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxlatefee\"";
if( $CONFIG["TaxLateFee"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxlatefee");
echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxcustominvoices\"";
if( $CONFIG["TaxCustomInvoices"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxcustominvoices");
echo "</label> (";
echo $aInt->lang("taxconfig", "taxproducts");
echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("taxconfig", "compoundtax");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxl2compound\"";
if( $CONFIG["TaxL2Compound"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "compoundtaxdesc");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("taxconfig", "deducttaxamount");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxinclusivededuct\"";
if( $CONFIG["TaxInclusiveDeduct"] == "on" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "deducttaxamountdesc");
echo "</label></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n</div>\n</form>\n\n<br>\n\n";
echo $aInt->beginAdminTabs(array( $aInt->lang("taxconfig", "level1rules"), $aInt->lang("taxconfig", "level2rules"), $aInt->lang("taxconfig", "addnewrule") ), true);
if( $validationerror ) 
{
    $jqueryCode = "\$(\".tab\").removeClass(\"tabselected\");\$(\".tabbox\").hide();\$(\"#tab2\").addClass(\"tabselected\");\$(\"#tab2box\").show();";
}

$aInt->sortableTableInit("nopagination");
$countries = new WHMCS\Utility\Country();
$countries = $countries->getCountryNameArray();
$tabledata = array(  );
$taxRulesLevel1 = Illuminate\Database\Capsule\Manager::table("tbltax")->where("level", "1")->orderBy("country", "asc")->orderBy("state", "asc")->get();
foreach( $taxRulesLevel1 as $data ) 
{
    $id = $data->id;
    $name = $data->name;
    $state = $data->state;
    if( array_key_exists($data->country, $countries) ) 
    {
        $country = $countries[$data->country];
    }
    else
    {
        $country = $data->country;
    }

    $taxrate = $data->taxrate;
    if( $state == "" ) 
    {
        $state = $aInt->lang("taxconfig", "taxappliesanystate");
    }

    if( $country == "" ) 
    {
        $country = $aInt->lang("taxconfig", "taxappliesanycountry");
    }

    $tabledata[] = array( $name, $country, $state, $taxrate . "%", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
}
echo $aInt->sortableTable(array( $aInt->lang("fields", "name"), $aInt->lang("fields", "country"), $aInt->lang("fields", "state"), $aInt->lang("fields", "taxrate"), "" ), $tabledata);
echo $aInt->nextAdminTab();
$aInt->sortableTableInit("nopagination");
$tabledata = array(  );
$taxRulesLevel2 = Illuminate\Database\Capsule\Manager::table("tbltax")->where("level", "2")->orderBy("country", "asc")->orderBy("state", "asc")->get();
foreach( $taxRulesLevel2 as $data ) 
{
    $id = $data->id;
    $name = $data->name;
    $state = $data->state;
    if( array_key_exists($data->country, $countries) ) 
    {
        $country = $countries[$data->country];
    }
    else
    {
        $country = $data->country;
    }

    $taxrate = $data->taxrate;
    if( $state == "" ) 
    {
        $state = $aInt->lang("taxconfig", "taxappliesanystate");
    }

    if( $country == "" ) 
    {
        $country = $aInt->lang("taxconfig", "taxappliesanycountry");
    }

    $tabledata[] = array( $name, $country, $state, $taxrate . "%", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>" );
}
echo $aInt->sortableTable(array( $aInt->lang("fields", "name"), $aInt->lang("fields", "country"), $aInt->lang("fields", "state"), $aInt->lang("fields", "taxrate"), "" ), $tabledata);
echo $aInt->nextAdminTab();
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=add\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">Level</td><td class=\"fieldarea\"><select name=\"level\" class=\"form-control select-inline\"><option>1</option><option";
if( $_POST["level"] == 2 ) 
{
    echo " selected";
}

echo ">2</option></select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "name");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
echo $_POST["name"];
echo "\" class=\"form-control input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "country");
echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"countrytype\" value=\"any\" checked> ";
echo $aInt->lang("taxconfig", "taxappliesallcountry");
echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"countrytype\" value=\"specific\"";
if( $_POST["countrytype"] == "specific" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxappliesspecificcountry");
echo ":</label> ";
include("../includes/clientfunctions.php");
echo getCountriesDropDown($_POST["country"]);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">State</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"statetype\" value=\"any\" checked> ";
echo $aInt->lang("taxconfig", "taxappliesallstate");
echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"statetype\" value=\"specific\"";
if( $_POST["statetype"] == "specific" ) 
{
    echo " checked";
}

echo "> ";
echo $aInt->lang("taxconfig", "taxappliesspecificstate");
echo ":</label> <input type=\"text\" name=\"state\" data-selectinlinedropdown=\"1\" size=\"25\" value=\"";
echo $_POST["state"];
echo "\" class=\"form-control input-250 input-inline\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "taxrate");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"taxrate\" value=\"";
echo (isset($_POST["taxrate"]) ? $_POST["taxrate"] : "0.00");
echo "\" class=\"form-control input-100 input-inline\" /> %</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("taxconfig", "addrule");
echo "\" class=\"button btn btn-primary\" />\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
$jsCode = "var stateNotRequired = true;\n";
$jqueryCode .= "jQuery(\"#country\").on(\n    \"change\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"countrytype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"countrytype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"focus\",\n    \"#stateinput\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"change\",\n    \"#stateselect\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);";
echo WHMCS\View\Asset::jsInclude("StatesDropdown.js");
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jqueryCode;
$aInt->jscode = $jsCode;
$aInt->display();

