<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$GATEWAYMODULE["fasthostsname"] = "fasthosts";
$GATEWAYMODULE["fasthostsvisiblename"] = "FastHosts";
$GATEWAYMODULE["fasthoststype"] = "CC";
function fasthosts_activate()
{
    defineGatewayField("fasthosts", "text", "merchantid", "", "Merchant ID", "20", "");
    defineGatewayField("fasthosts", "text", "paymentkey", "", "Payment Key", "20", "");
}

function fasthosts_capture($params)
{
    if( $params["cardtype"] == "Visa" ) 
    {
        $cardtype = "VI";
    }
    else
    {
        if( $params["cardtype"] == "MasterCard" ) 
        {
            $cardtype = "MC";
        }
        else
        {
            if( $params["cardtype"] == "American Express" ) 
            {
                $cardtype = "AM";
            }
            else
            {
                if( $params["cardtype"] == "Diners Club" ) 
                {
                    $cardtype = "DC";
                }
                else
                {
                    if( $params["cardtype"] == "Discover" ) 
                    {
                        $cardtype = "DI";
                    }
                    else
                    {
                        if( $params["cardtype"] == "JCB" ) 
                        {
                            $cardtype = "JC";
                        }
                        else
                        {
                            if( $params["cardtype"] == "Delta" ) 
                            {
                                $cardtype = "VD";
                            }
                            else
                            {
                                if( $params["cardtype"] == "Solo" ) 
                                {
                                    $cardtype = "MD";
                                }
                                else
                                {
                                    if( $params["cardtype"] == "Maestro" ) 
                                    {
                                        $cardtype = "MD";
                                    }
                                    else
                                    {
                                        if( $params["cardtype"] == "Switch" ) 
                                        {
                                            $cardtype = "MD";
                                        }
                                        else
                                        {
                                            if( $params["cardtype"] == "Electron" ) 
                                            {
                                                $cardtype = "VE";
                                            }

                                        }

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

    }

    $fasthosts[cardType] = $cardtype;
    $fasthosts[cardNumber] = $params["cardnum"];
    $fasthosts[cardExp] = substr($params["cardexp"], 0, 2) . "/" . substr($params["cardexp"], 2, 2);
    $fasthosts[issueNumber] = $params["cardissuenum"];
    $fasthosts[amount] = $params["amount"] * 100;
    $fasthosts[operation] = "P";
    $fasthosts[merchantTxn] = $params["invoiceid"];
    if( $params["cccvv"] ) 
    {
        $fasthosts[cvdIndicator] = "1";
        $fasthosts[cvdValue] = $params["cccvv"];
    }

    $fasthosts[custName1] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $fasthosts[streetAddr] = $params["clientdetails"]["address1"];
    $fasthosts[streetAddr2] = $params["clientdetails"]["address2"];
    $fasthosts[city] = $params["clientdetails"]["city"];
    $fasthosts[province] = $params["clientdetails"]["state"];
    $fasthosts[zip] = $params["clientdetails"]["postcode"];
    $fasthosts[country] = $params["clientdetails"]["country"];
    $fasthosts[phone] = $params["clientdetails"]["phonenumber"];
    $fasthosts[email] = $params["clientdetails"]["email"];
    $fasthosts["merchantID"] = $params["merchantid"];
    $fasthosts["paymentKey"] = $params["paymentkey"];
    $fasthosts["clientVersion"] = "1.1";
    $fasthosts["operation"] = "P";
    $data_stream = "";
    $url = "https://www.e-merchant.co.uk/api/";
    foreach( $fasthosts as $k => $v ) 
    {
        if( 0 < strlen($data_stream) ) 
        {
            $data_stream .= "&";
        }

        $data_stream .= (string) $k . "=" . urlencode($v);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stream);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result_tmp = curl_exec($ch);
    curl_close($ch);
    $result_tmp = explode("&", urldecode($result_tmp));
    foreach( $result_tmp as $v ) 
    {
        list($key, $val) = explode("=", $v);
        $result[$key] = $val;
    }
    $desc = "Action => Auth_Capture\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\n";
    foreach( $result as $key => $value ) 
    {
        $desc .= (string) $key . " => " . $value . "\n";
    }
    if( $result["status"] == "SP" ) 
    {
        return array( "status" => "success", "transid" => $result["txnNumber"], "rawdata" => $desc );
    }

    if( $result["status"] == "E" ) 
    {
        return array( "status" => "declined", "rawdata" => $desc );
    }

    return array( "status" => "error", "rawdata" => $desc );
}


