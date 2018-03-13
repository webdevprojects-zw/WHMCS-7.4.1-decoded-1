<?php 
namespace WHMCS\Admin\ApplicationSupport\View\Traits;


trait AdminUserContextTrait
{
    protected $adminUser = NULL;

    public function getAdminUser()
    {
        if( !$this->adminUser ) 
        {
            $id = \WHMCS\Session::get("adminid");
            if( $id ) 
            {
                $user = \WHMCS\User\Admin::find($id);
            }
            else
            {
                $user = new \WHMCS\User\Admin();
            }

            $this->setAdminUser($user);
        }

        return $this->adminUser;
    }

    public function setAdminUser($user)
    {
        $this->adminUser = $user;
        return $this;
    }

    public function getAdminTemplateVariables()
    {
        $user = $this->getAdminUser();
        $adminUsername = (string) $user->firstName . " " . $user->lastName;
        $data = array( "adminid" => $user->id, "admin_username" => ucfirst($adminUsername), "adminFullName" => $adminUsername, "admin_notes" => $user->notes, "admin_supportDepartmentIds" => $user->supportDepartmentIds, "admin_perms" => $user->getRolePermissions(), "addon_modules" => $user->getModulePermissions(), "adminLanguage" => $user->language, "adminsonline" => implode(", ", $this->getOnlineAdminUsernames()) );
        return $data;
    }

    public function getAdminLanguageVariables()
    {
        \AdminLang::self();
        global $_ADMINLANG;
        return $_ADMINLANG;
    }

    public function getOnlineAdminUsernames()
    {
        $adminUsernames = array(  );
        foreach( \WHMCS\User\AdminLog::with("admin")->online()->get() as $adminOnline ) 
        {
            $adminUsernames[] = $adminOnline->adminusername;
        }
        return $adminUsernames;
    }

}


