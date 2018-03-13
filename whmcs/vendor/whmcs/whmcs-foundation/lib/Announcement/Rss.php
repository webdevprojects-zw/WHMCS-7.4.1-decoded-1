<?php 
namespace WHMCS\Announcement;


class Rss
{
    public function toXml(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $xml = sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n            <rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n                <channel>\n                    <atom:link href=\"%s\" rel=\"self\" type=\"application/rss+xml\" />\n                    <title><![CDATA[%s]]></title>\n                    <description><![CDATA[%s %s %s]]></description>\n                    <link>%s</link>\n                    %s\n                </channel>\n            </rss>", \WHMCS\Config\Setting::getValue("Charset"), fqdnRoutePath("announcement-rss"), \WHMCS\Config\Setting::getValue("CompanyName"), \WHMCS\Config\Setting::getValue("CompanyName"), \Lang::trans("announcementstitle"), \Lang::trans("rssfeed"), fqdnRoutePath("announcement-index"), implode("\n", $this->getXmlItems()));
        $response = new \Zend\Diactoros\Response\TextResponse($xml, 200);
        $response = $response->withHeader("Content-Type", "application/rss+xml");
        return $response;
    }

    protected function getXmlItems()
    {
        $items = array(  );
        $language = "";
        if( isset($_REQUEST["language"]) && in_array($_REQUEST["language"], \WHMCS\Language\ClientLanguage::getLanguages()) ) 
        {
            $language = $_REQUEST["language"];
        }

        $result = select_query("tblannouncements", "*", array( "published" => "1" ), "date", "DESC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $id = $data["id"];
            $date = $data["date"];
            $title = $data["title"];
            $announcement = $data["announcement"];
            $result2 = select_query("tblannouncements", "", array( "parentid" => $id, "language" => $language ));
            $data = mysql_fetch_array($result2);
            if( $data["title"] ) 
            {
                $title = $data["title"];
            }

            if( $data["announcement"] ) 
            {
                $announcement = $data["announcement"];
            }

            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            $day = substr($date, 8, 2);
            $hours = substr($date, 11, 2);
            $minutes = substr($date, 14, 2);
            $seconds = substr($date, 17, 2);
            $items[] = sprintf("\n<item>\n    <title><![CDATA[%s]]></title>\n    <link>%s</link>\n    <guid>%s</guid>\n    <pubDate>%s</pubDate>\n    <description><![CDATA[%s]]></description>\n</item>", $title, fqdnRoutePath("announcement-view", $id), fqdnRoutePath("announcement-view", $id), date("r", mktime($hours, $minutes, $seconds, $month, $day, $year)), $announcement);
        }
        return $items;
    }

}


