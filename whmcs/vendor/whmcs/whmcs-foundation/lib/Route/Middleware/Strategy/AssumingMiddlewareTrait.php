<?php 
namespace WHMCS\Route\Middleware\Strategy;


trait AssumingMiddlewareTrait
{
    abstract public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return $this->_process($request, $delegate);
    }

}


