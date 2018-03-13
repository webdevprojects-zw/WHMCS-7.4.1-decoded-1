<?php 
namespace WHMCS\Environment;


class Http
{
    public function siteIsConfiguredForSsl()
    {
        try
        {
            \App::getSystemSSLURLOrFail();
            return true;
        }
        catch( \Exception $e ) 
        {
            return false;
        }
    }

    public function siteHasVerifiedSslCert()
    {
        try
        {
            $url = \App::getSystemSSLURLOrFail();
            $request = new \GuzzleHttp\Client(array( "verify" => true ));
            $request->get($url);
            return true;
        }
        catch( \Exception $e ) 
        {
            return false;
        }
    }

}


