<?php 
namespace WHMCS\ClientArea;


class ClientAreaServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;

    protected function getRoutes()
    {
        return array( "/clientarea" => array( array( "method" => array( "GET", "POST" ), "path" => "/module/{module}", "handle" => array( "\\WHMCS\\Module\\ClientAreaController", "index" ) ) ), "/download" => array( array( "name" => "download-index", "method" => "GET", "path" => "", "handle" => array( "\\WHMCS\\Download\\Controller\\DownloadController", "index" ) ), array( "name" => "download-by-cat", "method" => "GET", "path" => "/category/{catid:\\d+}[/{slug}.html]", "handle" => array( "\\WHMCS\\Download\\Controller\\DownloadController", "viewCategory" ) ), array( "name" => "download-search", "method" => array( "GET", "POST" ), "path" => "/search[/{search:.*}]", "handle" => array( "\\WHMCS\\Download\\Controller\\DownloadController", "search" ) ) ), "/downloads" => array( array( "name" => "download-by-cat-legacy", "method" => "GET", "path" => "/{catid:\\d+}[/{slug}.html]", "handle" => array( "\\WHMCS\\Download\\Controller\\DownloadController", "viewCategory" ) ) ), "/announcements" => array( array( "name" => "announcement-index", "method" => "GET", "path" => "[/view/{view:[^/]+}]", "handle" => array( "\\WHMCS\\Announcement\\Controller\\AnnouncementController", "index" ) ), array( "name" => "announcement-index-paged", "method" => "GET", "path" => "/page/{page:\\d+}[/view/{view:[^/]+}]", "handle" => array( "\\WHMCS\\Announcement\\Controller\\AnnouncementController", "index" ) ), array( "name" => "announcement-twitterfeed", "method" => "POST", "path" => "/twitterfeed", "handle" => array( "\\WHMCS\\Announcement\\Controller\\AnnouncementController", "twitterFeed" ) ), array( "name" => "announcement-view", "method" => "GET", "path" => "/{id:\\d+}[/{slug}.html]", "handle" => array( "\\WHMCS\\Announcement\\Controller\\AnnouncementController", "view" ) ), array( "name" => "announcement-rss", "method" => "GET", "path" => "/rss", "handle" => array( "\\WHMCS\\Announcement\\Rss", "toXml" ) ) ), "" => array( array( "name" => "announcement-rss-legacy", "method" => "GET", "path" => "/announcementsrss.php", "handle" => array( "\\WHMCS\\Announcement\\Rss", "toXml" ) ) ) );
    }

    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }

    public function register()
    {
    }

}


