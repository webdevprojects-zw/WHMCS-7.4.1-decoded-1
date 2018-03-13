<?php 
namespace WHMCS\ApplicationLink;


class Links extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblapplinks_links";
    protected $primaryKey = "id";
    protected $fillable = array( "applink_id", "scope" );

    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if( $drop ) 
        {
            $schemaBuilder->dropIfExists($this->getTable());
        }

        if( !$schemaBuilder->hasTable($this->getTable()) ) 
        {
            $schemaBuilder->create($this->getTable(), function($table)
{
    $table->increments("id");
    $table->integer("applink_id", false, true)->default(0);
    $table->string("scope", 80)->default("");
    $table->string("display_label", 256)->default("");
    $table->tinyInteger("is_enabled")->default(0);
    $table->tinyInteger("order")->default(0);
    $table->timestamp("created_at")->default("0000-00-00 00:00:00");
    $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
}

);
        }

    }

    public function applicationLink()
    {
        return $this->belongsTo("\\WHMCS\\ApplicationLink\\ApplicationLink", "id", "applink_id");
    }

}


