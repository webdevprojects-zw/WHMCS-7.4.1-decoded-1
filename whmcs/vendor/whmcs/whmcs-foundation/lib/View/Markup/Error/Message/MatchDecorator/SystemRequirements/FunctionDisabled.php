<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator\SystemRequirements;


class FunctionDisabled extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;

    const PATTERN_FUNCTION_DISABLED = "/(.*)\\(\\) has been disabled for security reasons/";

    public function getTitle()
    {
        return "Required Function Disabled";
    }

    public function getHelpUrl()
    {
        return "http://docs.whmcs.com/Automatic_Updater#System_Requirements";
    }

    protected function isKnown($data)
    {
        return preg_match(static::PATTERN_FUNCTION_DISABLED, $data);
    }

}


