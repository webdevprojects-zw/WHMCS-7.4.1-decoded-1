<?php 
echo "<h3>Settings</h3>\n\n";
if( $saveSuccess ) 
{
    echo "    <div class=\"alert alert-success\">\n        Settings updated successfully!\n    </div>\n";
}
else
{
    if( $errorMsg ) 
    {
        echo "    <div class=\"alert alert-danger\">\n        ";
        echo $errorMsg;
        echo "    </div>\n";
    }

}

echo "\n<div class=\"form-group\">\n    <label for=\"inputApiKey\">API Integration Key</label>\n    <input type=\"text\" name=\"api_key\" class=\"form-control\" id=\"inputApiKey\" placeholder=\"Enter to change\">\n    <p class=\"help-block\">Navigate to Account > Extras > API Keys to create one.</p>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputConnectedList\">Connected List</label>\n    <input type=\"text\" class=\"form-control\" id=\"inputConnectedList\" value=\"";
echo $connectedListName;
echo "\" disabled=\"disabled\">\n    <p class=\"help-block\">To change the mailing list, you must disconnect and re-connect so an e-commerce integration can be established for the new list.</p>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputUserOptIn\">Require User Opt-In</label>\n    <div class=\"checkbox\">\n        <label>\n            <input type=\"checkbox\" name=\"require_user_optin\" id=\"inputUserOptIn\" value=\"1\"";
echo ($requireUserOptIn ? " checked" : "");
echo ">\n            When enabled, users will be asked to confirm they wish to opt-in to your mailing list during the signup/checkout process\n        </label>\n    </div>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputOptInAgreementMsg\">Opt-In Agreement Message</label>\n    <input type=\"text\" name=\"optin_agreement_msg\" class=\"form-control\" id=\"inputOptInAgreementMsg\" value=\"";
echo $optInAgreementMsg;
echo "\">\n    <p class=\"help-block\">This message will be displayed to users during signup/checkout.</p>\n</div>\n\n<p>\n    <button type=\"submit\" class=\"btn btn-primary\">\n        Save Changes\n    </button>\n</p>\n\n<input type=\"hidden\" name=\"action\" value=\"savesettings\">\n";

