<?php 
namespace WHMCS\Announcement;


class Announcement extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblannouncements";
    protected $columnMap = array( "publishDate" => "date", "isPublished" => "published" );
    protected $dates = array( "publishDate" );
    protected $booleans = array( "isPublished" );

    public function parent()
    {
        return $this->hasOne("WHMCS\\Announcement\\Announcement", "id", "parentid");
    }

    public function translations()
    {
        return $this->hasMany("WHMCS\\Announcement\\Announcement", "parentid", "id");
    }

    public static function getUniqueMonthsWithAnnouncements($count = 10)
    {
        $months = array(  );
        $announcement = new self();
        $rawDates = \Illuminate\Database\Capsule\Manager::table($announcement->getTable())->where("published", "=", 1)->groupBy(\Illuminate\Database\Capsule\Manager::connection()->raw("date_format(date, \"%b %Y\")"))->orderBy("date", "desc")->limit($count)->get(array( "date" ));
        foreach( $rawDates as $date ) 
        {
            $dateTime = new \Carbon\Carbon($date->date);
            $months[] = $dateTime->startOfMonth();
        }
        return new \Illuminate\Support\Collection($months);
    }

    public function scopeTranslationsOf($query, $id = "", $language = "")
    {
        if( $id ) 
        {
            $query = $query->where("parentid", "=", $id);
        }

        if( $language ) 
        {
            $query = $query->where("language", "=", $language);
        }

        return $query;
    }

    public function bestTranslation($language = "")
    {
        if( !$language ) 
        {
            $language = \WHMCS\Session::get("Language");
        }

        if( !$language ) 
        {
            $language = \WHMCS\Config\Setting::getValue("Language");
        }

        static $cache = array(  );
        if( !isset($cache[$this->id][$language]) ) 
        {
            $translation = $this->scopeTranslationsOf($this->newQuery(), $this->id, $language)->first();
            if( $translation ) 
            {
                $cache[$this->id][$language] = $translation;
            }
            else
            {
                $cache[$this->id][$language] = $this;
            }

        }

        return $cache[$this->id][$language];
    }

    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query)
    {
        $query = $query->where("published", "=", "1");
        return $query;
    }

}


