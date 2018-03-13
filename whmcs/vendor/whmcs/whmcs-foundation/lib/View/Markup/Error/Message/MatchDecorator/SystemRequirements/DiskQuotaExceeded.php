<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator\SystemRequirements;


class DiskQuotaExceeded extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;

    const PATTERN_DISK_QUOTA_EXCEEDED = "/Disk quota exceeded/";

    public function getTitle()
    {
        return "Insufficient Disk Space";
    }

    public function getHelpUrl()
    {
        return "http://docs.whmcs.com/Automatic_Updater#System_Requirements";
    }

    protected function isKnown($data)
    {
        return preg_match(static::PATTERN_DISK_QUOTA_EXCEEDED, $data);
    }

}


