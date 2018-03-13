<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Configure Fraud Protection");
$aInt->title = $aInt->lang("fraud", "title");
$aInt->sidebar = "config";
$aInt->icon = "configbans";
$aInt->helplink = "Fraud Protection";
$aInt->requiredFiles(array( "modulefunctions" ));
ob_start();
$module = new WHMCS\Module\Fraud();
$fraudmodules = $module->getList();
if( $fraud && in_array($fraud, $fraudmodules) ) 
{
    $module->load($fraud);
    $configarray = $module->call("getConfigArray");
    $existingValues = $module->getSettings();
    if( $action == "save" ) 
    {
        check_token("WHMCS.admin.default");
        foreach( $configarray as $regconfoption => $values ) 
        {
            $regconfoption2 = str_replace(" ", "_", $regconfoption);
            $valueToSave = trim(WHMCS\Input\Sanitize::decode($_POST[$regconfoption2]));
            if( $values["Type"] == "password" ) 
            {
                $updatedPassword = interpretMaskedPasswordChangeForStorage($valueToSave, $existingValues[$regconfoption2]);
                if( $updatedPassword === false ) 
                {
                    $valueToSave = $existingValues[$regconfoption2];
                }

            }

            $result = select_query("tblfraud", "", array( "fraud" => $fraud, "setting" => $regconfoption ));
            $num_rows = mysql_num_rows($result);
            if( $num_rows == "0" ) 
            {
                insert_query("tblfraud", array( "fraud" => $fraud, "setting" => $regconfoption, "value" => $valueToSave ));
            }
            else
            {
                update_query("tblfraud", array( "value" => $valueToSave ), array( "fraud" => $fraud, "setting" => $regconfoption ));
            }

        }
        logAdminActivity("Fraud Module Configuration Modified: '" . $module->getDisplayName() . "'");
        redir("fraud=" . $fraud . "&success=1");
    }

    if( $success ) 
    {
        infoBox($aInt->lang("fraud", "changesuccess"), $aInt->lang("fraud", "changesuccessinfo"));
    }

    echo $infobox;
}
else
{
    $fraud = "";
}

echo "<p>" . $aInt->lang("fraud", "info") . "</p>";
echo "<form method=\"get\" action=\"" . $whmcs->getPhpSelf() . "\"><p>" . $aInt->lang("fraud", "choose") . ": <select name=\"fraud\" onChange=\"submit();\" class=\"form-control select-inline\">";
foreach( $fraudmodules as $file ) 
{
    echo "<option value=\"" . $file . "\"";
    if( $fraud == $file ) 
    {
        echo " selected";
    }

    echo ">" . TitleCase(str_replace("_", " ", $file)) . "</option>";
}
echo "</select> <input type=\"submit\" value=\" " . $aInt->lang("global", "go") . " \" class=\"btn btn-success\"></p></form>";
if( $fraud ) 
{
    $configarray = $module->call("getConfigArray");
    $configvalues = $module->getSettings();
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=save\">\n<input type=\"hidden\" name=\"fraud\" value=\"";
    echo $fraud;
    echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n";
    foreach( $configarray as $regconfoption => $values ) 
    {
        if( !$values["FriendlyName"] ) 
        {
            $values["FriendlyName"] = $regconfoption;
        }

        $values["Name"] = $regconfoption;
        $values["Value"] = $configvalues[$regconfoption];
        echo "<tr><td class=\"fieldlabel\">" . $values["FriendlyName"] . "</td><td class=\"fieldarea\">" . moduleConfigFieldOutput($values) . "</td></tr>";
    }
    echo "</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"btn btn-primary\" />\n    <input type=\"reset\" value=\"";
    echo $aInt->lang("global", "cancelchanges");
    echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

