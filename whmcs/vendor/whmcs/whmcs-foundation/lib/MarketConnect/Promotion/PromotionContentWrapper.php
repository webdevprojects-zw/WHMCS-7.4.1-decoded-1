<?php 
namespace WHMCS\MarketConnect\Promotion;


class PromotionContentWrapper
{
    protected $data = NULL;

    public function __construct($promoData)
    {
        $this->data = $promoData;
    }

    public function canShowPromo()
    {
        return !is_null($this->data);
    }

    public function getTemplate()
    {
        return (isset($this->data["template"]) ? $this->data["template"] : "");
    }

    public function getClass()
    {
        return (isset($this->data["class"]) ? $this->data["class"] : "");
    }

    public function getImagePath()
    {
        $path = "";
        if( !empty($this->data["imagePath"]) ) 
        {
            $path = $this->data["imagePath"];
            if( substr($path, 0, 1) !== "/" || substr($path, 0, 4) !== "http" ) 
            {
                $path = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/" . $path;
            }

        }

        return $path;
    }

    public function getHeadline()
    {
        return (isset($this->data["headline"]) ? $this->data["headline"] : "");
    }

    public function getTagline()
    {
        return (isset($this->data["tagline"]) ? $this->data["tagline"] : "");
    }

    public function getDescription()
    {
        return (isset($this->data["description"]) ? $this->data["description"] : "");
    }

    public function hasHighlights()
    {
        return isset($this->data["highlights"]) && 0 < count($this->data["highlights"]);
    }

    public function getHighlights()
    {
        return (isset($this->data["highlights"]) && is_array($this->data["highlights"]) ? $this->data["highlights"] : array(  ));
    }

    public function hasFeatures()
    {
        return isset($this->data["features"]) && 0 < count($this->data["features"]);
    }

    public function getFeatures()
    {
        return (isset($this->data["features"]) && is_array($this->data["features"]) ? $this->data["features"] : array(  ));
    }

    public function getLearnMoreRoute()
    {
        return (isset($this->data["learnMoreRoute"]) ? $this->data["learnMoreRoute"] : "");
    }

    public function getCta()
    {
        return (isset($this->data["cta"]) ? $this->data["cta"] : "");
    }

}


