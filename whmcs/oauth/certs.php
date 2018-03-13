<?php 
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "init.php");
$response = new Symfony\Component\HttpFoundation\JsonResponse();
$content = "";
$cacheKey = "OIDC-JWK";
$cache = new WHMCS\TransientData();
if( $cachedDiscovery = $cache->retrieve($cacheKey) ) 
{
    $content = $cachedDiscovery;
}
else
{
    $server = DI::make("oauth2_server");
    $content = jsonPrettyPrint($server->getJwks());
    $cache->store($cacheKey, $content, 60);
}

$response->setContent($content);
$response->send();

