<?php 
namespace WHMCS\Route\Contracts\Middleware;


interface ProxyInterface extends StrategyInterface
{
    public function factoryProxyDriver($handle, \WHMCS\Http\Message\ServerRequest $request);

}


