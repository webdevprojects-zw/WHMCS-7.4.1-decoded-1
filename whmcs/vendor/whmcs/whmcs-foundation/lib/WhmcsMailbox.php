<?php 
namespace WHMCS;


class WhmcsMailbox extends \PhpImap\Mailbox
{
    protected function convertStringEncoding($string, $fromEncoding, $toEncoding)
    {
        if( strcasecmp($fromEncoding, "iso-8859-8-i") == 0 ) 
        {
            $fromEncoding = "iso-8859-8";
        }

        return parent::convertStringEncoding($string, $fromEncoding, $toEncoding);
    }

}


