<?php 
namespace WHMCS\Admin\Setup\Payments;


class PaymentsRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\ProviderTrait;

    public function getRoutes()
    {
        $helpRoutes = array( "/admin/setup/payments" => array( "attributes" => array( "authentication" => "admin", "authorization" => function()
{
    return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array( "Configure Tax Setup" ));
}

 ), array( "method" => array( "POST" ), "name" => "admin-setup-payments-taxrules-settings", "path" => "/taxrules/settings", "handle" => array( "\\WHMCS\\Admin\\Setup\\Payments\\TaxRulesController", "saveSettings" ), "authorization" => function(\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz)
{
    return $authz->requireCsrfToken();
}

 ), array( "method" => array( "POST" ), "name" => "admin-setup-payments-taxrules-create", "path" => "/taxrules/create", "handle" => array( "\\WHMCS\\Admin\\Setup\\Payments\\TaxRulesController", "create" ), "authorization" => function(\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz)
{
    return $authz->requireCsrfToken();
}

 ), array( "method" => array( "POST" ), "name" => "admin-setup-payments-taxrules-delete", "path" => "/taxrules/delete", "handle" => array( "\\WHMCS\\Admin\\Setup\\Payments\\TaxRulesController", "delete" ), "authorization" => function(\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz)
{
    return $authz->requireCsrfToken();
}

, "authentication" => "adminConfirmation" ), array( "method" => array( "GET", "POST" ), "name" => "admin-setup-payments-taxrules", "path" => "/taxrules", "handle" => array( "\\WHMCS\\Admin\\Setup\\Payments\\TaxRulesController", "index" ), "authentication" => "adminConfirmation" ) ) );
        return $helpRoutes;
    }

    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-payments-";
    }

    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }

}


