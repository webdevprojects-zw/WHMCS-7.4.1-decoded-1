<?php 
namespace WHMCS\Language;


class AdminLanguage extends AbstractLanguage
{
    protected $globalVariable = "_ADMINLANG";

    public static function getDirectory()
    {
        $adminDirectory = \App::get_admin_folder_name();
        return ROOTDIR . DIRECTORY_SEPARATOR . $adminDirectory . DIRECTORY_SEPARATOR . "lang";
    }

    public static function factory($languageName = self::FALLBACK_LANGUAGE)
    {
        $validLanguageName = self::getValidLanguageName($languageName);
        return static::findOrCreate($validLanguageName);
    }

}


