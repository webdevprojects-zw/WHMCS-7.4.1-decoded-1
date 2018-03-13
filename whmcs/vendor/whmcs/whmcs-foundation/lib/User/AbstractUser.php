<?php 
namespace WHMCS\User;


abstract class AbstractUser extends \WHMCS\Model\AbstractModel
{
    abstract public function isAllowedToAuthenticate();

    public static function findUuid($uuid)
    {
        if( !$uuid ) 
        {
            return null;
        }

        return static::where("uuid", "=", $uuid)->first();
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function(AbstractUser $model)
{
    if( !$model->uuid ) 
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $model->uuid = $uuid->toString();
    }

}

);
        static::saving(function(AbstractUser $model)
{
    if( !$model->uuid ) 
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $model->uuid = $uuid->toString();
    }

}

);
    }

}


