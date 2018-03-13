<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator;


interface MatchDecoratorInterface extends \WHMCS\View\Markup\Error\Message\DecoratorInterface, \WHMCS\View\Markup\Error\ErrorLevelInterface
{
    public function wrap(\Iterator $data);

    public function getData();

    public function hasMatch();

}


