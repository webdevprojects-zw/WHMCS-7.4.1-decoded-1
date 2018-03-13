<?php 
namespace WHMCS\Announcement\View;


class Index extends \WHMCS\ClientArea
{
    protected function initializeView()
    {
        \Menu::addContext("routeNamespace", "announcement");
        parent::initializeView();
        $this->setPageTitle(\Lang::trans("announcementstitle"));
        $this->setDisplayTitle(\Lang::trans("news"));
        $this->setTagLine(\Lang::trans("allthelatest") . " " . \WHMCS\Config\Setting::getValue("CompanyName"));
        $this->addOutputHookFunction("ClientAreaPageAnnouncements");
        $this->addToBreadCrumb(\WHMCS\Config\Setting::getValue("SystemURL"), \Lang::trans("globalsystemname"))->addToBreadCrumb(routePath("announcement-index"), \Lang::trans("announcementstitle"));
        $this->assign("twitterusername", \WHMCS\Config\Setting::getValue("TwitterUsername"));
        $this->assign("twittertweet", \WHMCS\Config\Setting::getValue("AnnouncementsTweet"));
        $this->assign("facebookrecommend", \WHMCS\Config\Setting::getValue("AnnouncementsFBRecommend"));
        $this->assign("facebookcomments", \WHMCS\Config\Setting::getValue("AnnouncementsFBComments"));
        $this->assign("googleplus1", \WHMCS\Config\Setting::getValue("GooglePlus1"));
        $routeSetting = \WHMCS\Config\Setting::getValue("RouteUriPathMode");
        $seoSetting = ($routeSetting == \WHMCS\Route\UriPath::MODE_REWRITE ? 1 : 0);
        $this->assign("seofriendlyurls", $seoSetting);
        \Menu::addContext("monthsWithAnnouncements", \WHMCS\Announcement\Announcement::getUniqueMonthsWithAnnouncements());
        \Menu::primarySidebar("announcementList");
        \Menu::secondarySidebar("announcementList");
        $this->setTemplate("announcements");
    }

    public function getAnnouncementTemplateData(\WHMCS\Announcement\Announcement $item)
    {
        $translatedItem = $item->bestTranslation();
        return array( "id" => $item->id, "date" => $item->publishDate->format("MM/DD/YYYY"), "timestamp" => $item->publishDate->getTimestamp(), "title" => $translatedItem->title, "urlfriendlytitle" => getModRewriteFriendlyString($translatedItem->title), "summary" => ticketsummary(strip_tags($translatedItem->announcement), 350), "text" => $translatedItem->announcement );
    }

}


