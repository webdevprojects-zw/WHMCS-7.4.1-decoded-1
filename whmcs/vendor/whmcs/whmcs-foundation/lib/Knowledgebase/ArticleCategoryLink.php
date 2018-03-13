<?php 
namespace WHMCS\Knowledgebase;


class ArticleCategoryLink extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblknowledgebaselinks";
    public $timestamps = false;

    public function article()
    {
        return $this->belongsTo("\\WHMCS\\Knowledgebase\\Article", "articleid");
    }

    public function category()
    {
        return $this->belongsTo("\\WHMCS\\Knowledgebase\\Category", "categoryid");
    }

}


