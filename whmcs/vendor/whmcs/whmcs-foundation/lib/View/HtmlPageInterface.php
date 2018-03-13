<?php 
namespace WHMCS\View;


interface HtmlPageInterface
{
    public function getFormattedHtmlHeadContent();

    public function getFormattedHeaderContent();

    public function getFormattedBodyContent();

    public function getFormattedFooterContent();

}


