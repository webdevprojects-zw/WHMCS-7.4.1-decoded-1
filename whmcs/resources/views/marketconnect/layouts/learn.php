<?php 
echo "<button aria-label=\"Close\" class=\"close\" data-dismiss=\"modal\" type=\"button\"><span aria-hidden=\"true\">&times;</span></button>\n\n<div class=\"logo\"><img src=\"../assets/img/marketconnect/";
echo $vendorSystemName;
echo "/logo.png\" style=\"max-height:85px;\"></div>\n<div class=\"title\">\n    <h3>";
echo $serviceTitle;
echo "</h3>\n    <h4>From ";
echo $vendorName;
echo "</h4>\n</div>\n<div class=\"clearfix\"></div>\n\n<div>\n    <ul class=\"nav nav-tabs\" role=\"tablist\">\n        ";
echo $this->section("nav-tabs");
echo "        <li class=\"pull-right\" role=\"presentation\">\n            <a aria-controls=\"activate\" class=\"activate\" data-toggle=\"tab\" href=\"#activate\" role=\"tab\">Activate</a>\n        </li>\n    </ul>\n    <div class=\"tab-content\">\n        ";
echo $this->section("content-tabs");
echo "    </div>\n</div>\n";
if( App::getFromRequest("activate") ) 
{
    echo "<script type=\"text/javascript\">\n\$(document).ready(function (){\n    \$('.activate').click();\n});\n</script>";
}

echo "\n<script type=\"text/javascript\">\n\$(document).ready(function() {\n    jQuery(\".product-status\").bootstrapSwitch({size: 'small', onText: 'Active', onColor: 'success', offText: 'Disabled'});\n    jQuery(\".promo-switch, .setting-switch\").bootstrapSwitch({size: 'mini'});\n\n\n});\n</script>\n";

