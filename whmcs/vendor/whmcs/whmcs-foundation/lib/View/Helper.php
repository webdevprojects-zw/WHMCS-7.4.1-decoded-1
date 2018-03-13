<?php 
namespace WHMCS\View;


class Helper
{
    const ERROR_TITLE = "Critical Error";
    const ERROR_BODY = "Unknown Error";

    public static function applicationError($title = self::ERROR_TITLE, $body = self::ERROR_BODY, $exception = NULL)
    {
        if( is_null($title) ) 
        {
            $title = self::ERROR_TITLE;
        }

        if( is_null($body) ) 
        {
            $body = self::ERROR_BODY;
        }

        $body = nl2br("<h1>{{title}}</h1><p>" . $body . "</p>");
        if( \WHMCS\Utility\ErrorManagement::isDisplayErrorCurrentlyVisible() && ($exception instanceof \Exception || $exception instanceof \Error) ) 
        {
            $body .= "<p class=\"debug\">" . HtmlErrorPage::getHtmlStackTrace($exception) . "</p>";
        }

        $errorPage = new HtmlErrorPage($title, $body);
        return $errorPage->getHtmlErrorPage();
    }

    public static function generateCssFriendlyId($name, $title = "")
    {
        return preg_replace("/[^A-Za-z0-9_-]/", "_", $name . (($title != "" ? "-" . $title : "")));
    }

    public static function generateCssFriendlyClassName($value)
    {
        return preg_replace("/[^a-z0-9_-]/", "-", strtolower(trim(strip_tags($value))));
    }

    public static function buildTagCloud(array $tags = array(  ))
    {
        $tagCloud = "";
        $tagCount = ceil(count($tags) / 4);
        $fontSize = "24";
        $minFontSize = "10";
        $fontSizes = array(  );
        $i = 0;
        $firstTag = true;
        foreach( $tags as $tag => $count ) 
        {
            $tagFontSize = $fontSize;
            if( $count <= 1 ) 
            {
                $tagFontSize = "12";
            }

            if( $tagFontSize < $minFontSize ) 
            {
                $tagFontSize = $minFontSize;
            }

            if( isset($fontSizes[$count]) ) 
            {
                $tagFontSize = $fontSizes[$count];
            }
            else
            {
                $fontSizes[$count] = $tagFontSize;
            }

            $cleanTag = strip_tags($tag);
            $content = htmlspecialchars($cleanTag);
            $tagParam = urlencode(str_replace(" ", "-", $cleanTag));
            $tagCloud .= "<a href=\"" . routePath("knowledgebase-tag-view", $tagParam) . "\" style=\"font-size:" . $tagFontSize . "px;\">" . $content . "</a>" . PHP_EOL;
            $i++;
            if( $i == $tagCount || $firstTag ) 
            {
                $fontSize -= 4;
                $i = 0;
            }

            $firstTag = false;
        }
        return $tagCloud;
    }

    public static function alert($text, $alertType = "info", $additionalClasses = "")
    {
        if( !in_array($alertType, array( "success", "info", "warning", "danger" )) ) 
        {
            $alertType = "info";
        }

        switch( $alertType ) 
        {
            case "success":
                $icon = "<i class=\"fa fa-check-circle fa-3x pull-left\"></i>";
                break;
            case "warning":
                $icon = "<i class=\"fa fa-exclamation-circle fa-3x pull-left\"></i>";
                break;
            case "danger":
                $icon = "<i class=\"fa fa-times-circle fa-3x pull-left\"></i>";
                break;
            default:
                $icon = "<i class=\"fa fa-info-circle fa-3x pull-left\"></i>";
        }
        $alert = "<div class=\"alert alert-" . $alertType . " clearfix";
        if( $additionalClasses ) 
        {
            $alert .= " " . $additionalClasses;
        }

        $alert .= "\" role=\"alert\">" . $icon . $text . "</div>";
        return $alert;
    }

    public static function jsGrowlNotification($type, $titleLangKey, $msgLangKey)
    {
        if( $type == "success" ) 
        {
            $type = "notice";
        }
        else
        {
            if( !in_array($type, array( "error", "notice", "warning" )) ) 
            {
                $type = "";
            }

        }

        return "jQuery.growl" . (($type ? "." . $type : "")) . "({ title: \"" . addslashes(\AdminLang::trans($titleLangKey)) . "\", message: \"" . addslashes(\AdminLang::trans($msgLangKey)) . "\" });";
    }

    public static function getAssetVersionHash()
    {
        return substr(sha1(\App::getWHMCSInstanceID() . \App::getVersion()->getCanonical()), 0, 6);
    }

}


