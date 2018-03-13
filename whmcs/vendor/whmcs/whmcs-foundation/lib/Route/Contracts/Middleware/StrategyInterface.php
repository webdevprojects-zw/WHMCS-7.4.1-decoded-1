<?php 
namespace WHMCS\Route\Contracts\Middleware;


interface StrategyInterface extends \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

}


