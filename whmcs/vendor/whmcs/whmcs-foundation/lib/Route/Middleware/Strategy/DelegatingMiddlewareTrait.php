<?php 
namespace WHMCS\Route\Middleware\Strategy;


trait DelegatingMiddlewareTrait
{
    abstract public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $result = $this->_process($request, $delegate);
        if( $result instanceof \Psr\Http\Message\ResponseInterface || $result instanceof \WHMCS\Exception\HttpCodeException ) 
        {
            $response = $result;
        }
        else
        {
            $response = $delegate->process($result);
        }

        return $response;
    }

}


