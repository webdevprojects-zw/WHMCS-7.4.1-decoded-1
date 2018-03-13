<?php 
namespace WHMCS\Route\Middleware;


class RoutableRequestUri implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\AssumingMiddlewareTrait;

    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return $delegate->process($this->updateUriFromServerScriptName($request));
    }

    protected function updateUriFromServerScriptName(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $serverParams = $request->getServerParams();
        if( is_array($serverParams) || isset($serverParams["SCRIPT_NAME"]) ) 
        {
            $serverScriptName = $serverParams["SCRIPT_NAME"];
        }
        else
        {
            $serverScriptName = null;
        }

        $baseUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $serverScriptName);
        if( $path !== $serverScriptName && strpos($path, "detect-route-environment") === false ) 
        {
            $path = preg_replace("#^" . preg_quote($serverScriptName) . "#", "", $path);
        }

        if( $path !== $baseUrl && preg_match("#^" . preg_quote($baseUrl . "/") . "#", $path) ) 
        {
            $path = preg_replace("#^" . preg_quote($baseUrl) . "#", "", $path);
        }

        if( 1 < strlen($path) && substr($path, -1) == "/" ) 
        {
            $path = substr($path, 0, -1);
        }

        $uri = $uri->withPath($path);
        return $request->withUri($uri);
    }

}


