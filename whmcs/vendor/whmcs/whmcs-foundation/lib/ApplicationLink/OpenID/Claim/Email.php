<?php 
namespace WHMCS\ApplicationLink\OpenID\Claim;


class Email extends AbstractClaim
{
    public $email = NULL;
    public $email_verified = NULL;

    public function hydrate()
    {
        $user = $this->getUser();
        $this->email = $user->email;
        $this->email_verified = false;
        return $this;
    }

}


