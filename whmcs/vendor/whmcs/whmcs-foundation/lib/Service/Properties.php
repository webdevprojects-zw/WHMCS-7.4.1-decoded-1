<?php 
namespace WHMCS\Service;


class Properties
{
    protected $model = NULL;
    protected static $fieldsToCustomFieldName = array( "username" => "Username", "password" => "Password", "domain" => "Domain", "license" => "License Key", "dedicatedip" => "Dedicated IP", "diskusage" => "Disk Usage", "disklimit" => "Disk Limit", "bwusage" => "Bandwidth Usage", "bwlimit" => "Bandwidth Limit", "lastupdate" => "Last Update" );

    public function __construct($model)
    {
        $this->model = $model;
        return $this;
    }

    public function save(array $data)
    {
        $model = $this->model;
        if( $model instanceof Addon ) 
        {
            foreach( $data as $fieldName => $value ) 
            {
                $fieldType = "text";
                if( is_array($value) ) 
                {
                    $fieldType = $value["type"];
                    $value = $value["value"];
                }

                if( array_key_exists(strtolower($fieldName), self::$fieldsToCustomFieldName) ) 
                {
                    $fieldName = strtolower($fieldName);
                    $fieldName = self::$fieldsToCustomFieldName[$fieldName];
                }

                $customField = \WHMCS\CustomField::firstOrCreate(array( "fieldName" => $fieldName, "type" => "addon", "fieldType" => $fieldType, "relid" => $model->addonId ));
                if( $customField->wasRecentlyCreated ) 
                {
                    $customField->adminOnly = "on";
                }

                $customField->save();
                saveSingleCustomField($customField->id, $model->id, $value);
            }
        }
        else
        {
            $dataToUpdate = array(  );
            foreach( $data as $fieldName => $value ) 
            {
                if( array_key_exists(strtolower($fieldName), self::$fieldsToCustomFieldName) ) 
                {
                    $fieldName = strtolower($fieldName);
                    if( $fieldName == "license" ) 
                    {
                        $fieldName = "domain";
                    }

                    $dataToUpdate[$fieldName] = $value;
                }
                else
                {
                    $fieldType = "text";
                    if( is_array($value) ) 
                    {
                        $fieldType = $value["type"];
                        $value = $value["value"];
                    }

                    $customField = \WHMCS\CustomField::firstOrCreate(array( "fieldName" => $fieldName, "type" => "product", "fieldType" => $fieldType, "relid" => $model->packageId ));
                    if( $customField->wasRecentlyCreated ) 
                    {
                        $customField->adminOnly = "on";
                    }

                    $customField->save();
                    saveSingleCustomField($customField->id, $model->id, $value);
                }

            }
            if( $dataToUpdate ) 
            {
                if( array_key_exists("password", $dataToUpdate) ) 
                {
                    $dataToUpdate["password"] = encrypt($dataToUpdate["password"]);
                }

                \WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $model->id)->update($dataToUpdate);
            }

        }

        return true;
    }

    public function get($fieldName)
    {
        $model = $this->model;
        if( $model instanceof Addon ) 
        {
            if( array_key_exists(strtolower($fieldName), self::$fieldsToCustomFieldName) ) 
            {
                $fieldName = strtolower($fieldName);
                $customField = \WHMCS\CustomField::where("fieldname", "=", self::$fieldsToCustomFieldName[$fieldName])->where("type", "=", "addon")->where("relid", "=", $model->addonId)->first();
                if( !$customField ) 
                {
                    return false;
                }

            }
            else
            {
                $customField = \WHMCS\CustomField::where("fieldname", "=", $fieldName)->where("type", "=", "addon")->where("relid", "=", $model->addonId)->first();
                if( !$customField ) 
                {
                    return false;
                }

            }

            $customFieldValue = \WHMCS\CustomField\CustomFieldValue::firstOrNew(array( "fieldid" => $customField->id, "relid" => $model->id ));
            if( !$customFieldValue->id ) 
            {
                $customFieldValue->value = "";
            }

            $customFieldValue->save();
            return $customFieldValue->value;
        }

        if( array_key_exists(strtolower($fieldName), self::$fieldsToCustomFieldName) ) 
        {
            $fieldName = strtolower($fieldName);
            if( $fieldName == "license" ) 
            {
                $fieldName = "domain";
            }

            return get_query_val("tblhosting", $fieldName, array( "id" => $model->id ));
        }

        $customField = \WHMCS\CustomField::where("fieldname", "=", $fieldName)->where("type", "=", "product")->where("relid", "=", $model->packageId)->first();
        if( !$customField ) 
        {
            return false;
        }

        $customFieldValue = \WHMCS\CustomField\CustomFieldValue::firstOrNew(array( "fieldid" => $customField->id, "relid" => $model->id ));
        if( !$customFieldValue->exists ) 
        {
            $customFieldValue->value = "";
            $customFieldValue->save();
        }

        return $customFieldValue->value;
    }

}


