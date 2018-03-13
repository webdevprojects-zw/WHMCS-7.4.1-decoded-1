<?php 
namespace WHMCS\Mail;


class Template extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblemailtemplates";
    protected $guarded = array( "id" );
    protected $booleans = array( "custom", "disabled", "plaintext" );
    protected $commaSeparated = array( "attachments", "copyTo", "blindCopyTo" );
    public $unique = array(  );

    public function __toString()
    {
        return $this->name;
    }

    public function scopeMaster($query)
    {
        return $query->where("language", "=", "");
    }

    public static function getActiveLanguages()
    {
        return self::distinct()->select("language")->where("language", "!=", "")->orderBy("type")->pluck("language");
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function(Template $template)
{
    $existingLanguages = Template::where("name", "=", $template->name)->pluck("language")->all();
    if( is_null($existingLanguages) ) 
    {
        return true;
    }

    if( !in_array($template->language, $existingLanguages) ) 
    {
        return true;
    }

    throw new \WHMCS\Exception\Model\UniqueConstraint("Email template not unique.");
}

);
    }

}


