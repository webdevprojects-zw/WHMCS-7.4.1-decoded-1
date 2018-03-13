<?php 
namespace WHMCS;


class TransientData
{
    const DB_TABLE = "tbltransientdata";

    public function __construct()
    {
        return $this;
    }

    public static function getInstance()
    {
        return new self();
    }

    public function store($name, $data, $life = 300)
    {
        if( !is_string($data) ) 
        {
            return false;
        }

        $expires = time() + (int) $life;
        if( $this->ifNameExists($name) ) 
        {
            $this->sqlUpdate($name, $data, $expires);
        }
        else
        {
            $this->sqlInsert($name, $data, $expires);
        }

        return true;
    }

    public function retrieve($name)
    {
        return $this->sqlSelect($name, true);
    }

    public function retrieveByData($data)
    {
        return $this->sqlSelectByData($data, true);
    }

    public function ifNameExists($name)
    {
        $data = $this->sqlSelect($name);
        return ($data === null ? false : true);
    }

    public function delete($name)
    {
        $this->sqlDelete($name);
        return true;
    }

    public function purgeExpired($delaySeconds = 120)
    {
        $now = time() - (int) $delaySeconds;
        delete_query("tbltransientdata", "expires<" . db_escape_string($now));
        return true;
    }

    protected function sqlSelect($name, $exclude_expired = false)
    {
        $where = array( "name" => $name );
        if( $exclude_expired ) 
        {
            $where["expires"] = array( "sqltype" => ">", "value" => time() );
        }

        $data = get_query_val(self::DB_TABLE, "data", $where);
        return $data;
    }

    protected function sqlSelectByData($data, $exclude_expired = false)
    {
        if( $exclude_expired ) 
        {
            $name = \Illuminate\Database\Capsule\Manager::table("tbltransientdata")->where("data", "=", $data)->value("name");
        }
        else
        {
            $name = \Illuminate\Database\Capsule\Manager::table("tbltransientdata")->where("data", "=", $data)->where("expires", ">", \Carbon\Carbon::now()->toDateString())->value("name");
        }

        return $name;
    }

    protected function sqlInsert($name, $data, $expires)
    {
        $arrdata = array( "name" => $name, "data" => $data, "expires" => $expires );
        return insert_query(self::DB_TABLE, $arrdata);
    }

    protected function sqlUpdate($name, $data, $expires)
    {
        $updatearr = array( "data" => $data, "expires" => $expires );
        $where = array( "name" => $name );
        update_query(self::DB_TABLE, $updatearr, $where);
        return true;
    }

    public function sqlDelete($name)
    {
        $where = array( "name" => $name );
        delete_query(self::DB_TABLE, $where);
        return true;
    }

}


