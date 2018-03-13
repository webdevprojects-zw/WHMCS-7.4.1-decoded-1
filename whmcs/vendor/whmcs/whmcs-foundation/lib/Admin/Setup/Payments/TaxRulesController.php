<?php 
namespace WHMCS\Admin\Setup\Payments;


class TaxRulesController
{
    public function saveSettings(\WHMCS\Http\Message\ServerRequest $request)
    {
        $taxEnabled = $request->request()->get("taxenabled", "");
        $taxType = $request->request()->get("taxtype", "");
        $taxDomains = $request->request()->get("taxdomains", "");
        $taxBillableItems = $request->request()->get("taxbillableitems", "");
        $taxLateFee = $request->request()->get("taxlatefee", "");
        $taxCustomInvoices = $request->request()->get("taxcustominvoices", "");
        $taxL2Compound = $request->request()->get("taxl2compound", "");
        $taxInclusiveDeduct = $request->request()->get("taxinclusivededuct", "");
        $taxSettings = array( "TaxEnabled" => $taxEnabled, "TaxType" => $taxType, "TaxDomains" => $taxDomains, "TaxBillableItems" => $taxBillableItems, "TaxLateFee" => $taxLateFee, "TaxCustomInvoices" => $taxCustomInvoices, "TaxL2Compound" => $taxL2Compound, "TaxInclusiveDeduct" => $taxInclusiveDeduct );
        if( $taxEnabled != \WHMCS\Config\Setting::getValue("TaxEnabled") ) 
        {
            if( $taxEnabled ) 
            {
                logAdminActivity("Tax Configuration: Tax Support Enabled");
            }
            else
            {
                logAdminActivity("Tax Configuration: Tax Support Disabled");
            }

        }

        $changes = array(  );
        foreach( $taxSettings as $k => $v ) 
        {
            if( $k != "TaxEnabled" && \WHMCS\Config\Setting::getValue($k) != $v ) 
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

            \WHMCS\Config\Setting::setValue($k, $v);
        }
        if( $changes ) 
        {
            logAdminActivity("Tax Configuration: " . implode(". ", $changes) . ".");
        }

        return new \WHMCS\Http\Message\JsonResponse(array( "status" => "success" ));
    }

    public function create(\WHMCS\Http\Message\ServerRequest $request)
    {
        $name = $request->request()->get("name", "");
        $state = $request->request()->get("state", "");
        $country = $request->request()->get("country", "");
        $taxRate = $request->request()->get("taxrate", "");
        $level = (int) $request->request()->get("level", "");
        $countryType = $request->request()->get("countrytype", "");
        $stateType = $request->request()->get("statetype", "");
        if( $countryType == "any" && $stateType != "any" ) 
        {
            $errorHtml = infoBox(\AdminLang::trans("global.validationerror"), \AdminLang::trans("taxconfig.taxvalidationerrorcountry"));
            $view = $this->index($request);
            $view->setBodyContent($errorHtml . $view->getBodyContent());
        }
        else
        {
            if( $countryType == "any" ) 
            {
                $country = "";
            }

            if( $stateType == "any" ) 
            {
                $state = "";
            }

            logAdminActivity("Tax Configuration: Level " . $level . " Rule Added: " . $name);
            insert_query("tbltax", array( "level" => $level, "name" => $name, "state" => $state, "country" => $country, "taxrate" => $taxRate ));
            $view = $this->index($request);
        }

        return $view;
    }

    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        $id = (int) $request->get("id", 0);
        $taxRule = \Illuminate\Database\Capsule\Manager::table("tbltax")->find($id);
        if( $taxRule ) 
        {
            logAdminActivity("Tax Configuration: Level " . $taxRule->level . " Rule Deleted: " . $taxRule->name);
            \Illuminate\Database\Capsule\Manager::table("tbltax")->delete($id);
            return new \WHMCS\Http\Message\JsonResponse(array( "status" => "success" ));
        }

        return new \WHMCS\Http\Message\JsonResponse(array( "status" => "error" ));
    }

    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper())->setTitle(\AdminLang::trans("taxconfig.taxrulestitle"))->setSidebarName("config")->setFavicon("taxrules")->setHelpLink("Tax/VAT");
        $content = "";
        if( $request->get("saved") ) 
        {
            $content .= infoBox(\AdminLang::trans("global.changesuccess"), \AdminLang::trans("global.changesuccessdesc"));
        }

        $content .= $this->pageSummaryHtml();
        $tabContent = new \WHMCS\Admin\ApplicationSupport\View\Html\Helper\TabbedContent();
        $tabContent->setActiveTabId($request->get("tab", 1));
        $sortableTable = new \WHMCS\Admin\ApplicationSupport\View\Html\Helper\SortableTable($request);
        $columnOrderBy = $request->get("orderby", "");
        $table1 = $sortableTable->factoryPaginatedTable("admin-tax-rule-l1-", $columnOrderBy);
        if( !$table1->getOrderBy() ) 
        {
            $table1->setOrderBy("country");
        }

        $table1->setRowLimit(10);
        $table1->setRowsOfData($this->taxRuleQuery(1)->count());
        $page = $request->get("page", 0);
        $table1->setPage($page);
        $tabContent->addTabContent(\AdminLang::trans("taxconfig.level1rules"), $table1->getHtml(array( array( "name", \AdminLang::trans("fields.name") ), array( "country", \AdminLang::trans("fields.country") ), array( "state", \AdminLang::trans("fields.state") ), array( "taxrate", \AdminLang::trans("fields.taxrate") ), "" ), $this->getTaxHtmlTableData(1, $page, $table1->getOrderBy(), $table1->getOrderDirection())));
        $view->addJquery($table1->getJqueryCode());
        $columnOrderBy = $request->get("orderby", "");
        $table1 = $sortableTable->factoryPaginatedTable("admin-tax-rule-l2-", $columnOrderBy);
        if( !$table1->getOrderBy() ) 
        {
            $table1->setOrderBy("country");
        }

        $table1->setRowLimit(10);
        $table1->setRowsOfData($this->taxRuleQuery(2)->count());
        $page = $request->get("page", 0);
        $table1->setPage($page);
        $tabContent->addTabContent(\AdminLang::trans("taxconfig.level2rules"), $table1->getHtml(array( array( "name", \AdminLang::trans("fields.name") ), array( "country", \AdminLang::trans("fields.country") ), array( "state", \AdminLang::trans("fields.state") ), array( "taxrate", \AdminLang::trans("fields.taxrate") ), "" ), $this->getTaxHtmlTableData(2, $page, $table1->getOrderBy(), $table1->getOrderDirection())));
        $view->addJquery($table1->getJqueryCode());
        $tabContent->addTabContent(\AdminLang::trans("taxconfig.addnewrule"), $this->getAddRuleFormHtml());
        $view->addJquery($tabContent->getJQueryCode());
        $content .= $tabContent->getFormattedBodyContent();
        $content .= \WHMCS\View\Asset::jsInclude("StatesDropdown.js");
        $view->addJavascript("var stateNotRequired = true;" . PHP_EOL);
        $view->addHtmlHeadElement($tabContent->getFormattedHtmlHeadContent());
        $view->setBodyContent($content);
        $successTitle = \AdminLang::trans("global.changesuccess");
        $successDesc = \AdminLang::trans("global.changesuccessdesc");
        $deleteConfirmTitle = \AdminLang::trans("global.delete");
        $deleteConfirmDesc = \AdminLang::trans("taxconfig.delsuretaxrule");
        $jqueryCode = "jQuery(\".deleteRule\").click(\n    function(e) {\n        e.preventDefault();\n        var anchor = \$(this);\n        swal(\n            {\n                title: \"" . $deleteConfirmTitle . "\",\n                text: \"" . $deleteConfirmDesc . "\",\n                type: \"warning\",\n                showCancelButton: true,\n                confirmButtonColor: \"#DD6B55\",\n                closeOnConfirm: false,\n                showLoaderOnConfirm: true\n            },\n            function() {\n                jQuery.post(\n                    anchor.data('href'),\n                    {\n                        id: anchor.data('id'),\n                        token: csrfToken\n                    }\n                )\n                .done(function() {location.reload(true);});\n            }\n        );              \n    }\n);\n\njQuery(\"#country\").on(\n    \"change\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"countrytype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"countrytype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"focus\",\n    \"#stateinput\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"change\",\n    \"#stateselect\",\n    function()\n    {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() == 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);\n\njQuery('#frmTaxSettings').submit(function(e){\n    e.preventDefault();\n    var form = \$(this),\n        url = form.attr(\"action\")\n        data = form.serialize();\n    \n    \$.post(\n        url,\n        data,\n        function () {\n            swal(\"" . $successTitle . "\", \"" . $successDesc . "\", \"success\");\n        }\n    );\n});";
        $view->addJquery($jqueryCode);
        return $view;
    }

    protected function pageSummaryHtml()
    {
        $taxEnabledAttribute = (\WHMCS\Config\Setting::getValue("TaxEnabled") ? " checked" : "");
        $exclusiveTaxAttribute = (\WHMCS\Config\Setting::getValue("TaxType") == "Exclusive" ? " checked" : "");
        $inclusiveTaxAttribute = (\WHMCS\Config\Setting::getValue("TaxType") == "Inclusive" ? " checked" : "");
        $taxDomainsAttribute = (\WHMCS\Config\Setting::getValue("TaxDomains") == "on" ? " checked" : "");
        $taxBillableItems = (\WHMCS\Config\Setting::getValue("TaxBillableItems") == "on" ? " checked" : "");
        $taxLateFees = (\WHMCS\Config\Setting::getValue("TaxLateFee") == "on" ? " checked" : "");
        $taxCustomInvoices = (\WHMCS\Config\Setting::getValue("TaxCustomInvoices") == "on" ? " checked" : "");
        $taxL2Compound = (\WHMCS\Config\Setting::getValue("TaxL2Compound") == "on" ? " checked" : "");
        $taxInclusiveDeduct = (\WHMCS\Config\Setting::getValue("TaxInclusiveDeduct") == "on" ? " checked" : "");
        $html = "<p>" . \AdminLang::trans("taxconfig.taxrulesconfigheredesc") . "</p>" . "<form id=\"frmTaxSettings\" name=\"frmTaxSettings\" method=\"post\" action=\"" . routePath("admin-setup-payments-taxrules-settings") . "\">" . "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">" . "<tr>" . "<td class=\"fieldlabel\">" . \AdminLang::trans("taxconfig.taxsupportenabled") . "</td>" . "<td class=\"fieldarea\"><label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxenabled\" id=\"taxenabled\" " . $taxEnabledAttribute . ">" . \AdminLang::trans("taxconfig.taxsupportenableddesc") . "</label></td></tr>" . "<tr>" . "<td class=\"fieldlabel\">" . \AdminLang::trans("taxconfig.taxtype") . "</td>" . "<td class=\"fieldarea\"><label class=\"radio-inline\">" . "<input type=\"radio\" name=\"taxtype\" value=\"Exclusive\" " . $exclusiveTaxAttribute . ">" . \AdminLang::trans("taxconfig.taxtypeexclusive") . "</label> " . "<label class=\"radio-inline\">" . "<input type=\"radio\" name=\"taxtype\" value=\"Inclusive\" " . $inclusiveTaxAttribute . ">" . \AdminLang::trans("taxconfig.taxtypeinclusive") . "</label></td></tr>" . "<tr>" . "<td class=\"fieldlabel\">" . \AdminLang::trans("taxconfig.taxappliesto") . "</td>" . "<td class=\"fieldarea\"><label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxdomains\" " . $taxDomainsAttribute . ">" . \AdminLang::trans("taxconfig.taxdomains") . "</label> " . "<label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxbillableitems\" " . $taxBillableItems . ">" . \AdminLang::trans("taxconfig.taxbillableitems") . "</label> " . "<label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxlatefee\" " . $taxLateFees . ">" . \AdminLang::trans("taxconfig.taxlatefee") . "</label> " . "<label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxcustominvoices\" " . $taxCustomInvoices . ">" . \AdminLang::trans("taxconfig.taxcustominvoices") . "</label> " . "(" . \AdminLang::trans("taxconfig.taxproducts") . ")" . "</td></tr>" . "<tr>" . "<td class=\"fieldlabel\">" . \AdminLang::trans("taxconfig.compoundtax") . "</td>" . "<td class=\"fieldarea\"><label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxl2compound\" " . $taxL2Compound . ">" . \AdminLang::trans("taxconfig.compoundtaxdesc") . "</label></td></tr>" . "<tr>" . "<td class=\"fieldlabel\">" . \AdminLang::trans("taxconfig.deducttaxamount") . "</td>" . "<td class=\"fieldarea\"><label class=\"checkbox-inline\">" . "<input type=\"checkbox\" name=\"taxinclusivededuct\" " . $taxInclusiveDeduct . ">" . \AdminLang::trans("taxconfig.deducttaxamountdesc") . "</label></td></tr>" . "</table>" . "<div class=\"btn-container\">" . "<input id=\"btnTaxSettingsSubmit\" type=\"submit\" value=\"" . \AdminLang::trans("global.savechanges") . "\" class=\"btn btn-primary\" />" . "</div>" . "</form>" . "<br/>";
        return $html;
    }

    protected function getAddRuleFormHtml()
    {
        include_once(ROOTDIR . "/includes/clientfunctions.php");
        $countryDropDown = getCountriesDropDown($_POST["country"]);
        ob_start();
        echo "       <form method=\"post\" action=\"";
        echo routePath("admin-setup-payments-taxrules-create");
        echo "\">\n           <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n               <tr><td width=\"15%\" class=\"fieldlabel\">Level</td><td class=\"fieldarea\"><select name=\"level\"><option>1</option><option";
        if( $_POST["level"] == 2 ) 
        {
            echo " selected";
        }

        echo ">2</option></select></td></tr>\n               <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("fields.name");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" size=\"30\" value=\"";
        echo $_POST["name"];
        echo "\" ></td></tr>\n               <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("fields.country");
        echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"countrytype\" value=\"any\" checked> ";
        echo \AdminLang::trans("taxconfig.taxappliesallcountry");
        echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"countrytype\" value=\"specific\"";
        if( $_POST["countrytype"] == "specific" ) 
        {
            echo " checked";
        }

        echo "> ";
        echo \AdminLang::trans("taxconfig.taxappliesspecificcountry");
        echo ":</label>";
        echo $countryDropDown;
        echo "</td></tr>\n               <tr><td class=\"fieldlabel\">State</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"statetype\" value=\"any\" checked> ";
        echo \AdminLang::trans("taxconfig.taxappliesallstate");
        echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"statetype\" value=\"specific\"";
        if( $_POST["statetype"] == "specific" ) 
        {
            echo " checked";
        }

        echo "> ";
        echo \AdminLang::trans("taxconfig.taxappliesspecificstate");
        echo ":</label> <input type=\"text\" name=\"state\" data-selectinlinedropdown=\"1\" size=\"25\" value=\"";
        echo $_POST["state"];
        echo "\" /></td></tr>\n               <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("fields.taxrate");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"taxrate\" size=\"10\" value=\"";
        echo (isset($_POST["taxrate"]) ? $_POST["taxrate"] : "0.00");
        echo "\" /> %</td></tr>\n           </table>\n           <div class=\"btn-container\">\n               <input type=\"submit\" value=\"";
        echo \AdminLang::trans("taxconfig.addrule");
        echo "\" class=\"button btn btn-primary\" />\n           </div>\n       </form>\n       ";
        return ob_get_clean();
    }

    protected function taxRuleQuery($level)
    {
        return \Illuminate\Database\Capsule\Manager::table("tbltax")->where("level", $level);
    }

    protected function getTaxHtmlTableData($level = 1, $page = 0, $orderBy = NULL, $orderDirection = "ASC")
    {
        $countries = new \WHMCS\Utility\Country();
        $countries = $countries->getCountryNameArray();
        $tableData = array(  );
        $limit = 3;
        $offset = $limit * $page;
        $query = $this->taxRuleQuery($level);
        if( $orderBy ) 
        {
            $query->orderBy($orderBy, $orderDirection);
        }

        try
        {
            $ruleSet = $query->orderBy("country", "asc")->orderBy("state", "asc")->limit($limit)->offset($offset)->get();
        }
        catch( \Exception $e ) 
        {
            $ruleSet = $this->taxRuleQuery($level)->orderBy("country", "asc")->orderBy("state", "asc")->limit($limit)->offset($offset)->get();
        }
        foreach( $ruleSet as $data ) 
        {
            if( array_key_exists($data->country, $countries) ) 
            {
                $country = $countries[$data->country];
            }
            else
            {
                $country = $data->country;
            }

            $state = $data->state;
            if( $state == "" ) 
            {
                $state = \AdminLang::trans("taxconfig.taxappliesanystate");
            }

            if( $country == "" ) 
            {
                $country = \AdminLang::trans("taxconfig.taxappliesanycountry");
            }

            $tableData[] = array( (string) $data->name, $country, $state, (string) $data->taxrate . "%", "<a class=\"deleteRule\" href=\"#\" " . " data-href=\"" . routePath("admin-setup-payments-taxrules-delete") . "\" " . " data-id=\"" . (string) $data->id . "\" " . "\">" . "<img src=\"images/delete.gif\" border=\"0\"></a>" );
        }
        return $tableData;
    }

}


