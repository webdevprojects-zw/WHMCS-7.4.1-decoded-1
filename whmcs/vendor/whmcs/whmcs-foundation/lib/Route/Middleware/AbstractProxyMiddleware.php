<?php 
namespace WHMCS\Route\Middleware;


abstract class AbstractProxyMiddleware implements \WHMCS\Route\Contracts\Middleware\ProxyInterface, \WHMCS\Route\Contracts\MapInterface
{
    use Strategy\AssumingMiddlewareTrait;
    use \WHMCS\Route\HandleMapTrait;

    abstract public function factoryProxyDriver($handle, \WHMCS\Http\Message\ServerRequest $request);

    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $handle = $request->getAttribute("matchedRouteHandle");
        if( !$handle ) 
        {
            return $delegate->process($request);
        }

        $mappedHandle = $this->getMappedRoute($handle);
        if( is_null($mappedHandle) ) 
        {
            return $delegate->process($request);
        }

        $driver = $this->factoryProxyDriver($mappedHandle, $request);
        if( !$driver instanceof \Interop\Http\ServerMiddleware\MiddlewareInterface ) 
        {
            throw new \RuntimeException("Invalid \"%s\" route attribute defined for %s", $this->getMappedAttributeName(), $request->getUri()->getPath());
        }

        return $driver->process($request, $delegate);
    }

}


