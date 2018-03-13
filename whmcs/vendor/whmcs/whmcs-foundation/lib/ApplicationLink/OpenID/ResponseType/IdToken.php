<?php 
namespace WHMCS\ApplicationLink\OpenID\ResponseType;


class IdToken extends \OAuth2\OpenID\ResponseType\IdToken
{
    protected function encodeToken(array $token, $client_id = NULL)
    {
        $key = $this->publicKeyStorage->getKeyDetails($client_id);
        return $this->encryptionUtil->encode($token, $key["privateKey"], $key["algorithm"], $key["identifier"]);
    }

}


