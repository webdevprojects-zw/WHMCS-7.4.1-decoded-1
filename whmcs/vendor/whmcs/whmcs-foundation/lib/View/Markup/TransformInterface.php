<?php 
namespace WHMCS\View\Markup;


interface TransformInterface
{
    const FORMAT_PLAIN = "plain";
    const FORMAT_BBCODE = "bbcode";
    const FORMAT_MARKDOWN = "markdown";
    const FORMAT_HTML = "html";

    public function transform($text, $markupFormat, $emailFriendly);

}


