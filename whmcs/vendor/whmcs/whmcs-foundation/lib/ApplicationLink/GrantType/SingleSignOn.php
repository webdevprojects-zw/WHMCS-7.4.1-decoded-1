<?php 
namespace WHMCS\ApplicationLink\GrantType;


class SingleSignOn extends \OAuth2\GrantType\ClientCredentials
{
    public function getQuerystringIdentifier()
    {
        return "single_sign_on";
    }

}


