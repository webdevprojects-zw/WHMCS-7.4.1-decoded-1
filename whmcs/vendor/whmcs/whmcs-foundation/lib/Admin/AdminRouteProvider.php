<?php 
namespace WHMCS\Admin;


class AdminRouteProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;

    public function getRoutes()
    {
        $adminRoutes = array( "/admin/setup/notifications" => new Setup\Notifications\NotificationsRouteProvider(), "/admin/setup/general/uripathmgmt" => array( array( "method" => array( "GET", "POST" ), "name" => "dev-test", "path" => "/view", "handle" => array( "\\WHMCS\\Admin\\Setup\\General\\UriManagement\\ConfigurationController", "view" ) ) ), "/admin/setup/payments" => new Setup\Payments\PaymentsRouteProvider(), "/admin/setup/authn" => new Setup\Authentication\Client\RemoteAuthRouteProvider(), "/admin/setup/authz" => new Setup\Authorization\AuthorizationRouteProvider(), "/admin/help" => new Help\HelpRouteProvider(), "/admin/search" => array( array( "method" => array( "GET", "POST" ), "name" => "admin-search-client", "path" => "/client", "handle" => array( "\\WHMCS\\Admin\\Search\\Controller\\ClientController", "searchRequest" ), "authentication" => "admin", "authorization" => function()
{
    return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array( "Add/Edit Client Notes", "Add New Order", "Edit Clients Details", "Edit Transaction", "List Invoices", "List Support Tickets", "List Transactions", "Manage Billable Items", "Manage Quotes", "Open New Ticket", "View Activity Log", "View Billable Items", "View Clients Domains", "View Clients Notes", "View Clients Products/Services", "View Clients Summary", "View Email Message Log", "View Orders", "View Reports", "View Support Ticket" ));
}

 ) ), "/admin" => array( array( "method" => array( "POST" ), "name" => "admin-notes-save", "path" => "/profile/notes", "authentication" => "admin", "authorization" => function()
{
    return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
}

, "handle" => array( "WHMCS\\Admin\\Controller\\HomepageController", "saveNotes" ) ), array( "method" => array( "GET", "POST" ), "name" => "admin-widget-refresh", "path" => "/widget/refresh", "authentication" => "admin", "authorization" => function()
{
    return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array( "Main Homepage" ));
}

, "handle" => array( "WHMCS\\Admin\\Controller\\HomepageController", "refreshWidget" ) ), array( "method" => array( "GET", "POST" ), "name" => "admin-widget-display-toggle", "path" => "/widget/display/toggle/{widget:\\w+}", "authentication" => "admin", "authorization" => function()
{
    return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array( "Main Homepage" ));
}

, "handle" => array( "WHMCS\\Admin\\Controller\\HomepageController", "toggleWidgetDisplay" ) ), array( "method" => array( "GET", "POST" ), "name" => "admin-login", "path" => "/login[.php]", "handle" => array( "\\WHMCS\\Admin\\Controller\\LoginController", "viewLoginForm" ) ), array( "method" => array( "GET", "POST" ), "name" => "admin-homepage", "path" => "/[index.php]", "authorization" => function()
{
    return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array( "Main Homepage", "Support Center Overview" ));
}

, "authentication" => "admin", "handle" => array( "\\WHMCS\\Admin\\Controller\\HomepageController", "index" ) ) ) );
        return $this->mutateAdminRoutesForCustomDirectory($adminRoutes);
    }

    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }

}


