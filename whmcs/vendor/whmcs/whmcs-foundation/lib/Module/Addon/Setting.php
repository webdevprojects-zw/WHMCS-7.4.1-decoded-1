<?php 
namespace WHMCS\Module\Addon;


class Setting extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladdonmodules";
    protected $fillable = array( "module", "setting" );
    public $timestamps = false;

    public function scopeModule($query, $module)
    {
        return $query->where("module", $module);
    }

}


