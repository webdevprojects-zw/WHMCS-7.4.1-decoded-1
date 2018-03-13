<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator\Validation;


class InvalidCertificate extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;

    const PATTERN_FAILED_CERT_LOAD = "/Invalid certificate content/";

    public function getTitle()
    {
        return "Certification Error - Invalid or Corrupt Certificate";
    }

    public function getHelpUrl()
    {
        return "http://docs.whmcs.com/Automatic_Updater#Certification_Error";
    }

    protected function isKnown($data)
    {
        return preg_match(self::PATTERN_FAILED_CERT_LOAD, $data);
    }

}


