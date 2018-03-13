<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator;


class NoMatchDecorator extends AbstractMatchDecorator
{
    use GenericMatchDecorationTrait;

    public function getTitle()
    {
        return "Error";
    }

    public function getHelpUrl()
    {
        return "http://docs.whmcs.com/Automatic_Updater#Troubleshooting";
    }

    protected function isKnown($data)
    {
        return true;
    }

}


