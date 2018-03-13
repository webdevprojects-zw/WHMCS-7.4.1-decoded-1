<?php 
namespace WHMCS\Cron;


interface DecoratorItemInterface
{
    public function getIcon();

    public function getName();

    public function getSuccessCountIdentifier();

    public function getFailureCountIdentifier();

    public function getSuccessKeyword();

    public function getFailureKeyword();

    public function getFailureUrl();

    public function isBooleanStatusItem();

}


