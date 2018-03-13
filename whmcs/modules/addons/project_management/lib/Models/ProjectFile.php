<?php 
namespace WHMCSProjectManagement\Models;


class ProjectFile extends \WHMCS\Model\AbstractModel
{
    protected $table = "mod_project_management_files";

    public function createTable($drop = false)
    {
        $tableName = $this->table;
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if( $drop ) 
        {
            $schemaBuilder->dropIfExists($tableName);
        }

        if( !$schemaBuilder->hasTable($tableName) ) 
        {
            $schemaBuilder->create($tableName, function($table)
{
    $table->increments("id");
    $table->unsignedInteger("project_id");
    $table->unsignedInteger("message_id");
    $table->string("filename", 256)->default("");
    $table->unsignedInteger("admin_id");
    $table->timestamps();
}

);
        }

    }

    public function dropTable()
    {
        $tableName = $this->table;
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        $schemaBuilder->dropIfExists($tableName);
    }

}


