<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator\FilePermission;


class PostCommandCopy extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;

    const PATTERN_DIRECTORY_UNABLE_TO_COPY = "/Unable to copy (.*) to (.*)/";

    public function getTitle()
    {
        return "Insufficient File Permissions For Deployment";
    }

    public function getHelpUrl()
    {
        return "http://docs.whmcs.com/Automatic_Updater#Permission_Errors";
    }

    protected function isKnown($data)
    {
        return preg_match(self::PATTERN_DIRECTORY_UNABLE_TO_COPY, $data);
    }

}


