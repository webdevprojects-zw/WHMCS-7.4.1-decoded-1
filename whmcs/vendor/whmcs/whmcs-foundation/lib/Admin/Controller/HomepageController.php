<?php 
namespace WHMCS\Admin\Controller;


class HomepageController
{
    public static function assertCurl()
    {
        if( !function_exists("curl_init") ) 
        {
            echo "<div style=\"border: 1px dashed #cc0000;font-family:Tahoma,sans-serif;background-color:#FBEEEB;width:100%;padding:10px;color:#cc0000;\"><strong>Critical Error</strong><br>CURL is not installed or is disabled on your server and it is required for WHMCS to run</div>";
            exit();
        }

    }

    public function refreshWidget(\WHMCS\Http\Message\ServerRequest $request)
    {
        $aInt = new \WHMCS\Admin("Main Homepage");
        $aInt->title = \AdminLang::trans("global.hometitle");
        $aInt->sidebar = "home";
        $aInt->icon = "home";
        $aInt->requiredFiles(array( "clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions", "processinvoices", "reportfunctions" ));
        $aInt->template = "homepage";
        try
        {
            $widgetInterface = new \WHMCS\Module\Widget();
            $widget = $widgetInterface->getWidgetByName(\App::getFromRequest("widget"));
            $refresh = (bool) $request->get("refresh");
            $widgetOutput = $widget->render($refresh);
            $js = "";
            foreach( $aInt->getChartFunctions() as $func ) 
            {
                if( strpos($widgetOutput, $func) !== false ) 
                {
                    $js .= $func . "();";
                }

            }
            if( !empty($js) ) 
            {
                $js = "<script>" . $js . "</script>";
            }

            return new \WHMCS\Http\Message\JsonResponse(array( "success" => true, "widgetOutput" => $widgetOutput . $js ));
        }
        catch( \Exception $e ) 
        {
            new \WHMCS\Http\Message\JsonResponse(array( "success" => false, "exceptionMsg" => $e->getMessage() ));
        }
        return $aInt;
    }

    public function saveNotes(\WHMCS\Http\Message\ServerRequest $request)
    {
        $notes = $request->get("notes");
        update_query("tbladmins", array( "notes" => $notes ), array( "id" => \WHMCS\Session::get("adminid") ));
        return new \WHMCS\Http\Message\JsonResponse(array( "status" => "success" ));
    }

    public function toggleWidgetDisplay(\WHMCS\Http\Message\ServerRequest $request)
    {
        $widget = $request->get("widget");
        try
        {
            $session = new \WHMCS\Session();
            $session->create(\WHMCS\Config\Setting::getValue("InstanceID"));
            $adminUser = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
            $currentWidgets = $adminUser->hiddenWidgets;
            if( !in_array($widget, $currentWidgets) ) 
            {
                $currentWidgets[] = $widget;
            }
            else
            {
                $currentWidgets = array_flip($currentWidgets);
                unset($currentWidgets[$widget]);
                $currentWidgets = array_flip($currentWidgets);
            }

            $adminUser->hiddenWidgets = $currentWidgets;
            $adminUser->save();
            return new \WHMCS\Http\Message\JsonResponse(array( "success" => true ));
        }
        catch( \Exception $e ) 
        {
            return new \WHMCS\Http\Message\JsonResponse(array( "success" => false, "widget" => $widget ));
        }
    }

    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        self::assertCurl();
        $licensing = \DI::make("license");
        if( !$licensing->checkOwnedUpdates() ) 
        {
            redir("licenseerror=version", "licenseerror.php");
        }

        if( !checkPermission("Main Homepage", true) ) 
        {
            redir("", "supportcenter.php");
        }

        $aInt = new \WHMCS\Admin("Main Homepage");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $session = new \WHMCS\Session();
        $session->create(\WHMCS\Config\Setting::getValue("InstanceID"));
        $aInt->title = \AdminLang::trans("global.hometitle");
        $aInt->sidebar = "home";
        $aInt->icon = "home";
        $aInt->requiredFiles(array( "clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions", "processinvoices", "reportfunctions" ));
        $aInt->template = "homepage";
        $whmcs = \App::self();
        if( $request->get("createinvoices") || $request->get("generateinvoices") ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Generate Due Invoices");
            $noemails = $request->get("noemails");
            global $invoicecount;
            createInvoices("", $noemails);
            redir("generatedinvoices=1&count=" . $invoicecount);
        }

        if( $request->get("generatedinvoices") ) 
        {
            infoBox(\AdminLang::trans("invoices.gencomplete"), (int) $request->get("count") . " Invoices Created");
        }

        if( $request->get("attemptccpayments") ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Attempts CC Captures");
            \WHMCS\Session::set("AdminHomeCCCaptureResultMsg", ccProcessing());
            redir("attemptedccpayments=1");
        }

        if( $request->get("attemptedccpayments") && \WHMCS\Session::get("AdminHomeCCCaptureResultMsg") ) 
        {
            infoBox(\AdminLang::trans("invoices.attemptcccapturessuccess"), \WHMCS\Session::get("AdminHomeCCCaptureResultMsg"));
            \WHMCS\Session::delete("AdminHomeCCCaptureResultMsg");
        }

        $updater = new \WHMCS\Installer\Update\Updater();
        $templatevars["licenseinfo"] = array( "registeredname" => $licensing->getRegisteredName(), "productname" => $licensing->getProductName(), "expires" => $licensing->getExpiryDate(), "currentversion" => $whmcs->getVersion()->getCasual(), "latestversion" => $updater->getLatestVersion()->getCasual(), "updateavailable" => $updater->isUpdateAvailable() && $aInt->hasPermission("Update WHMCS") );
        if( $licensing->getKeyData("productname") == "15 Day Free Trial" ) 
        {
            $templatevars["freetrial"] = true;
        }

        $templatevars["infobox"] = (isset($infobox) ? $infobox : "");
        $query = "SELECT COUNT(*) FROM tblpaymentgateways WHERE setting='type' AND value='CC'";
        $result = full_query($query);
        $data = mysql_fetch_array($result);
        if( $data[0] ) 
        {
            $templatevars["showattemptccbutton"] = true;
        }

        if( \WHMCS\Config\Setting::getValue("MaintenanceMode") ) 
        {
            $templatevars["maintenancemode"] = true;
        }

        $templatevars["widgets"] = (new \WHMCS\Module\Widget())->getAllWidgets();
        $adminUser = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
        $templatevars["hiddenWidgets"] = $adminUser->hiddenWidgets;
        $templatevars["generateInvoices"] = $aInt->modal("GenerateInvoices", \AdminLang::trans("invoices.geninvoices"), \AdminLang::trans("invoices.geninvoicessendemails"), array( array( "title" => \AdminLang::trans("global.yes"), "onclick" => "window.location=\"index.php?generateinvoices=true" . generate_token("link") . "\"", "class" => "btn-primary" ), array( "title" => \AdminLang::trans("global.no"), "onclick" => "window.location=\"index.php?generateinvoices=true&noemails=true" . generate_token("link") . "\"" ) ));
        $templatevars["creditCardCapture"] = $aInt->modal("CreditCardCapture", \AdminLang::trans("invoices.attemptcccaptures"), \AdminLang::trans("invoices.attemptcccapturessure"), array( array( "title" => \AdminLang::trans("global.yes"), "onclick" => "window.location=\"index.php?attemptccpayments=true" . generate_token("link") . "\"", "class" => "btn-primary" ), array( "title" => \AdminLang::trans("global.no") ) ));
        $addons_html = run_hook("AdminHomepage", array(  ));
        $templatevars["addons_html"] = $addons_html;
        $roleId = get_query_val("tbladmins", "roleid", array( "id" => (int) \WHMCS\Session::get("adminid") ));
        if( $roleId == 1 ) 
        {
            $twoFactor = new \WHMCS\TwoFactorAuthentication();
            if( ($twoFactor->isActiveClients() || $twoFactor->isActiveAdmins()) && in_array("duosecurity", $twoFactor->getAvailableModules()) ) 
            {
                if( !class_exists("WHMCS_DuoSecurity") ) 
                {
                    require_once(ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "security" . DIRECTORY_SEPARATOR . "duosecurity" . DIRECTORY_SEPARATOR . "duosecurity.php");
                }

                $twoFactorSettings = \WHMCS\Config\Setting::getValue("2fasettings");
                $twoFactorSettings = safe_unserialize($twoFactorSettings);
                if( !is_array($twoFactorSettings) ) 
                {
                    $twoFactorSettings = array(  );
                }

                $twoFactorSettings = $twoFactorSettings["modules"]["duosecurity"];
                if( !duosecurity_isConfigurationCustom($twoFactorSettings["integrationKey"], $twoFactorSettings["secretKey"], $twoFactorSettings["apiHostname"]) ) 
                {
                    if( !is_array($addons_html) ) 
                    {
                        $addons_html = array(  );
                    }

                    $daysUntilDeprecation = duosecurity_daysUntilDeprecation();
                    $dayOrDays = ($daysUntilDeprecation != 1 ? "days" : "day");
                    if( 0 < $daysUntilDeprecation ) 
                    {
                        $duoNotice = "    <div class=\"alert alert-warning alert-dismissible\" role=\"alert\">\n    <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">\n        <span aria-hidden=\"true\">&times;</span>\n    </button>\n        <strong><i class=\"fa fa-exclamation-triangle fa-fw\"></i> DuoSecurity Action Required</strong><br />\n        You have " . $daysUntilDeprecation . " " . $dayOrDays . " remaining to configure DuoSecurity account credentials. Failure to do this will result in DuoSecurity Two-Factor Authentication being unavailable. Please act now to avoid interruption in service.<br /><a href=\"configtwofa.php\" class=\"alert-link\">Go to configuration &raquo;</a>\n</div>";
                    }
                    else
                    {
                        $duoNotice = "<div class=\"alert alert-danger alert-dismissible\" role=\"alert\">\n    <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">\n        <span aria-hidden=\"true\">&times;</span>\n    </button>\n    <strong><i class=\"fa fa-exclamation-triangle fa-fw\"></i> DuoSecurity Action Required</strong><br />\n    DuoSecurity protection is currently disabled. You must create and enter DuoSecurity account credentials to continue using the service.<br /><a href=\"configtwofa.php\" class=\"alert-link\">Go to configuration &raquo;</a>\n</div>";
                    }

                    $templatevars["addons_html"] = array_merge(array( $duoNotice ), $addons_html);
                }

                unset($twoFactorSettings);
            }

            unset($twoFactor);
            if( !\WHMCS\Config\Setting::getValue("DisableSetupWizard") ) 
            {
                $aInt->addHeadJqueryCode("openSetupWizard();");
            }
            else
            {
                if( $aInt->hasPermission("View What's New") && $aInt->shouldSeeFeatureHighlights() ) 
                {
                    $aInt->addHeadJqueryCode("openFeatureHighlights();");
                }

            }

        }

        $licensing = \DI::make("license");
        if( $licensing->isClientLimitsEnabled() ) 
        {
            $templatevars["licenseinfo"]["productname"] .= " (" . $licensing->getTextClientLimit() . ")";
        }

        if( isset($jscode) ) 
        {
            $aInt->jscode = $jscode;
        }

        if( isset($jquerycode) ) 
        {
            $aInt->jquerycode = $jquerycode;
        }

        $aInt->templatevars = $templatevars;
        return $aInt->display();
    }

}


