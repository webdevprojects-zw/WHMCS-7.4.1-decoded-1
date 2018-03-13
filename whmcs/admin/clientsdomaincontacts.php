<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("Edit Clients Domains");
$aInt->title = $aInt->lang("domains", "modifycontact");
$aInt->sidebar = "clients";
$aInt->icon = "clientsprofile";
$aInt->requiredFiles(array( "clientfunctions", "registrarfunctions" ));
ob_start();
$domains = new WHMCS\Domains();
$country = new WHMCS\Utility\Country();
$domain_data = $domains->getDomainsDatabyID($whmcs->get_req_var("domainid"));
$domainid = $domain_data["id"];
if( !$domainid ) 
{
    $aInt->gracefulExit("Domain ID Not Found");
}

$userid = $domain_data["userid"];
$aInt->valUserID($userid);
$domain = $domain_data["domain"];
$registrar = $domain_data["registrar"];
$registrationperiod = $domain_data["registrationperiod"];
if( $action == "save" ) 
{
    check_token("WHMCS.admin.default");
    $contactdetails = $whmcs->get_req_var("contactdetails");
    $wc = $whmcs->get_req_var("wc");
    $sel = $whmcs->get_req_var("sel");
    foreach( $wc as $wc_key => $wc_val ) 
    {
        if( $wc_val == "contact" ) 
        {
            $selectedContact = $sel[$wc_key];
            $selectedContactType = substr($selectedContact, 0, 1);
            $selectedContactID = substr($selectedContact, 1);
            $tmpcontactdetails = array(  );
            if( $selectedContactType == "u" ) 
            {
                $client = new WHMCS\Client($userid);
                $tmpcontactdetails = $client->getDetails();
            }
            else
            {
                if( $selectedContactType == "c" ) 
                {
                    $client = new WHMCS\Client($userid);
                    $tmpcontactdetails = $client->getDetails($selectedContactID);
                }

            }

            $contactdetails[$wc_key] = $domains->buildWHOISSaveArray($tmpcontactdetails);
        }
        else
        {
            foreach( array( "Registrant", "Admin", "Tech", "Billing" ) as $contactType ) 
            {
                if( array_key_exists("Phone Country Code", $contactdetails[$contactType]) ) 
                {
                    $contactdetails[$contactType]["Phone"] = "+" . $contactdetails[$contactType]["Phone Country Code"] . "." . $contactdetails[$contactType]["Phone"];
                }

            }
        }

    }
    $success = $domains->moduleCall("SaveContactDetails", array( "contactdetails" => foreignChrReplace($contactdetails) ));
    $reDirVars = array(  );
    $reDirVars["domainid"] = $domainid;
    if( $success ) 
    {
        $reDirVars["editSuccess"] = true;
    }
    else
    {
        $reDirVars["editSuccess"] = false;
        WHMCS\Cookie::set("contactEditError", $domains->getLastError());
    }

    redir($reDirVars);
    exit();
}
else
{
    if( $whmcs->get_req_var("editSuccess") == 1 ) 
    {
        infoBox($aInt->lang("domains", "modifySuccess"), $aInt->lang("domains", "changesuccess"), "success");
    }
    else
    {
        if( $whmcs->get_req_var("editError") == 0 ) 
        {
            $editError = WHMCS\Input\Sanitize::makeSafeForOutput(WHMCS\Cookie::get("contactEditError"));
            if( $editError ) 
            {
                infoBox($aInt->lang("domains", "registrarerror"), $editError, "error");
            }

            WHMCS\Cookie::delete("contactEditError");
        }

    }

    $success = $domains->moduleCall("GetContactDetails");
    if( $success ) 
    {
        $contactdetails = $domains->getModuleReturn();
    }
    else
    {
        infoBox($aInt->lang("domains", "registrarerror"), $domains->getLastError());
    }

    echo "<script language=\"javascript\">\nfunction usedefaultwhois(id) {\n    jQuery(\".\"+id.substr(0,id.length-1)+\"customwhois\").attr(\"disabled\", true);\n    jQuery(\".\"+id.substr(0,id.length-1)+\"defaultwhois\").attr(\"disabled\", false);\n    jQuery('#'+id.substr(0,id.length-1)+'1').attr(\"checked\", \"checked\");\n}\nfunction usecustomwhois(id) {\n    jQuery(\".\"+id.substr(0,id.length-1)+\"customwhois\").attr(\"disabled\", false);\n    jQuery(\".\"+id.substr(0,id.length-1)+\"defaultwhois\").attr(\"disabled\", true);\n    jQuery('#'+id.substr(0,id.length-1)+'2').attr(\"checked\", \"checked\");\n}\n</script>\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?domainid=";
    echo $domainid;
    echo "&action=save\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "registrar");
    echo "</td>\n        <td class=\"fieldarea\">";
    echo ucfirst($registrar);
    echo "</td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "domain");
    echo "</td>\n        <td class=\"fieldarea\">";
    echo $domain;
    echo "</td>\n    </tr>\n</table>\n\n";
    echo $infobox;
    if( $success ) 
    {
        $contactsarray = array(  );
        $result = select_query("tblcontacts", "id,firstname,lastname", array( "userid" => $userid, "address1" => array( "sqltype" => "NEQ", "value" => "" ) ), "firstname` ASC,`lastname", "ASC");
        while( $data = mysql_fetch_assoc($result) ) 
        {
            $contactsarray[] = array( "id" => $data["id"], "name" => $data["firstname"] . " " . $data["lastname"] );
        }
        $cols = (count($contactdetails) == 3 ? "4" : "6");
        echo "\n<div class=\"row\">\n    ";
        foreach( $contactdetails as $contactdetail => $values ) 
        {
            echo "        <div class=\"col-sm-6 col-lg-";
            echo $cols;
            echo "\">\n\n            <h2>";
            echo $contactdetail;
            echo "</h2></p>\n\n            <p>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"wc[";
            echo $contactdetail;
            echo "]\" id=\"";
            echo $contactdetail;
            echo "1\" value=\"contact\" onclick=\"usedefaultwhois(id)\" />\n                    ";
            echo $aInt->lang("domains", "domaincontactusexisting");
            echo "                </label>\n            </p>\n\n            <p style=\"padding-left:30px;\">\n                ";
            echo $aInt->lang("domains", "domaincontactchoose");
            echo "                <select name=\"sel[";
            echo $contactdetail;
            echo "]\" id=\"";
            echo $contactdetail;
            echo "3\" class=\"";
            echo $contactdetail;
            echo "defaultwhois form-control select-inline input-300\" onclick=\"usedefaultwhois(id)\">\n                    <option value=\"u";
            echo $userid;
            echo "\">";
            echo $aInt->lang("domains", "domaincontactprimary");
            echo "</option>\n                    ";
            foreach( $contactsarray as $subcontactsarray ) 
            {
                echo "                    <option value=\"c";
                echo $subcontactsarray["id"];
                echo "\">";
                echo $subcontactsarray["name"];
                echo "</option>\n                    ";
            }
            echo "                </select>\n            </p>\n\n            <p>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"wc[";
            echo $contactdetail;
            echo "]\" id=\"";
            echo $contactdetail;
            echo "2\" value=\"custom\" onclick=\"usecustomwhois(id)\" checked />\n                    ";
            echo $aInt->lang("domains", "domaincontactusecustom");
            echo "                </label>\n            </p>\n\n            <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"";
            echo $contactdetail;
            echo "customwhois\">\n                ";
            foreach( $values as $name => $value ) 
            {
                echo "                    <tr>\n                        <td width=\"20%\" class=\"fieldlabel\">";
                echo $name;
                echo "</td>\n                        <td class=\"fieldarea\">\n                            ";
                $textFieldInput = true;
                if( $name == "Country" ) 
                {
                    if( !$value ) 
                    {
                        $value = WHMCS\Config\Setting::getValue("DefaultCountry");
                        $countries = $country->getCountryNameArray();
                        $textFieldInput = false;
                    }
                    else
                    {
                        if( $country->isValidCountryCode($value) ) 
                        {
                            $countries = $country->getCountryNameArray();
                            $textFieldInput = false;
                        }
                        else
                        {
                            if( $country->isValidCountryName($value) ) 
                            {
                                $countries = $country->getCountryNamesOnly();
                                $textFieldInput = false;
                            }
                            else
                            {
                                $textFieldInput = true;
                            }

                        }

                    }

                    if( !$textFieldInput ) 
                    {
                        echo "<select name=\"contactdetails[" . $contactdetail . "][" . $name . "]\" class=\"" . $contactdetail . "customwhois form-control\">";
                        foreach( $countries as $k => $v ) 
                        {
                            echo "<option value=\"" . $k . "\"" . (($k == $value ? " selected" : "")) . ">" . $v . "</option>";
                        }
                        echo "</select>";
                    }

                }

                if( $textFieldInput ) 
                {
                    echo "<input type=\"text\" name=\"contactdetails[" . $contactdetail . "][" . $name . "]\" value=\"" . $value . "\" size=\"30\" class=\"" . $contactdetail . "customwhois form-control input-300\">";
                }

                echo "                        </td>\n                    </tr>\n                ";
            }
            echo "            </table>\n\n        </div>\n    ";
        }
        echo "</div>\n";
    }

    echo "\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"button btn btn-primary\">\n        <a href=\"clientsdomains.php?userid=";
    echo $userid;
    echo "&domainid=";
    echo $domainid;
    echo "\" class=\"button btn btn-default\">";
    echo $aInt->lang("global", "goback");
    echo "</a>\n    </div>\n\n</form>\n\n";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->display();
}


