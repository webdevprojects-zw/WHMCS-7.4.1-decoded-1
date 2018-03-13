<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View Credit Card Details");
$aInt->title = AdminLang::trans("clients.ccdetails");
$aInt->requiredFiles(array( "ccfunctions", "clientfunctions" ));
ob_start();
$ccstoredisabled = WHMCS\Config\Setting::getValue("CCNeverStore");
$userid = $whmcs->get_req_var("userid");
$action = $whmcs->get_req_var("action");
$clientsPaymentGateway = getClientsPaymentMethod($userid);
$remoteStoreGateway = $noCcGateway = false;
$gateway = NULL;
if( $clientsPaymentGateway ) 
{
    $gateway = new WHMCS\Module\Gateway();
    $gateway->load($clientsPaymentGateway);
    if( $gateway->functionExists("storeremote") ) 
    {
        $remoteStoreGateway = true;
    }

    if( $gateway->functionExists("no_cc") ) 
    {
        $noCcGateway = true;
    }

}

if( $noCcGateway ) 
{
    $aInt->title = AdminLang::trans("clients.remoteGatewayTitle");
}

if( $ccstoredisabled && !$remoteStoreGateway ) 
{
    $client = WHMCS\User\Client::find($userid);
    if( $client->paymentGatewayToken ) 
    {
        $output = "<strong>" . AdminLang::trans("fields.gatewayid") . "</strong><br />" . (string) $client->paymentGatewayToken . "<br /><br />";
    }
    else
    {
        $output = "<div class=\"fa-stack\"><i class=\"fa fa-credit-card fa-stack-1x fa-fw\"></i>" . "<i class=\"fa fa-ban fa-stack-2x text-danger fa-fw\"></i></div> " . AdminLang::trans("clients.ccstoredisabled");
    }

    $closeWindow = AdminLang::trans("addons.closewindow");
    echo "<div class=\"row client-summary-panels\">\n    <div class=\"col-lg-3\">\n        <div class=\"clientsummarybox text-center\">\n            <div class=\"title\">\n                " . $output . "\n            </div><br />\n            <div class=\"clearfix\"></div>\n            <button class=\"btn btn-default\" onclick=\"window.close()\">" . $closeWindow . "</button>\n        </div>\n    </div>\n</div>";
}
else
{
    $statusTitle = "";
    $statusMsg = "";
    $statusState = "";
    $validhash = "";
    if( $action == "clear" ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Update/Delete Stored Credit Card");
        updateCCDetails($userid, "", "", "", "", "", "", "", true);
        $statusTitle = AdminLang::trans("global.success");
        $statusState = "success";
    }
    else
    {
        if( $_POST["action"] == "save" ) 
        {
            check_token("WHMCS.admin.default");
            checkPermission("Update/Delete Stored Credit Card");
            $errormessage = updateCCDetails($userid, $cctype, $ccnumber, $cardcvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum);
            if( !$errormessage ) 
            {
                $statusTitle = AdminLang::trans("global.success");
                $statusMsg = AdminLang::trans("clients.ccdetailschanged");
                $statusState = "success";
            }
            else
            {
                $statusTitle = AdminLang::trans("global.erroroccurred");
                $statusMsg = $errormessage;
                $statusState = "danger";
            }

        }

    }

    if( $fullcc && !$noCcGateway ) 
    {
        check_token("WHMCS.admin.default");
        checkPermission("Decrypt Full Credit Card Number");
        $referrer = $_SERVER["HTTP_REFERER"];
        $pos = strpos($referrer, "?");
        if( $pos ) 
        {
            $referrer = substr($referrer, 0, $pos);
        }

        $adminfolder = $whmcs->get_admin_folder_name();
        if( App::getSystemUrl() . (string) $adminfolder . "/clientsccdetails.php" != $referrer ) 
        {
            echo "<p>" . $aInt->lang("global", "invalidaccessattempt") . "</p>";
            exit();
        }

        if( $cchash != $cc_encryption_hash ) 
        {
            $statusTitle = AdminLang::trans("global.error");
            $statusMsg = AdminLang::trans("clients.incorrecthash");
            $statusState = "danger";
        }
        else
        {
            $validhash = "true";
            logActivity("Viewed Decrypted Credit Card Number for User ID " . $userid);
        }

    }

    if( $statusTitle ) 
    {
        echo "<div class=\"alert alert-" . $statusState . " text-center\" role=\"alert\">\n    <strong> " . $statusTitle . "</strong>\n    " . str_replace("<li>", "<br />", $statusMsg) . "\n</div>";
    }

    $data = getCCDetails($userid);
    $cardtype = $data["cardtype"];
    $cardnum = ($validhash ? $data["fullcardnum"] : $data["cardnum"]);
    $cardexp = $data["expdate"];
    $cardissuenum = $data["issuenumber"];
    $cardstart = $data["startdate"];
    $gatewayid = $data["gatewayid"];
    if( $gatewayid ) 
    {
        $outputGatewayId = "";
        $gatewayid = json_decode($gatewayid, true);
        if( !$gatewayid || !is_array($gatewayid) || json_last_error() !== JSON_ERROR_NONE ) 
        {
            $outputGatewayId = $data["gatewayid"];
        }

        if( $gatewayid && is_array($gatewayid) ) 
        {
            foreach( $gatewayid as $key => $value ) 
            {
                if( !is_int($key) && is_string($key) ) 
                {
                    $outputGatewayId .= (string) $key . ": ";
                }

                $outputGatewayId .= (string) $value . "<br>";
            }
            $outputGatewayId = substr($outputGatewayId, 0, -4);
        }

        $gatewayid = $outputGatewayId;
    }

    echo "<table class=\"padded-fields\">";
    if( !$noCcGateway ) 
    {
        echo "<tr><td colspan=\"2\"><b>";
        echo $aInt->lang("clients", "existingccdetails");
        echo "</b></td></tr>\n";
        if( $cardtype ) 
        {
            echo "<tr>\n    <td>\n        ";
            echo $aInt->lang("fields", "cardtype");
            echo ":\n    </td>\n    <td>\n    ";
            switch( strtolower($cardtype) ) 
            {
                case "visa":
                    $logo = "<i class=\"fa fa-cc-visa fa-fw\"></i>";
                    break;
                case "mastercard":
                    $logo = "<i class=\"fa fa-cc-mastercard fa-fw\"></i>";
                    break;
                case "american express":
                    $logo = "<i class=\"fa fa-cc-amex fa-fw\"></i>";
                    break;
                case "discover":
                    $logo = "<i class=\"fa fa-cc-discover fa-fw\"></i>";
                    break;
                case "":
                    $logo = "";
                    break;
                default:
                    $logo = "<i class=\"fa fa-credit-card fa-fw\"></i>";
            }
            echo $logo . " " . $cardtype;
            echo "    </td>\n</tr>\n<tr><td>";
            echo $aInt->lang("fields", "cardnum");
            echo ":</td><td>";
            echo $cardnum;
            if( $gatewayid ) 
            {
                echo " *";
            }

            echo "</td></tr>\n";
            if( $cardexp ) 
            {
                echo "<tr><td>";
                echo $aInt->lang("fields", "expdate");
                echo ":</td><td>";
                echo $cardexp;
                echo "</td></tr>";
            }

            if( $cardissuenum ) 
            {
                echo "<tr><td>";
                echo $aInt->lang("fields", "issueno");
                echo ":</td><td>";
                echo $cardissuenum;
                echo "</td></tr>";
            }

            if( $cardstart ) 
            {
                echo "<tr><td>";
                echo $aInt->lang("fields", "startdate");
                echo ":</td><td>";
                echo $cardstart;
                echo "</td></tr>";
            }

        }
        else
        {
            if( $cardnum ) 
            {
                echo "<tr><td colspan=\"2\">" . $cardnum . "</td></tr>";
            }

        }

    }

    if( $data["fullcardnum"] ) 
    {
        echo "<tr><td><br /></td></tr>\n<tr><td colspan=\"2\"><b>";
        echo $aInt->lang("clients", "fullviewcardno");
        echo "</b></td></tr>\n<tr><td colspan=\"2\">\n";
        echo $aInt->lang("clients", "entercchash");
        echo "<br><br>\n<div align=\"center\">\n    <form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n        ";
        generate_token();
        echo "        <input type=\"hidden\" name=\"userid\" value=\"";
        echo $userid;
        echo "\">\n        <input type=\"hidden\" name=\"fullcc\" value=\"true\">\n        <textarea name=\"cchash\" cols=\"25\" rows=\"3\" class=\"form-control\"></textarea>\n        <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "submit");
        echo "\" class=\"btn btn-primary top-margin-5\" />\n    </form>\n</div>\n";
    }
    else
    {
        if( $gatewayid ) 
        {
            $gatewayIdDescription = "";
            if( !$noCcGateway ) 
            {
                $gatewayIdDescription = "<br><br>" . AdminLang::trans("clients.ccstoredremotely");
            }

            echo "<strong>" . $aInt->lang("fields", "gatewayid") . "</strong><br />\"" . $gatewayid . "\"" . $gatewayIdDescription;
        }

    }

    echo "</td></tr>\n";
    if( !$noCcGateway ) 
    {
        echo "<tr><td colspan=\"2\"><br><b>" . AdminLang::trans("clients.enternewcc") . "</b></td></tr>";
    }

    echo "    <tr>\n        <td colspan=\"2\">\n            <form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "\" id=\"frmCreditCardDetails\">\n                <div class=\"alert alert-danger text-center gateway-errors hidden\"></div>\n                <table class=\"padded-fields\">\n                    ";
    if( !$noCcGateway ) 
    {
        echo "                    <tr id=\"rowCardType\">\n                        <td>\n                            <input type=\"hidden\" name=\"action\" value=\"save\">\n                            <input type=\"hidden\" name=\"userid\" value=\"";
        echo $userid;
        echo "\">\n                            ";
        generate_token();
        echo "                            ";
        echo $aInt->lang("fields", "cardtype");
        echo ":\n                        </td>\n                        <td>\n                            ";
        $acceptedCardTypes = explode(",", WHMCS\Config\Setting::getValue("AcceptedCardTypes"));
        $defaultCreditCard = reset($acceptedCardTypes);
        switch( strtolower($defaultCreditCard) ) 
        {
            case "visa":
                $logo = "<i class=\"fa fa-cc-visa fa-fw\"></i>";
                break;
            case "mastercard":
                $logo = "<i class=\"fa fa-cc-mastercard fa-fw\"></i>";
                break;
            case "american express":
                $logo = "<i class=\"fa fa-cc-amex fa-fw\"></i>";
                break;
            case "discover":
                $logo = "<i class=\"fa fa-cc-discover fa-fw\"></i>";
                break;
            default:
                $logo = "<i class=\"fa fa-credit-card fa-fw\"></i>";
        }
        echo "                            <input type=\"hidden\" name=\"cctype\" id=\"cctype\" value=\"";
        echo $defaultCreditCard;
        echo "\" />\n                            <div class=\"credit-card-type\">\n                                <button id=\"ccTypeButton\" type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n                                    <span id=\"selectedCard\">";
        echo (string) $logo . " <span class='type'>" . $defaultCreditCard . "</span>";
        echo "</span>\n                                    <span class=\"fa fa-caret-down fa-fw\"></span>\n                                    <span class=\"sr-only\">Toggle Dropdown</span>\n                                </button>\n                                <ul class=\"dropdown-menu\" role=\"menu\">\n                                    ";
        foreach( $acceptedCardTypes as $card ) 
        {
            switch( strtolower($card) ) 
            {
                case "visa":
                    $logo = "<i class=\"fa fa-cc-visa fa-fw\"></i>";
                    break;
                case "mastercard":
                    $logo = "<i class=\"fa fa-cc-mastercard fa-fw\"></i>";
                    break;
                case "american express":
                    $logo = "<i class=\"fa fa-cc-amex fa-fw\"></i>";
                    break;
                case "discover":
                    $logo = "<i class=\"fa fa-cc-discover fa-fw\"></i>";
                    break;
                default:
                    $logo = "<i class=\"fa fa-credit-card fa-fw\"></i>";
            }
            echo "<li><a onclick='return false;'>" . $logo . " <span class='type'>" . $card . "</span></a></li>";
        }
        echo "                                </ul>\n                            </div>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td nowrap>";
        echo $aInt->lang("fields", "cardnum");
        echo ":</td>\n                        <td><input id=\"inputCardNumber\" type=\"text\" name=\"ccnumber\" autocomplete=\"off\" class=\"form-control\" data-trigger=\"manual\" data-placement=\"top\" title=\"";
        echo AdminLang::trans("clients.ccInvalid");
        echo "\" /></td>\n                    </tr>\n                    <tr><td>";
        echo $aInt->lang("fields", "expdate");
        echo ":</td><td><input id=\"inputCardMonth\" type=\"text\" name=\"ccexpirymonth\" maxlength=\"2\" class=\"form-control\" style=\"display:inline-block;width:50px;\">/<input id=\"inputCardYear\" type=\"text\" name=\"ccexpiryyear\" maxlength=\"2\" class=\"form-control\" style=\"display:inline-block;width:50px;\" data-trigger=\"manual\" data-placement=\"top\" title=\"";
        echo AdminLang::trans("clients.ccExpiryInvalid");
        echo "\" /> (";
        echo $aInt->lang("fields", "mmyy");
        echo ")</td></tr>\n                    ";
        if( $CONFIG["ShowCCIssueStart"] ) 
        {
            echo "                        <tr><td>";
            echo $aInt->lang("fields", "issueno");
            echo ":</td><td><input type=\"text\" name=\"ccissuenum\" maxlength=\"4\" class=\"form-control\" style=\"width:70px;\"></td></tr>\n                        <tr><td>";
            echo $aInt->lang("fields", "startdate");
            echo ":</td><td><input type=\"text\" name=\"ccstartmonth\" maxlength=\"2\" class=\"form-control\" style=\"display:inline-block;width:50px;\">/<input type=\"text\" name=\"ccstartyear\" maxlength=\"2\" class=\"form-control\" style=\"display:inline-block;width:50px;\"> (";
            echo $aInt->lang("fields", "mmyy");
            echo ")</td></tr>\n                    ";
        }

        echo "                    <tr><td nowrap>";
        echo $aInt->lang("fields", "cardcvv");
        echo ":</td><td><input type=\"text\" name=\"cardcvv\" id=\"cardcvv\" autocomplete=\"off\" class=\"form-control\" style=\"width:70px;\" data-trigger=\"manual\" data-placement=\"top\" title=\"";
        echo AdminLang::trans("clients.cvvInvalid");
        echo "\"></td></tr>\n                    ";
    }

    echo "                </table>\n                <script language=\"JavaScript\">\n                    function confirmClear() {\n                        if (confirm(\"";
    echo $aInt->lang("clients", "ccdeletesure");
    echo "\")) {\n                            window.location='";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "&action=clear";
    echo generate_token("link");
    echo "';\n                        }}\n\n                    jQuery(document).ready(function() {\n                        var cardNumber = jQuery('#inputCardNumber'),\n                            cardCvv = jQuery('#cardcvv'),\n                            cardExpiryMonth = jQuery('#inputCardMonth'),\n                            cardExpiryYear = jQuery('#inputCardYear');\n                        cardNumber.payment('formatCardNumber');\n\n                        cardNumber.focus(function(){\n                            cardNumber.tooltip('hide');\n                        });\n                        cardCvv.focus(function(){\n                            cardCvv.tooltip('hide');\n                        });\n                        cardExpiryMonth.focus(function(){\n                            cardExpiryYear.tooltip('hide');\n                        });\n                        cardExpiryYear.focus(function(){\n                            cardExpiryYear.tooltip('hide');\n                        });\n\n                        cardNumber.blur(function(){\n                            var cardValid = jQuery.payment.validateCardNumber(cardNumber.val());\n                            if (!cardValid) {\n                                cardNumber.tooltip('show');\n                            }\n                        });\n\n                        cardCvv.blur(function(){\n                            var cvvValid = jQuery.payment.validateCardCVC(\n                                cardCvv.val(),\n                                jQuery.payment.cardType(cardNumber.val())\n                            );\n                            if (!cvvValid) {\n                                cardCvv.tooltip('show');\n                            }\n                        });\n\n                        cardExpiryYear.blur(function() {\n                            var expiryValid = jQuery.payment.validateCardExpiry(cardExpiryMonth.val(), cardExpiryYear.val());\n                            if (!expiryValid) {\n                                cardExpiryYear.tooltip('show');\n                            }\n                        });\n\n                        jQuery('#frmCreditCardDetails').submit(function(e) {\n                            cardNumber.tooltip('hide');\n                            cardCvv.tooltip('hide');\n                            cardExpiryYear.tooltip('hide');\n                            return true;\n                        });\n                    });\n                </script>\n                <div class=\"btn-container\">\n                    ";
    if( !$noCcGateway ) 
    {
        echo "<input id=\"btnSaveChanges\" type=\"submit\" value=\"" . AdminLang::trans("global.savechanges") . "\" class=\"btn btn-primary\" />";
    }

    echo "                    <input type=\"button\" value=\"";
    echo $aInt->lang("addons", "closewindow");
    echo "\" class=\"button btn btn-default\" onclick=\"window.close()\" /><br />\n                    <input type=\"button\" value=\"";
    echo $aInt->lang("clients", "cleardetails");
    echo "\" class=\"btn btn-danger btn-sm top-margin-5\" onClick=\"confirmClear();return false;\" />\n                </div>\n            </form>\n        </td>\n    </tr>\n</table>\n\n";
    echo WHMCS\View\Asset::jsInclude("Admin.js");
    echo WHMCS\View\Asset::jsInclude("CreditCardValidation.js");
    echo WHMCS\View\Asset::jsInclude("jquery.payment.js");
    if( $gateway && $gateway->functionExists("credit_card_input") ) 
    {
        echo $gateway->call("credit_card_input");
    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->displayPopUp();

