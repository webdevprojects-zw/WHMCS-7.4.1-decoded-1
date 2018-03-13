<?php 
namespace WHMCS\View\Markup\Error\Message;


interface DecoratorInterface
{
    public function toHtml();

    public function toPlain();

    public function getTitle();

    public function getHelpUrl();

}


