<?php 
namespace WHMCS\Module\Contracts;


interface NotificationModuleInterface
{
    public function settings();

    public function isActive();

    public function getName();

    public function getDisplayName();

    public function getLogoPath();

    public function testConnection($settings);

    public function notificationSettings();

    public function getDynamicField($fieldName, $settings);

    public function sendNotification(\WHMCS\Notification\Contracts\NotificationInterface $notification, $moduleSettings, $notificationSettings);

}


