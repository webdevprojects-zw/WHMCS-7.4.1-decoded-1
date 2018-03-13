<?php 
namespace WHMCS\View;


class Asset
{
    protected $webRoot = "";

    public function __construct($webRoot)
    {
        $this->webRoot = $webRoot;
    }

    public function getWebRoot()
    {
        return $this->webRoot;
    }

    public function getJsPath()
    {
        return $this->getWebRoot() . "/assets/js";
    }

    public function getCssPath()
    {
        return $this->getWebRoot() . "/assets/css";
    }

    public function getImgPath()
    {
        return $this->getWebRoot() . "/assets/img";
    }

    public function getFontsPath()
    {
        return $this->getWebRoot() . "/assets/fonts";
    }

    public function getFilesystemImgPath()
    {
        return ROOTDIR . "/assets/img";
    }

    public static function cssInclude($filename)
    {
        return sprintf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />", \DI::make("asset")->getCssPath() . "/" . $filename);
    }

    public static function jsInclude($filename)
    {
        return sprintf("<script type=\"text/javascript\" src=\"%s\"></script>", \DI::make("asset")->getJsPath() . "/" . $filename);
    }

    public static function imgTag($filename, $alt = "", $options = array(  ))
    {
        $attributes = "";
        foreach( $options as $key => $value ) 
        {
            $attributes .= " " . $key . "=\"" . $value . "\"";
        }
        return sprintf("<img src=\"%s\" border=\"0\" alt=\"%s\"%s>", \DI::make("asset")->getImgPath() . "/" . $filename, $alt, $attributes);
    }

    public static function icon($rootClassName)
    {
        $iconClassParts = explode("-", $rootClassName, 2);
        return "<i class=\"" . $iconClassParts[0] . " " . $rootClassName . "\"></i>";
    }

}


