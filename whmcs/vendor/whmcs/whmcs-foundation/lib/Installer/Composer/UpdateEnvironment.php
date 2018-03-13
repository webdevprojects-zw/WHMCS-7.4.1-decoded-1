<?php 
namespace WHMCS\Installer\Composer;


class UpdateEnvironment
{
    public static function initEnvironment($updateTempDir)
    {
        $environmentErrors = array(  );
        if( empty($updateTempDir) || !is_dir($updateTempDir) ) 
        {
            $environmentErrors[] = \AdminLang::trans("update.missingUpdateTempDir");
        }
        else
        {
            if( !is_writable($updateTempDir) ) 
            {
                $environmentErrors[] = \AdminLang::trans("update.updateTempDirNotWritable");
            }

        }

        return $environmentErrors;
    }

}


