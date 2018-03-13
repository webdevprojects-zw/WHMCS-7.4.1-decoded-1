<?php 
function eu_vat_config()
{
    $soap_check_msg = "";
    if( !class_exists("SoapClient") ) 
    {
        $soap_check_msg = " (requires the PHP SOAP extension which is not currently compiled into your PHP build)";
    }

    $configarray = array( "name" => "EU VAT Addon", "version" => "2.1", "author" => "WHMCS", "language" => "english", "description" => "This addon allows you to configure a number of additional invoice/billing related options specific to EU invoicing & VAT requirements" . $soap_check_msg, "fields" => array(  ) );
    return $configarray;
}

function eu_vat_update_config_field($field)
{
    global $modulevars;
    if( isset($modulevars[$field]) ) 
    {
        update_query("tbladdonmodules", array( "value" => $_POST[$field] ), array( "module" => "eu_vat", "setting" => $field ));
    }
    else
    {
        insert_query("tbladdonmodules", array( "module" => "eu_vat", "setting" => $field, "value" => $_POST[$field] ));
    }

}

function eu_vat_output($vars)
{
    global $CONFIG;
    global $aInt;
    $whmcs = App::self();
    $modulevars = array(  );
    $result = select_query("tbladdonmodules", "", array( "module" => "eu_vat" ));
    while( $data = mysql_fetch_array($result) ) 
    {
        $modulevars[$data["setting"]] = $data["value"];
    }
    if( $_REQUEST["action"] == "save" ) 
    {
        eu_vat_update_config_field("enablevalidation");
        eu_vat_update_config_field("vatcustomfield");
        eu_vat_update_config_field("homecountry");
        eu_vat_update_config_field("taxexempt");
        eu_vat_update_config_field("notaxexempthome");
        eu_vat_update_config_field("enablecustominvoicenum");
        eu_vat_update_config_field("custominvoicenumber");
        eu_vat_update_config_field("custominvoicenumautoreset");
        eu_vat_update_config_field("sequentialpaidautoreset");
        eu_vat_update_config_field("enableinvoicedatepayment");
        update_query("tblconfiguration", array( "value" => $_POST["enblesequentialpaidinvoice"] ), array( "setting" => "SequentialInvoiceNumbering" ));
        update_query("tblconfiguration", array( "value" => $_POST["sequentialpaidnumber"] ), array( "setting" => "SequentialInvoiceNumberValue" ));
        $customInvoiceNumberFormat = $whmcs->get_req_var("custominvoicenumformat");
        $errorFields = array(  );
        if( $customInvoiceNumberFormat ) 
        {
            if( WHMCS\Invoice::isValidCustomInvoiceNumberFormat(WHMCS\Input\Sanitize::decode($customInvoiceNumberFormat)) ) 
            {
                eu_vat_update_config_field("custominvoicenumformat");
            }
            else
            {
                $errorFields[] = "customInvoiceNumberFormat";
            }

        }

        $sequentialPaidFormat = $whmcs->get_req_var("sequentialpaidformat");
        if( $sequentialPaidFormat ) 
        {
            if( WHMCS\Invoice::isValidCustomInvoiceNumberFormat(WHMCS\Input\Sanitize::decode($sequentialPaidFormat)) ) 
            {
                update_query("tblconfiguration", array( "value" => $sequentialPaidFormat ), array( "setting" => "SequentialInvoiceNumberFormat" ));
            }
            else
            {
                $errorFields[] = "sequentialPaidFormat";
            }

        }

        $errorRedirect = "";
        if( 0 < count($errorFields) ) 
        {
            $errorRedirect = "&error=true";
            WHMCS\Cookie::set("ErrorFields", $errorFields);
        }

        redir("module=eu_vat" . $errorRedirect);
    }

    $countries = array( AT, BE, BG, CY, CZ, DE, DK, EE, ES, FI, FR, GB, GR, HR, HU, IE, IT, LT, LU, LV, MT, NL, PL, PT, RO, SE, SI, SK );
    if( $_REQUEST["action"] == "setupvat" ) 
    {
        full_query("TRUNCATE tbltax");
        foreach( $countries as $country ) 
        {
            insert_query("tbltax", array( "level" => "1", "name" => $_POST["vatlabel"], "state" => "", "country" => $country, "taxrate" => $_POST["vatrate"] ));
        }
        redir("module=eu_vat");
    }

    $LANG = $vars["_lang"];
    $customfields = array( "Choose One..." );
    $result = select_query("tblcustomfields", "", array( "type" => "client" ));
    while( $data = mysql_fetch_array($result) ) 
    {
        $customfields[] = $data["fieldname"];
    }
    if( !count($customfields) ) 
    {
        $customfields[] = "No Custom Fields Found";
    }

    if( !class_exists("SoapClient") ) 
    {
        global $infobox;
        infoBox($LANG["soapwarningtitle"], $LANG["soapwarningdescription"] . " <a href=\"http://docs.whmcs.com/EU_VAT_Addon\" target=\"_blank\">" . $LANG["soapwarningdocslink"] . "</a>", "error");
        echo $infobox;
    }

    if( $whmcs->get_req_var("error") == true ) 
    {
        $errorFields = WHMCS\Cookie::get("ErrorFields", true);
        global $infobox;
        foreach( $errorFields as $field ) 
        {
            $infoBoxTitle = "";
            switch( WHMCS\Input\Sanitize::decode($field) ) 
            {
                case "customInvoiceNumberFormat":
                    $infoBoxTitle = $LANG["custinvoiceformatnumbering"] . " " . $aInt->lang("global", "validationerror");
                    break;
                case "sequentialPaidFormat":
                    $infoBoxTitle = $aInt->lang("general", "sequentialpaidformat") . " " . $aInt->lang("global", "validationerror");
                    break;
            }
            if( $infoBoxTitle && $infoBoxTitle != "" ) 
            {
                infoBox($infoBoxTitle, $aInt->lang("general", "sequentialPaidNumberValidationFail"), "error");
            }

            echo $infobox;
        }
        WHMCS\Cookie::delete("ErrorFields");
    }

    echo "\n<p>";
    echo $LANG["introtext"];
    echo "</p>\n\n<form method=\"post\" action=\"";
    echo $vars["modulelink"];
    echo "\">\n<input type=\"hidden\" name=\"action\" value=\"save\" />\n\n<p><b>";
    echo $LANG["vatvalidationheading"];
    echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"25%\" class=\"fieldlabel\">";
    echo $LANG["enable"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enablevalidation\"";
    if( $modulevars["enablevalidation"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["validationdesc"];
    echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $LANG["vatcustomfield"];
    echo "</td><td class=\"fieldarea\"><select name=\"vatcustomfield\" class=\"form-control select-inline\">";
    foreach( $customfields as $v ) 
    {
        echo "<option";
        if( $v == $modulevars["vatcustomfield"] ) 
        {
            echo " selected";
        }

        echo ">" . $v . "</option>";
    }
    echo "</select> ";
    echo $LANG["vatcustomfielddesc"];
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $LANG["homecountry"];
    echo "</td><td class=\"fieldarea\"><select name=\"homecountry\" class=\"form-control select-inline\">";
    foreach( $countries as $v ) 
    {
        echo "<option";
        if( $v == $modulevars["homecountry"] ) 
        {
            echo " selected";
        }

        echo ">" . $v . "</option>";
    }
    echo "</select> ";
    echo $LANG["homecountrydesc"];
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $LANG["taxexempt"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxexempt\"";
    if( $modulevars["taxexempt"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["taxexemptdesc"];
    echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $LANG["homecountryexcl"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"notaxexempthome\"";
    if( $modulevars["notaxexempthome"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["homecountryexcldesc"];
    echo "</label></td></tr>\n</table>\n\n<p><b>";
    echo $LANG["custinvoiceformatheading"];
    echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"25%\" class=\"fieldlabel\">";
    echo $LANG["enable"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enablecustominvoicenum\"";
    if( $modulevars["enablecustominvoicenum"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["custinvoiceformatenabledesc"];
    echo "</label></td></tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
    echo $LANG["custinvoiceformatnumbering"];
    echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"row\">\n                <div class=\"col-xs-4\">\n                    <input type=\"text\" name=\"custominvoicenumformat\" value=\"";
    echo $modulevars["custominvoicenumformat"];
    echo "\" class=\"form-control\" />\n                </div>\n            </div>\n            ";
    echo $LANG["custinvoiceformatfields"];
    echo ": {YEAR} {MONTH} {DAY} {NUMBER}\n        </td>\n    </tr>\n    <tr>\n        <td width=\"25%\" class=\"fieldlabel\">";
    echo $LANG["custinvoiceformatnextnumber"];
    echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"row\">\n                <div class=\"col-xs-2\">\n                    <input type=\"text\" name=\"custominvoicenumber\" value=\"";
    echo $modulevars["custominvoicenumber"];
    echo "\" class=\"form-control\" />\n                </div>\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
    echo $LANG["custinvoiceformatautoreset"];
    echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"custominvoicenumautoreset\" value=\"\"";
    if( $modulevars["custominvoicenumautoreset"] == "" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetnever"];
    echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"custominvoicenumautoreset\" value=\"monthly\"";
    if( $modulevars["custominvoicenumautoreset"] == "monthly" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetmonthly"];
    echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"custominvoicenumautoreset\" value=\"annually\"";
    if( $modulevars["custominvoicenumautoreset"] == "annually" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetannually"];
    echo "            </label>\n        </td>\n    </tr>\n</table>\n\n<p><b>";
    echo $LANG["seqpaidnumberheading"];
    echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"25%\" class=\"fieldlabel\">";
    echo $LANG["enable"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enblesequentialpaidinvoice\"";
    if( $CONFIG["SequentialInvoiceNumbering"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["seqpaidnumberenabledesc"];
    echo "</label></td></tr>\n    <tr>\n        <td width=\"25%\" class=\"fieldlabel\">\n            ";
    echo $LANG["seqpaidnumberformat"];
    echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"row\">\n                <div class=\"col-xs-4\">\n                    <input type=\"text\" name=\"sequentialpaidformat\" value=\"";
    echo $CONFIG["SequentialInvoiceNumberFormat"];
    echo "\" class=\"form-control\" />\n                </div>\n            </div>\n            ";
    echo $LANG["custinvoiceformatfields"];
    echo ": {YEAR} {MONTH} {DAY} {NUMBER}\n        </td>\n    </tr>\n    <tr>\n        <td width=\"25%\" class=\"fieldlabel\">";
    echo $LANG["seqpaidnumbernextnumber"];
    echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"row\">\n                <div class=\"col-xs-2\">\n                    <input type=\"text\" name=\"sequentialpaidnumber\" value=\"";
    echo $CONFIG["SequentialInvoiceNumberValue"];
    echo "\" class=\"form-control\" />\n                </div>\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
    echo $LANG["custinvoiceformatautoreset"];
    echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"sequentialpaidautoreset\" value=\"\"";
    if( $modulevars["sequentialpaidautoreset"] == "" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetnever"];
    echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"sequentialpaidautoreset\" value=\"monthly\"";
    if( $modulevars["sequentialpaidautoreset"] == "monthly" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetmonthly"];
    echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"sequentialpaidautoreset\" value=\"annually\"";
    if( $modulevars["sequentialpaidautoreset"] == "annually" ) 
    {
        echo " checked";
    }

    echo " />\n                ";
    echo $LANG["custinvoiceformatautoresetannually"];
    echo "            </label>\n        </td>\n    </tr>\n<tr><td class=\"fieldlabel\">";
    echo $LANG["seqpaidnumberinvoicedate"];
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enableinvoicedatepayment\"";
    if( $modulevars["enableinvoicedatepayment"] ) 
    {
        echo " checked";
    }

    echo " /> ";
    echo $LANG["seqpaidnumberinvoicedatedesc"];
    echo "</td></label></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" id=\"saveChanges\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n<h2>";
    echo $LANG["autovatrulessetupheading"];
    echo "</h2>\n\n<p>";
    echo $LANG["autovatrulessetupdesc"];
    echo "</p>\n\n<form method=\"post\" action=\"";
    echo $vars["modulelink"];
    echo "\" class=\"form-inline\">\n    <input type=\"hidden\" name=\"action\" value=\"setupvat\" />\n    <div class=\"text-center\">\n        <div class=\"form-group\">\n            <label for=\"inputVatLabel\">";
    echo $LANG["vatlabel"];
    echo "</label>\n            <input type=\"text\" name=\"vatlabel\" id=\"inputVatLabel\" value=\"VAT\" class=\"form-control\" />\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputVatRate\">";
    echo $LANG["vatrate"];
    echo "</label>\n            <div class=\"input-group\">\n                <input type=\"text\" name=\"vatrate\" id=\"inputVatRate\" value=\"20\" class=\"form-control\" style=\"width:75px;\" />\n                <span class=\"input-group-addon\">%</span>\n            </div>\n        </div>\n        <button type=\"submit\" class=\"btn btn-success\">";
    echo $aInt->lang("global", "submit");
    echo "</button>\n    </div>\n</form>\n\n<br /><br />\n\n";
}


