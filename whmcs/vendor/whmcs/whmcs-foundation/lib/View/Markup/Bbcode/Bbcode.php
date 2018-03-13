<?php 
namespace WHMCS\View\Markup\Bbcode;


class Bbcode
{
    public static function transform($text)
    {
        $bbCodeMap = array( "b" => "strong", "i" => "em", "u" => "ul", "div" => "div" );
        $text = preg_replace("/\\[div=(&quot;|\")(.*?)(&quot;|\")\\]/", "<div class=\"\$2\">", $text);
        foreach( $bbCodeMap as $bbCode => $htmlCode ) 
        {
            $text = str_replace("[" . $bbCode . "]", "<" . $htmlCode . ">", $text);
            $text = str_replace("[/" . $bbCode . "]", "</" . $htmlCode . ">", $text);
        }
        return $text;
    }

}


