<?php 
namespace WHMCS\Domain\TopLevel;


class Category extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltld_categories";
    public $unique = array( "category" );
    protected $booleans = array( "isPrimary" );

    public function topLevelDomains()
    {
        return $this->belongsToMany("WHMCS\\Domain\\TopLevel", "tbltld_category_pivot", "category_id", "tld_id")->withTimestamps();
    }

    public function scopeTldsIn($query, array $tlds = array(  ))
    {
        return $query->whereHas("topLevelDomains", function($subQuery) use ($tlds)
{
    $subQuery->whereIn("tld", $tlds);
}

);
    }

}


