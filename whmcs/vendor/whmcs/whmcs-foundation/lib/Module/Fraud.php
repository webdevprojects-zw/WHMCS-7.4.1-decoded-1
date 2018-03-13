<?php 
namespace WHMCS\Module;


class Fraud extends AbstractModule
{
    protected $type = "fraud";

    public function getSettings()
    {
        $settings = array(  );
        $result = select_query("tblfraud", "", array( "fraud" => $this->getLoadedModule() ));
        while( $data = mysql_fetch_array($result) ) 
        {
            $setting = $data["setting"];
            $value = $data["value"];
            $settings[$setting] = $value;
        }
        return $settings;
    }

    public function call($function, $params = array(  ))
    {
        if( !is_array($params) ) 
        {
            $params = array(  );
        }

        $params = array_merge($params, $this->getSettings());
        return parent::call($function, $params);
    }

    public function doFraudCheck($orderid, $userid = "", $ip = "")
    {
        $countries = new \WHMCS\Utility\Country();
        $params = array(  );
        $whmcs = \WHMCS\Application::getInstance();
        $params["ip"] = ($ip ? $ip : $whmcs->getRemoteIp());
        $params["forwardedip"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
        $userid = (int) $userid;
        if( !$userid ) 
        {
            $userid = $_SESSION["uid"];
        }

        $clientsdetails = getClientsDetails($userid);
        $countrycode = $clientsdetails["country"];
        $params["clientsdetails"] = $clientsdetails;
        $params["clientsdetails"]["countrycode"] = $clientsdetails["phonecc"];
        $params["clientsdetails"]["phonenumber"] = $clientsdetails["phonenumber"];
        $results = $this->call("doFraudCheck", $params);
        $fraudoutput = "";
        if( $results ) 
        {
            foreach( $results as $key => $value ) 
            {
                if( $key != "userinput" && $key != "title" && $key != "description" && $key != "error" ) 
                {
                    $fraudoutput .= (string) $key . " => " . $value . "\n";
                }

            }
        }

        update_query("tblorders", array( "fraudmodule" => $this->getLoadedModule(), "fraudoutput" => $fraudoutput ), array( "id" => (int) $orderid ));
        $results["fraudoutput"] = $fraudoutput;
        return $results;
    }

    public function processResultsForDisplay($orderid, $fraudoutput = "")
    {
        if( $orderid && !$fraudoutput ) 
        {
            $data = get_query_vals("tblorders", "fraudoutput", array( "id" => $orderid, "fraudmodule" => $this->getLoadedModule() ));
            $fraudoutput = $data["fraudoutput"];
        }

        $results = $this->call("processResultsForDisplay", array( "data" => $fraudoutput ));
        return \WHMCS\Input\Sanitize::makeSafeForOutput($results);
    }

}


