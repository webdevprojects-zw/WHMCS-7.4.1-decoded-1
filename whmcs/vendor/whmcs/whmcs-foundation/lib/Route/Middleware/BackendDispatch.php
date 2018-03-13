<?php 
namespace WHMCS\Route\Middleware;


class BackendDispatch implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\AssumingMiddlewareTrait;

    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return $this->getDispatch($request)->dispatch($request);
    }

    public function getDispatch(\WHMCS\Http\Message\ServerRequest $request)
    {
        if( $request->isAdminRequest() ) 
        {
            return \DI::make("Backend\\Dispatcher\\Admin");
        }

        if( $request->isApiRequest() ) 
        {
            return \DI::make("Backend\\Dispatcher\\Api");
        }

        return \DI::make("Backend\\Dispatcher\\Client");
    }

}


