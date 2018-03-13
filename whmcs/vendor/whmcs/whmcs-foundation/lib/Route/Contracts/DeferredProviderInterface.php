<?php 
namespace WHMCS\Route\Contracts;


interface DeferredProviderInterface extends ProviderInterface
{
    public function getDeferredRoutePathNameAttribute();

}


