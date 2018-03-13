<?php 
function mailchimp_config()
{
    return array( "name" => "MailChimp", "description" => "Integrates with the MailChimp email service for newsletters and email marketing automation.", "author" => "WHMCS", "language" => "english", "version" => "1.0", "fields" => array(  ) );
}

function mailchimp_activate()
{
    $sql = "CREATE TABLE `mod_mailchimp_optins` (\n  `id` int(10) NOT NULL AUTO_INCREMENT,\n  `userid` int(10) NOT NULL DEFAULT '0',\n  `ipaddress` varchar(32) NOT NULL DEFAULT '',\n  `hostname` varchar(255) NOT NULL DEFAULT '',\n  `optin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    full_query($sql);
}

function mailchimp_deactivate()
{
    $sql = "DROP TABLE `mod_mailchimp_optins`";
    full_query($sql);
}

function mailchimp_output($vars)
{
    $action = (isset($_REQUEST["action"]) ? $_REQUEST["action"] : "");
    $dispatcher = new WHMCS\Module\Addon\Mailchimp\Dispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    if( is_array($response) ) 
    {
        echo json_encode($response);
        exit();
    }

    echo $response;
}


