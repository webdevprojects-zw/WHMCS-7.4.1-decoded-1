<?php 
namespace WHMCS\Admin\Wizard\Steps\GettingStarted;


class Start
{
    public function getStepContent()
    {
        return "<div class=\"wizard-transition-step\">\n    <div class=\"icon\"><i class=\"fa fa-lightbulb-o\"></i></div>\n    <div class=\"title\">{lang key=\"wizard.welcome\"}</div>\n    <div class=\"tag\">{lang key=\"wizard.intro\"}</div>\n    <div class=\"greyout\">{lang key=\"wizard.noTime\"}</div>\n</div>";
    }

}


