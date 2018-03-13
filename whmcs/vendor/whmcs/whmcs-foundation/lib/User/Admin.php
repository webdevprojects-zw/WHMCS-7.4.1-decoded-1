<?php 
namespace WHMCS\User;


class Admin extends AbstractUser implements UserInterface
{
    protected $table = "tbladmins";
    protected $columnMap = array( "passwordHash" => "password", "twoFactorAuthModule" => "authmodule", "twoFactorAuthData" => "authdata", "supportDepartmentIds" => "supportdepts", "isDisabled" => "disabled", "receivesTicketNotifications" => "ticketnotifications" );
    public $unique = array( "email" );
    protected $appends = array( "fullName", "gravatarHash" );
    protected $commaSeparated = array( "supportDepartmentIds", "receivesTicketNotifications", "hiddenWidgets" );

    const TEMPLATE_THEME_DEFAULT = "blend";

    public function getFullNameAttribute()
    {
        return (string) $this->firstName . " " . $this->lastName;
    }

    public function getGravatarHashAttribute()
    {
        return md5(strtolower(trim($this->email)));
    }

    public function getUsernameAttribute()
    {
        return (isset($this->attributes["username"]) ? $this->attributes["username"] : "");
    }

    public function isAllowedToAuthenticate()
    {
        return !$this->isDisabled;
    }

    public function isAllowedToMasquerade()
    {
        return $this->hasPermission(120);
    }

    public function hasPermission($permission)
    {
        static $rolesPerms = NULL;
        if( !is_numeric($permission) ) 
        {
            $id = Admin\Permission::findId($permission);
        }
        else
        {
            $id = $permission;
        }

        if( $id ) 
        {
            if( !$rolesPerms || empty($rolesPerms[$this->roleId]) ) 
            {
                $rolesPerms[$this->roleId] = \WHMCS\Database\Capsule::table("tbladminperms")->where("roleid", $this->roleId)->pluck("permid");
            }

            return in_array($id, $rolesPerms[$this->roleId]);
        }

        return false;
    }

    public function getRolePermissions()
    {
        $adminPermissions = array(  );
        $adminPermissionsArray = Admin\Permission::all();
        $rolePermissions = \WHMCS\Database\Capsule::table("tbladminperms")->where("roleid", "=", $this->roleId)->get();
        foreach( $rolePermissions as $rolePermission ) 
        {
            if( isset($adminPermissionsArray[$rolePermission->permid]) ) 
            {
                $adminPermissions[] = $adminPermissionsArray[$rolePermission->permid];
            }

        }
        return $adminPermissions;
    }

    public function getModulePermissions()
    {
        $addonModulesPermissions = array(  );
        $setting = \WHMCS\Config\Setting::getValue("AddonModulesPerms");
        if( $setting ) 
        {
            $allModulesPermissions = safe_unserialize($setting);
            if( is_array($allModulesPermissions) && array_key_exists($this->roleId, $allModulesPermissions) ) 
            {
                $addonModulesPermissions = $allModulesPermissions[$this->roleId];
            }

        }

        return $addonModulesPermissions;
    }

    public function authenticationDevices()
    {
        return $this->hasMany("\\WHMCS\\Authentication\\Device", "user_id");
    }

    public function getTemplateThemeNameAttribute()
    {
        $templateThemeName = $this->template;
        if( !$templateThemeName ) 
        {
            $templateThemeName = static::TEMPLATE_THEME_DEFAULT;
        }

        return $templateThemeName;
    }

}


