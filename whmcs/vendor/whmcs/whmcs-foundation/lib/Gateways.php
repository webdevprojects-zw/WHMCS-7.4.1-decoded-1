<?php 
namespace WHMCS;


class Gateways
{
    private $modulename = "";
    private static $gateways = NULL;
    private $displaynames = array(  );

    const CC_EXPIRY_MAX_YEARS = 20;

    public function __construct()
    {
    }

    public function getDisplayNames()
    {
        $result = select_query("tblpaymentgateways", "gateway,value", array( "setting" => "name" ), "order", "ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $this->displaynames[$data["gateway"]] = $data["value"];
        }
        return $this->displaynames;
    }

    public function getDisplayName($gateway)
    {
        if( empty($this->displaynames) ) 
        {
            $this->getDisplayNames();
        }

        return (array_key_exists($gateway, $this->displaynames) ? $this->displaynames[$gateway] : $gateway);
    }

    public static function isNameValid($gateway)
    {
        if( !is_string($gateway) || empty($gateway) ) 
        {
            return false;
        }

        if( !ctype_alnum(str_replace(array( "_", "-" ), "", $gateway)) ) 
        {
            return false;
        }

        return true;
    }

    public static function getActiveGateways()
    {
        if( is_array(self::$gateways) ) 
        {
            return self::$gateways;
        }

        self::$gateways = array(  );
        $result = select_query("tblpaymentgateways", "DISTINCT gateway", "");
        while( $data = mysql_fetch_array($result) ) 
        {
            $gateway = $data[0];
            if( Gateways::isNameValid($gateway) ) 
            {
                self::$gateways[] = $gateway;
            }

        }
        return self::$gateways;
    }

    public function isActiveGateway($gateway)
    {
        $gateways = $this->getActiveGateways();
        return in_array($gateway, $gateways);
    }

    public static function makeSafeName($gateway)
    {
        $validgateways = Gateways::getActiveGateways();
        return (in_array($gateway, $validgateways) ? $gateway : "");
    }

    public function getAvailableGateways($invoiceid = "")
    {
        $validgateways = array(  );
        $result = full_query("SELECT DISTINCT gateway, (SELECT value FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `name`, (SELECT `order` FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `order` FROM `tblpaymentgateways` g1 WHERE setting='visible' AND value='on' ORDER BY `order` ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $validgateways[$data[0]] = $data[1];
        }
        if( $invoiceid ) 
        {
            $invoiceid = (int) $invoiceid;
            $invoicegateway = get_query_val("tblinvoices", "paymentmethod", array( "id" => $invoiceid ));
            $result = select_query("tblinvoiceitems", "", array( "type" => "Hosting", "invoiceid" => $invoiceid ));
            while( $data = mysql_fetch_assoc($result) ) 
            {
                $relid = $data["relid"];
                if( $relid ) 
                {
                    $result2 = full_query("SELECT pg.disabledgateways AS disabled FROM tblhosting h LEFT JOIN tblproducts p on h.packageid = p.id LEFT JOIN tblproductgroups pg on p.gid = pg.id where h.id = " . (int) $relid);
                    $data2 = mysql_fetch_assoc($result2);
                    $gateways = explode(",", $data2["disabled"]);
                    foreach( $gateways as $gateway ) 
                    {
                        if( array_key_exists($gateway, $validgateways) && $gateway != $invoicegateway ) 
                        {
                            unset($validgateways[$gateway]);
                        }

                    }
                }

            }
            if( array_key_exists($invoicegateway, $validgateways) === false ) 
            {
                $validgateways[$invoicegateway] = get_query_val("tblpaymentgateways", "value", array( "setting" => "name", "gateway" => $invoicegateway ));
            }

        }

        return $validgateways;
    }

    public function getFirstAvailableGateway()
    {
        $gateways = $this->getAvailableGateways();
        return key($gateways);
    }

    public function getCCDateMonths()
    {
        $months = array(  );
        for( $i = 1; $i <= 12; $i++ ) 
        {
            $months[] = str_pad($i, 2, "0", STR_PAD_LEFT);
        }
        return $months;
    }

    public function getCCStartDateYears()
    {
        $startyears = array(  );
        for( $i = date("Y") - 12; $i <= date("Y"); $i++ ) 
        {
            $startyears[] = $i;
        }
        return $startyears;
    }

    public function getCCExpiryDateYears()
    {
        $expiryyears = array(  );
        for( $i = date("Y"); $i <= date("Y") + static::CC_EXPIRY_MAX_YEARS; $i++ ) 
        {
            $expiryyears[] = $i;
        }
        return $expiryyears;
    }

}


