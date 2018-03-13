<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator;


trait GenericMatchDecorationTrait
{
    public function toHtml()
    {
        return $this->toGenericHtml(implode("<br/>", $this->getParsedMessageList()));
    }

    public function toPlain()
    {
        return $this->toGenericPlain(implode("\n", $this->getParsedMessageList()));
    }

}


