<?php 
namespace WHMCS\MarketConnect;


class Promotion
{
    public static function initHooks()
    {
        $hooks = array( "ClientAreaHomepage" => "clientAreaHomeOutput", "ClientAreaProductDetailsOutput" => "productDetailsOutput", "ClientAreaSidebars" => "clientAreaSidebars", "ShoppingCartCheckoutOutput" => "cartCheckoutPromotion", "ShoppingCartViewCartOutput" => "cartViewPromotion" );
        foreach( $hooks as $hook => $function ) 
        {
            add_hook($hook, -1, function($var = NULL) use ($function)
{
    $response = array(  );
    foreach( Service::active()->get() as $service ) 
    {
        $response[] = $service->factoryPromoter()->$function(func_get_args());
    }
    return implode($response);
}

);
        }
    }

}


