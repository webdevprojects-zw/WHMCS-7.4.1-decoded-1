<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Configure Payment Gateways");
$aInt->title = $aInt->lang("setup", "gateways");
$aInt->sidebar = "config";
$aInt->icon = "offlinecc";
$aInt->helplink = "Payment Gateways";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array( "gatewayfunctions", "modulefunctions" ));
$GatewayValues = $GatewayConfig = $ActiveGateways = $DisabledGateways = $AllGateways = array(  );
$result = select_query("tblpaymentgateways", "", "", "setting", "ASC");
while( $data = mysql_fetch_array($result) ) 
{
    $gwv_gateway = $data["gateway"];
    $gwv_setting = $data["setting"];
    $gwv_value = $data["value"];
    $GatewayValues[$gwv_gateway][$gwv_setting] = $gwv_value;
}
$includedmodules = array(  );
$dh = opendir("../modules/gateways/");
while( false !== ($file = readdir($dh)) ) 
{
    $fileext = explode(".", $file, 2);
    if( trim($file) && $file != "index.php" && 1 < count($fileext) && $fileext[1] == "php" && !in_array($fileext[0], $includedmodules) ) 
    {
        $includedmodules[] = $fileext[0];
        $gwv_modulename = $fileext[0];
        if( !isValidforPath($gwv_modulename) ) 
        {
            exit( "Invalid Gateway Module Name" );
        }

        require_once(ROOTDIR . "/modules/gateways/" . $gwv_modulename . ".php");
        $AllGateways[] = $gwv_modulename;
        if( isset($GatewayValues[$gwv_modulename]["type"]) ) 
        {
            $ActiveGateways[] = $gwv_modulename;
        }
        else
        {
            $DisabledGateways[] = $gwv_modulename;
        }

        if( function_exists($gwv_modulename . "_config") ) 
        {
            $GatewayConfig[$gwv_modulename] = call_user_func($gwv_modulename . "_config");
        }
        else
        {
            $GatewayFieldDefines = array(  );
            $GatewayFieldDefines["FriendlyName"] = array( "Type" => "System", "Value" => $GATEWAYMODULE[$gwv_modulename . "visiblename"] );
            if( isset($GATEWAYMODULE[$gwv_modulename . "notes"]) ) 
            {
                $GatewayFieldDefines["UsageNotes"] = array( "Type" => "System", "Value" => $GATEWAYMODULE[$gwv_modulename . "notes"] );
            }

            call_user_func($gwv_modulename . "_activate");
            $GatewayConfig[$gwv_modulename] = array_merge($GatewayFieldDefines, defineGatewayFieldStorage(true));
        }

    }

}
closedir($dh);
$result = select_query("tblpaymentgateways", "", "", "order", "DESC");
$data = mysql_fetch_array($result);
$lastorder = $data["order"];
$action = $whmcs->get_req_var("action");
if( $action == "activate" && in_array($gateway, $includedmodules) ) 
{
    check_token("WHMCS.admin.default");
    delete_query("tblpaymentgateways", array( "gateway" => $gateway ));
    $lastorder++;
    $type = "Invoices";
    if( function_exists($gateway . "_capture") ) 
    {
        $type = "CC";
    }

    insert_query("tblpaymentgateways", array( "gateway" => $gateway, "setting" => "name", "value" => $GatewayConfig[$gateway]["FriendlyName"]["Value"], "order" => $lastorder ));
    if( $GatewayConfig[$gateway]["RemoteStorage"] ) 
    {
        insert_query("tblpaymentgateways", array( "gateway" => $gateway, "setting" => "remotestorage", "value" => "1" ));
    }

    insert_query("tblpaymentgateways", array( "gateway" => $gateway, "setting" => "type", "value" => $type ));
    insert_query("tblpaymentgateways", array( "gateway" => $gateway, "setting" => "visible", "value" => "on" ));
    logAdminActivity("Gateway Module Activated: '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'");
    redir("activated=" . $gateway . "#m_" . $gateway);
}

if( $action == "deactivate" && in_array($newgateway, $includedmodules) ) 
{
    check_token("WHMCS.admin.default");
    if( $gateway != $newgateway ) 
    {
        update_query("tblhosting", array( "paymentmethod" => $newgateway ), array( "paymentmethod" => $gateway ));
        update_query("tblhostingaddons", array( "paymentmethod" => $newgateway ), array( "paymentmethod" => $gateway ));
        update_query("tbldomains", array( "paymentmethod" => $newgateway ), array( "paymentmethod" => $gateway ));
        update_query("tblinvoices", array( "paymentmethod" => $newgateway ), array( "paymentmethod" => $gateway ));
        update_query("tblorders", array( "paymentmethod" => $newgateway ), array( "paymentmethod" => $gateway ));
        update_query("tblaccounts", array( "gateway" => $newgateway ), array( "gateway" => $gateway ));
        delete_query("tblpaymentgateways", array( "gateway" => $gateway ));
        logAdminActivity("Gateway Module Deactivated: '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'" . " to '" . $GatewayConfig[$newgateway]["FriendlyName"]["Value"] . "'");
        redir("deactivated=true");
    }
    else
    {
        redir();
    }

    exit();
}

if( $action == "save" && in_array($module, $includedmodules) ) 
{
    check_token("WHMCS.admin.default");
    $GatewayConfig[$module]["visible"] = array( "Type" => "yesno" );
    $GatewayConfig[$module]["name"] = array( "Type" => "text" );
    $GatewayConfig[$module]["convertto"] = array( "Type" => "text" );
    foreach( $GatewayConfig[$module] as $confname => $values ) 
    {
        if( $values["Type"] != "System" ) 
        {
            $valueToSave = WHMCS\Input\Sanitize::decode(trim($field[$confname]));
            if( $values["Type"] == "password" ) 
            {
                $updatedPassword = interpretMaskedPasswordChangeForStorage($valueToSave, $GatewayValues[$module][$confname]);
                if( $updatedPassword === false ) 
                {
                    $valueToSave = $GatewayValues[$module][$confname];
                }

            }

            $result = select_query("tblpaymentgateways", "COUNT(*)", array( "gateway" => $module, "setting" => $confname ));
            $data = mysql_fetch_array($result);
            $count = $data[0];
            if( $count ) 
            {
                update_query("tblpaymentgateways", array( "value" => $valueToSave ), array( "gateway" => $module, "setting" => $confname ));
            }
            else
            {
                insert_query("tblpaymentgateways", array( "gateway" => $module, "setting" => $confname, "value" => $valueToSave ));
            }

        }

    }
    logAdminActivity("Gateway Module Configuration Modified: '" . $GatewayConfig[$module]["FriendlyName"]["Value"] . "'");
    redir("updated=" . $module . "#m_" . $module);
}

if( $action == "moveup" ) 
{
    check_token("WHMCS.admin.default");
    $result = select_query("tblpaymentgateways", "", array( "`order`" => $order ));
    $data = mysql_fetch_array($result);
    $gateway = $data["gateway"];
    $order1 = $order - 1;
    update_query("tblpaymentgateways", array( "order" => $order ), array( "`order`" => $order1 ));
    update_query("tblpaymentgateways", array( "order" => $order1 ), array( "gateway" => $gateway ));
    logAdminActivity("Gateway Module Sorting Changed: Moved Up - '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'");
    redir("sortchange=1");
}

if( $action == "movedown" ) 
{
    check_token("WHMCS.admin.default");
    $result = select_query("tblpaymentgateways", "", array( "`order`" => $order ));
    $data = mysql_fetch_array($result);
    $gateway = $data["gateway"];
    $order1 = $order + 1;
    update_query("tblpaymentgateways", array( "order" => $order ), array( "`order`" => $order1 ));
    update_query("tblpaymentgateways", array( "order" => $order1 ), array( "gateway" => $gateway ));
    logAdminActivity("Gateway Module Sorting Changed: Moved Down - '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'");
    redir("sortchange=1");
}

$result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
$i = 0;
while ($currenciesarray[$i] = mysql_fetch_assoc($result))
    {
    $i++;
    }
    array_pop($currenciesarray);
    $promoHelper = new WHMCS\View\Admin\Marketplace\PromotionHelper();
    $promoHelper->hookIntoPage($aInt);
    if( $promoHelper->isPromoFetchRequest() ) 
    {
        $response = $promoHelper->fetchPromoContent($whmcs->get_req_var("partner"), $whmcs->get_req_var("promodata"));
        $aInt->setBodyContent($response);
    }
    else
    {
        ob_start();
        $showGatewayConfig = false;
        if( $whmcs->get_req_var("activated") ) 
        {
            $showGatewayConfig = true;
        }

        if( $whmcs->get_req_var("deactivated") ) 
        {
            infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "deactivatesuccess"));
            $showGatewayConfig = true;
        }

        if( $whmcs->get_req_var("updated") ) 
        {
            $showGatewayConfig = true;
        }

        if( $whmcs->get_req_var("sortchange") ) 
        {
            infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "sortchangesuccess"));
            $showGatewayConfig = true;
        }

        echo "\n<div role=\"tabpanel\">\n    <ul class=\"nav nav-tabs\" role=\"tablist\">\n        <li role=\"presentation\"";
        if( !$showGatewayConfig ) 
        {
            echo " class=\"active\"";
        }

        echo ">\n            <a href=\"#featured\" id=\"btnFeaturedGateways\" aria-controls=\"home\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fa fa-star\"></i> Featured Payment Gateways\n            </a>\n        </li>\n        <li role=\"presentation\">\n            <a href=\"#all\" id=\"btnViewAllGateways\" aria-controls=\"profile\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fa fa-plus\"></i> All Payment Gateways\n            </a>\n        </li>\n        <li role=\"presentation\"";
        if( $showGatewayConfig ) 
        {
            echo " class=\"active\"";
        }

        echo ">\n            <a href=\"#manage\" id=\"btnManageGateways\" aria-controls=\"messages\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fa fa-wrench\"></i> Manage Existing Gateways\n            </a>\n        </li>\n    </ul>\n    <br />\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane fade in";
        if( !$showGatewayConfig ) 
        {
            echo " active";
        }

        echo "\" id=\"featured\">\n\n            <div class=\"partner-box\">\n                <div class=\"row\">\n                    <div class=\"col-md-3\">\n                        <div class=\"partner-logo\" onclick=\"showPromo('paypal')\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/paypal.gif\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-7 partner-features\">\n                        <div class=\"partner-headline\">\n                            PayPal is one of the simplest and quickest ways for your customers to pay.\n                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-sm-11 col-sm-offset-1\">\n                                <div class=\"row\">\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fa fa-check\"></i> Get Paid On Time<br />\n                                        <i class=\"fa fa-check\"></i> Express Checkout Supported\n                                    </div>\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fa fa-check\"></i> Automatic Subscription Billing<br />\n                                        <i class=\"fa fa-check\"></i> One-Click Refunds\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 text-center partner-actions\">\n                        <button class=\"btn btn-info\" onclick=\"showPromo('paypal')\">\n                            ";
        echo AdminLang::trans("global.learnMore");
        echo "                        </button>\n                    </div>\n                </div>\n            </div>\n\n            <div class=\"partner-box partner-box-blue\">\n                <div class=\"row\">\n                    <div class=\"col-md-3\">\n                        <div class=\"partner-logo\" onclick=\"showPromo('authorizenet')\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/authorizenet.png\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-7 partner-features\">\n                        <div class=\"partner-headline\">\n                            Accept Credit Cards simply and securely with Authorize.net powered by EVO Payments.\n                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-sm-11 col-sm-offset-1\">\n                                <div class=\"row\">\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fa fa-check\"></i> Automated Recurring Billing<br />\n                                        <i class=\"fa fa-check\"></i> Secure Tokenised Card Storage\n                                    </div>\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fa fa-check\"></i> Seamless Checkout Experience<br />\n                                        <i class=\"fa fa-check\"></i> Best Rates Guaranteed\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 text-center partner-actions\">\n                        <button class=\"btn btn-info\" onclick=\"showPromo('authorizenet')\">\n                            ";
        echo AdminLang::trans("global.learnMore");
        echo "                        </button>\n                    </div>\n                </div>\n            </div>\n\n            <div class=\"row\">\n                <div class=\"col-md-2\"></div>\n                <div class=\"col-md-4\">\n                    <div style=\"margin:0 0 10px 0;padding:10px 15px;background-color:#fff;border-radius:6px;\" class=\"text-center\">\n                        <div style=\"height:70px;line-height:60px;\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/2checkout.gif\">\n                        </div>\n                        2CheckOut provides a secure hosted checkout process so you can accept payments without any of the hassles of PCI Compliance.<br />\n                        <div class=\"top-margin-10\">\n                            <button class=\"btn btn-default\" onclick=\"showPromo('2checkout')\">\n                                ";
        echo AdminLang::trans("global.learnMore");
        echo "                            </button>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"col-md-4\">\n                    <div style=\"margin:0 0 10px 0;padding:10px 15px;background-color:#fff;border-radius:6px;\" class=\"text-center\">\n                        <div style=\"height:70px;line-height:60px;\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/skrill.gif\">\n                        </div>\n                        Trusted by millions across the globe Skrill allows you to pay and get paid in nearly 200 countries and 40 currencies.<br />\n                        <div class=\"top-margin-10\">\n                            <button class=\"btn btn-default\" onclick=\"showPromo('skrill')\">\n                                ";
        echo AdminLang::trans("global.learnMore");
        echo "                            </button>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"col-md-2\"></div>\n            </div>\n\n            <p class=\"text-center text-muted\" style=\"background-color:#efefef;padding:6px;\">Looking for a payment gateway not listed above? View the <a href=\"#\" onclick=\"\$('#btnViewAllGateways').click()\" class=\"btn btn-warning btn-xs\">full list of payment gateways</a> we integrate with.</p>\n\n            <p class=\"text-center text-muted\"><small>There are many more payment gateways that, although not included in WHMCS by default, have modules for WHMCS. Many of those can be found in our <a href=\"https://marketplace.whmcs.com/\" target=\"_blank\">Marketplace</a>.</small></p>\n\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane fade\" id=\"all\">\n\n            <p>Click on a payment gateway below to activate and begin using it. Already active payment gateways will appear in green.</p>\n\n            <div class=\"row\">\n                <div class=\"clearfix\" style=\"background-color:#f8f8f8;margin:0 0 20px 0;padding:20px 0;\">\n                    <div class=\"col-xs-10 col-xs-offset-1\">\n                        <div class=\"row\">\n\n";
        sort($AllGateways);
        $output = array(  );
        foreach( $AllGateways as $modulename ) 
        {
            $displayName = $GatewayConfig[$modulename]["FriendlyName"]["Value"];
            $isActive = in_array($modulename, $ActiveGateways);
            $btnDisabled = ($isActive ? " disabled" : "");
            $output[strtolower($displayName)] = "<div class=\"col-md-3 col-sm-6 text-center\" style=\"margin-bottom:5px;\">\n            <form method=\"post\" action=\"configgateways.php\">\n                <input type=\"hidden\" name=\"action\" value=\"activate\" />\n                <input type=\"hidden\" name=\"gateway\" value=\"" . $modulename . "\" />\n                <button type=\"submit\" id=\"btnActivate-" . $modulename . "\" class=\"btn btn-" . (($isActive ? "success" : "default")) . " btn-sm btn-block\"" . $btnDisabled . ">\n                    " . $displayName . "\n                </button>\n            </form>\n        </div>" . PHP_EOL;
        }
        ksort($output);
        echo implode($output);
        echo "                        </div>\n                    </div>\n                </div>\n            </div>\n\n            <p class=\"text-center text-muted\">Can't find the payment gateway you're looking for? Take a look at our <a href=\"https://marketplace.whmcs.com/product/category/Payment+Gateways\" target=\"_blank\">Marketplace</a> for gateways with third party modules.</p>\n\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane fade";
        if( $showGatewayConfig ) 
        {
            echo " in active";
        }

        echo "\" id=\"manage\">\n\n";
        echo ($infobox ? $infobox . "<br />" : "");
        $count = 1;
        $newgateways = "";
        $data = get_query_vals("tblpaymentgateways", "COUNT(gateway)", array( "setting" => "name" ));
        $numgateways = $data[0];
        $result3 = select_query("tblpaymentgateways", "", array( "setting" => "name" ), "order", "ASC");
        while( $data = mysql_fetch_array($result3) ) 
        {
            $module = $data["gateway"];
            $order = $data["order"];
            echo "\n<form id=\"frmActivateGatway\" method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=save\">\n<input type=\"hidden\" name=\"module\" value=\"";
            echo $module;
            echo "\">\n\n";
            $isModuleDisabled = false;
            if( isset($GatewayConfig[$module]) ) 
            {
                $modName = $GatewayConfig[$module]["FriendlyName"]["Value"];
            }
            else
            {
                $modName = $module;
                $isModuleDisabled = true;
            }

            echo "<a name=\"m_" . $module . "\"></a><h2>" . $count . ". " . $modName;
            if( $numgateways != "1" ) 
            {
                echo " <a href=\"#\" onclick=\"deactivateGW('" . $module . "','" . $GatewayConfig[$module]["FriendlyName"]["Value"] . "');return false\" style=\"color:#cc0000\">(" . $aInt->lang("gateways", "deactivate") . ")</a> ";
            }

            if( $order != "1" ) 
            {
                echo "<a href=\"?action=moveup&order=" . $order . generate_token("link") . "\"><img src=\"images/moveup.gif\" align=\"absmiddle\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"></a> ";
            }

            if( $order != $lastorder ) 
            {
                echo "<a href=\"?action=movedown&order=" . $order . generate_token("link") . "\"><img src=\"images/movedown.gif\" align=\"absmiddle\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"></a>";
            }

            echo "</h2>";
            if( $whmcs->get_req_var("activated") == $module ) 
            {
                infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "activatesuccess"));
                echo $infobox;
            }
            else
            {
                if( $whmcs->get_req_var("updated") == $module ) 
                {
                    infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "savesuccess"), "success");
                    echo $infobox;
                }

            }

            if( $isModuleDisabled === true ) 
            {
                echo "    <p style=\"border: 2px solid red; padding: 10px\"><strong>";
                echo $aInt->lang("gateways", "moduleunavailable");
                echo "</strong></p>\n";
            }
            else
            {
                echo "</p>\n<table class=\"form\" id=\"Payment-Gateway-Config-";
                echo $module;
                echo "\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"300\" class=\"fieldlabel\">";
                echo $aInt->lang("gateways", "showonorderform");
                echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"field[visible]\"";
                if( $GatewayValues[$module]["visible"] ) 
                {
                    echo " checked";
                }

                echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo $aInt->lang("gateways", "displayname");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"field[name]\" size=\"30\" class=\"form-control input-inline input-300\" value=\"";
                echo htmlspecialchars($GatewayValues[$module]["name"]);
                echo "\"></td></tr>\n";
                foreach( $GatewayConfig[$module] as $confname => $values ) 
                {
                    if( $values["Type"] != "System" ) 
                    {
                        $values["Name"] = "field[" . $confname . "]";
                        if( isset($GatewayValues[$module][$confname]) ) 
                        {
                            $values["Value"] = $GatewayValues[$module][$confname];
                        }

                        echo "<tr>\n                <td class=\"fieldlabel\">" . $values["FriendlyName"] . "</td>\n                <td class=\"fieldarea\">" . moduleConfigFieldOutput($values) . "</td>\n            </tr>";
                    }

                }
                if( 1 < count($currenciesarray) ) 
                {
                    echo "<tr><td class=\"fieldlabel\">" . $aInt->lang("gateways", "currencyconvert") . "</td><td class=\"fieldarea\"><select name=\"field[convertto]\" class=\"form-control select-inline\"><option value=\"\">" . $aInt->lang("global", "none") . "</option>";
                    foreach( $currenciesarray as $currencydata ) 
                    {
                        echo "<option value=\"" . $currencydata["id"] . "\"";
                        if( isset($GatewayValues[$module]["convertto"]) && $currencydata["id"] == $GatewayValues[$module]["convertto"] ) 
                        {
                            echo " selected";
                        }

                        echo ">" . $currencydata["code"] . "</option>";
                    }
                    echo "</select></td></tr>";
                }

                echo "<tr><td class=\"fieldlabel\"></td><td class=\"fieldarea\"><input type=\"submit\" value=\"";
                echo $aInt->lang("global", "savechanges");
                echo "\" class=\"btn btn-primary\">";
                if( $GatewayConfig[$module]["UsageNotes"]["Value"] ) 
                {
                    echo " (" . $GatewayConfig[$module]["UsageNotes"]["Value"] . ")";
                }

                echo "</td></tr>\n</table>\n";
            }

            echo "<br />\n\n</form>\n\n";
            if( $count != $order ) 
            {
                update_query("tblpaymentgateways", array( "order" => $count ), array( "setting" => "name", "gateway" => $module ));
            }

            $count++;
            $newgateways .= "<option value=\"" . $module . "\">" . $GatewayConfig[$module]["FriendlyName"]["Value"] . "</option>";
        }
        if( count($ActiveGateways) < 1 ) 
        {
            echo "<p class=\"alert alert-danger\"><strong>" . $aInt->lang("gateways", "noGatewaysActive") . "</strong> " . $aInt->lang("gateways", "activateGatewayFirst") . "</p>";
        }

        echo "\n        </div>\n    </div>\n</div>\n\n";
        $jscode .= "var gatewayOptions = \"" . addslashes($newgateways) . "\";\nfunction deactivateGW(module,friendlyname) {\n    \$(\"#inputDeactivateGatewayName\").val(module);\n    \$(\"#inputFriendlyGatewayName\").val(friendlyname);\n    \$(\"#inputNewGateway\").html(gatewayOptions);\n    \$(\"#inputNewGateway option[value='\"+module+\"']\").remove();\n    \$(\"#modalDeactivateGateway\").modal(\"show\");\n}";
        echo $aInt->modal("DeactivateGateway", $aInt->lang("gateways", "deactivatemodule"), "<p>" . $aInt->lang("gateways", "deactivatemoduleinfo") . "</p>\n<form method=\"post\" action=\"configgateways.php?action=deactivate\" id=\"frmDeactivateGateway\">\n    <input type=\"hidden\" name=\"gateway\" value=\"\" id=\"inputDeactivateGatewayName\">\n    <input type=\"hidden\" name=\"friendlygateway\" value=\"\" id=\"inputFriendlyGatewayName\">\n    <div class=\"text-center\">\n        <select id=\"inputNewGateway\" name=\"newgateway\" class=\"form-control select-inline\">\n            " . $newgateways . "\n        </select>\n    </div>\n</form>", array( array( "title" => $aInt->lang("gateways", "deactivate"), "onclick" => "\$(\"#frmDeactivateGateway\").submit()", "class" => "btn-primary" ), array( "title" => $aInt->lang("supportreq", "cancel") ) ));
        $content = ob_get_contents();
        ob_end_clean();
        $aInt->content = $content;
        $aInt->jquerycode = $jquerycode;
        $aInt->jscode = $jscode;
    }

    $aInt->display();
