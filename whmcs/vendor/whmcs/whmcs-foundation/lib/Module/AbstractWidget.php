<?php 
namespace WHMCS\Module;


abstract class AbstractWidget
{
    protected $title = NULL;
    protected $description = NULL;
    protected $columns = 1;
    protected $weight = 100;
    protected $wrapper = true;
    protected $cache = false;
    protected $cacheExpiry = 3600;
    protected $requiredPermission = "";

    public function getId()
    {
        return str_replace("WHMCS\\Module\\Widget\\", "", get_class($this));
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getColumnSize()
    {
        return (int) $this->columns;
    }

    public function getWeight()
    {
        return (int) $this->weight;
    }

    public function showWrapper()
    {
        return (bool) $this->wrapper;
    }

    public function isCachable()
    {
        return (bool) $this->cache;
    }

    public function getCacheExpiry()
    {
        return (int) $this->cacheExpiry;
    }

    public function getRequiredPermission()
    {
        return $this->requiredPermission;
    }

    abstract public function getData();

    abstract public function generateOutput($data);

    protected function fetchData($forceRefresh = false)
    {
        $storage = new \WHMCS\TransientData();
        $storageName = "widget." . $this->getId();
        if( $this->isCachable() && !$forceRefresh ) 
        {
            $data = $storage->retrieve($storageName);
            if( !is_null($data) ) 
            {
                $decoded = json_decode($data, true);
                if( is_array($decoded) && count($decoded) ) 
                {
                    return $decoded;
                }

            }

        }

        $data = $this->getData();
        if( $this->isCachable() ) 
        {
            $storage->store($storageName, json_encode($data), $this->getCacheExpiry());
        }

        return $data;
    }

    public function render($forceRefresh = false)
    {
        $data = $this->fetchData($forceRefresh);
        $response = $this->generateOutput($data);
        if( is_array($response) ) 
        {
            return json_encode($response);
        }

        return $response;
    }

}


