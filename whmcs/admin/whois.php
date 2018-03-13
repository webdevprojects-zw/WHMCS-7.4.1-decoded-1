<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("WHOIS Lookups");
$aInt->title = $aInt->lang("whois", "title");
$aInt->sidebar = "utilities";
$aInt->icon = "domains";
$aInt->requiredFiles(array( "domainfunctions" ));
if( $action == "checkavailability" ) 
{
    check_token("WHMCS.admin.default");
    $whois = new WHMCS\WHOIS();
    $result = $whois->lookup(array( "sld" => $sld, "tld" => $tld ));
    echo $result["result"];
    exit();
}

$code = "";
if( $domain = $whmcs->get_req_var("domain") ) 
{
    check_token("WHMCS.admin.default");
    $domains = new WHMCS\Domains();
    $domainparts = $domains->splitAndCleanDomainInput($domain);
    $isValid = $domains->checkDomainisValid($domainparts);
    if( $isValid ) 
    {
        $whois = new WHMCS\WHOIS();
        if( $whois->canLookup($domainparts["tld"]) ) 
        {
            $result = $whois->lookup($domainparts);
            if( $result["result"] == "available" ) 
            {
                $code .= "<div class=\"alert alert-success text-center\" role=\"alert\" style=\"font-size:18px;\">" . sprintf($aInt->lang("whois", "available"), $domain) . "</div>";
            }
            else
            {
                if( $result["result"] == "unavailable" ) 
                {
                    $code .= "<div class=\"alert alert-danger text-center\" role=\"alert\" style=\"font-size:18px;\">" . sprintf($aInt->lang("whois", "unavailable"), $domain) . "</div>";
                }
                else
                {
                    $code .= "<div class=\"alert alert-danger text-center\" role=\"alert\" style=\"font-size:18px;\">" . $aInt->lang("whois", "error") . "</div>" . "<p align=\"text-center\">" . $result["errordetail"] . "</p>";
                }

            }

        }
        else
        {
            $code .= "<div class=\"alert alert-danger text-center\" role=\"alert\" style=\"font-size:18px;\">" . sprintf($aInt->lang("whois", "invalidtld"), $domainparts["tld"]) . "</div>";
        }

    }
    else
    {
        $code .= "<div class=\"alert alert-danger text-center\" role=\"alert\" style=\"font-size:18px;\">" . $aInt->lang("whois", "invaliddomain") . "</div>";
    }

}

$code .= "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n    <div class=\"row clearfix\">\n        <div class=\"col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1\">\n            <div class=\"input-group input-group-lg\">\n                <input type=\"text\" name=\"domain\" value=\"" . $domain . "\" class=\"form-control\" placeholder=\"domaintolookup.com\" />\n                <div class=\"input-group-btn\">\n                    <input type=\"submit\" value=\"Lookup Domain\" class=\"btn btn-primary\" />\n                </div>\n            </div>\n        </div>\n    </div>\n</form>";
if( $domain && $isValid && $result["result"] == "unavailable" ) 
{
    $code .= "<h2>" . $aInt->lang("whois", "whois") . "</h2>\n<div class=\"well well-lg\">\n    " . $result["whois"] . "\n</div>";
}

$aInt->content = $code;
$aInt->display();

