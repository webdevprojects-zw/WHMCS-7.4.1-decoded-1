<?php 
namespace WHMCS\Route\Middleware\Strategy;


trait SharingMiddlewareTrait
{
    abstract public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

    abstract public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

}


