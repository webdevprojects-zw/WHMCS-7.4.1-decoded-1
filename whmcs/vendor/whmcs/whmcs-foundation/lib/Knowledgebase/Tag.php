<?php 
namespace WHMCS\Knowledgebase;


class Tag extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblknowledgebasetags";
    public $timestamps = false;

    public function scopeTag($query, $tag)
    {
        return $query->where("tag", "like", $tag);
    }

    public static function getTagTotals()
    {
        return static::select("tag", \WHMCS\Database\Capsule::raw("count(*) as total"))->groupBy("tag")->lists("total", "tag")->all();
    }

    public function articles()
    {
        return $this->hasOne("\\WHMCS\\Knowledgebase\\Article", "articleid");
    }

}


