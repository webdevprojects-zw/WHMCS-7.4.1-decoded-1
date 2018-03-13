<?php 
echo "<h3>Other Settings</h3>\n\n<div class=\"promotions\">\n    <div class=\"row\">\n        <div class=\"col-sm-12\">\n            <div class=\"promo\">\n                <h4>Auto Assign to Addons <input type=\"checkbox\" class=\"setting-switch\" data-name=\"auto-assign-addons\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo (is_null($service) || is_null($service->setting("general.auto-assign-addons")) && $isActivationForm || $service->setting("general.auto-assign-addons") ? " checked" : "");
echo "></h4>\n                <p>Automatically assign these products as add-on options to all applicable products</p>\n            </div>\n        </div>\n        <div class=\"col-sm-12\">\n            <div class=\"promo\">\n                <h4>Landing Page Links<input type=\"checkbox\" class=\"setting-switch\" data-name=\"activate-landing-page\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo (is_null($service) || is_null($service->setting("general.activate-landing-page")) && $isActivationForm || $service->setting("general.activate-landing-page") ? " checked" : "");
echo "></h4>\n                <p>Activate navigation link within the client area navigation bar</p>\n            </div>\n        </div>\n    </div>\n</div>\n\n";

