<?php 
namespace WHMCS\User\Client;


class SecurityQuestion extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladminsecurityquestions";

    public function getQuestionAttribute($question)
    {
        return decrypt($question);
    }

    public function setQuestionAttribute($question)
    {
        $this->attributes["question"] = encrypt($question);
    }

    public function clients()
    {
        return $this->hasMany("WHMCS\\User\\Client", "securityqid");
    }

}


