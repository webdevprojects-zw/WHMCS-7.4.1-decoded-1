<?php 
namespace WHMCS\Module;


class Addon extends AbstractModule
{
    protected $type = "addons";

    public function call($function, $params = array(  ))
    {
        $return = parent::call($function, $params);
        if( isset($return["jsonResponse"]) ) 
        {
            $response = new \WHMCS\Http\JsonResponse();
            $response->setData($return["jsonResponse"]);
            $response->send();
            \WHMCS\Terminus::getInstance()->doExit();
        }

        return $return;
    }

}


