<?php 
namespace WHMCS\Route\Contracts;


interface ProviderInterface
{
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector);

}


