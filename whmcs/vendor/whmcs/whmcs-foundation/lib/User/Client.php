<?php 
namespace WHMCS\User;


class Client extends AbstractUser implements UserInterface
{
    protected $table = "tblclients";
    protected $columnMap = array( "passwordHash" => "password", "twoFactorAuthModule" => "authmodule", "twoFactorAuthData" => "authdata", "currencyId" => "currency", "defaultPaymentGateway" => "defaultgateway", "overrideLateFee" => "latefeeoveride", "overrideOverdueNotices" => "overideduenotices", "disableAutomaticCreditCardProcessing" => "disableautocc", "billingContactId" => "billingcid", "securityQuestionId" => "securityqid", "securityQuestionAnswer" => "securityqans", "creditCardType" => "cardtype", "creditCardLastFourDigits" => "cardlastfour", "creditCardExpiryDate" => "expdate", "storedBankNameCrypt" => "bankname", "storedBankTypeCrypt" => "banktype", "storedBankCodeCrypt" => "bankcode", "storedBankAccountCrypt" => "bankacct", "paymentGatewayToken" => "gatewayid", "lastLoginDate" => "lastlogin", "lastLoginIp" => "ip", "lastLoginHostname" => "host", "passwordResetKey" => "pwresetkey", "passwordResetKeyRequestDate" => "pwresetexpiry" );
    public $timestamps = true;
    protected $dates = array( "lastLoginDate", "passwordResetKeyRequestDate" );
    protected $booleans = array( "taxExempt", "overrideLateFee", "overrideOverdueNotices", "separateInvoices", "disableAutomaticCreditCardProcessing", "emailOptOut", "overrideAutoClose", "emailVerified" );
    public $unique = array( "email" );
    protected $appends = array( "fullName", "countryName" );
    protected $fillable = array( "lastlogin", "ip", "host", "pwresetkey", "pwresetexpiry" );

    public function domains()
    {
        return $this->hasMany("WHMCS\\Domain\\Domain", "userid");
    }

    public function services()
    {
        return $this->hasMany("WHMCS\\Service\\Service", "userid");
    }

    public function contacts()
    {
        return $this->hasMany("WHMCS\\User\\Client\\Contact", "userid");
    }

    public function quotes()
    {
        return $this->hasMany("WHMCS\\Billing\\Quote", "userid");
    }

    public function affiliate()
    {
        return $this->hasOne("WHMCS\\User\\Client\\Affiliate", "clientid");
    }

    public function securityQuestion()
    {
        return $this->belongsTo("WHMCS\\User\\Client\\SecurityQuestion", "securityqid");
    }

    public function invoices()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice", "userid");
    }

    public function transactions()
    {
        return $this->hasMany("WHMCS\\Billing\\Payment\\Transaction", "userid");
    }

    public function remoteAccountLinks()
    {
        $relation = $this->hasMany("WHMCS\\Authentication\\Remote\\AccountLink", "client_id");
        $relation->getQuery()->whereNull("contact_id");
        return $relation;
    }

    public function orders()
    {
        return $this->hasMany("WHMCS\\Order\\Order", "userid");
    }

    public function currencyrel()
    {
        return $this->hasOne("WHMCS\\Billing\\Currency", "id", "currency");
    }

    public function hasDomain($domainName)
    {
        $domainCount = $this->domains()->where("domain", "=", $domainName)->count();
        if( 0 < $domainCount ) 
        {
            return true;
        }

        $serviceDomainCount = $this->services()->where("domain", "=", $domainName)->count();
        return 0 < $serviceDomainCount;
    }

    protected function generateCreditCardEncryptionKey()
    {
        $config = \Config::self();
        return md5($config["cc_encryption_hash"] . $this->id);
    }

    public function getAlerts(Client\AlertFactory $factory = NULL)
    {
        static $alerts = NULL;
        if( is_null($alerts) ) 
        {
            if( is_null($factory) ) 
            {
                $factory = new Client\AlertFactory($this);
            }

            $alerts = $factory->build();
        }

        return $alerts;
    }

    public function isCreditCardExpiring($withinMonths = 2)
    {
        if( $this->creditCardExpiryDate == "" ) 
        {
            return false;
        }

        $expiryDate = $this->decryptValue($this->creditCardExpiryDate, $this->generateCreditCardEncryptionKey());
        if( !is_numeric($expiryDate) || strlen($expiryDate) != 4 ) 
        {
            return false;
        }

        return \Carbon\Carbon::createFromFormat("dmy", "01" . $expiryDate)->diffInMonths(\Carbon\Carbon::now()->startOfMonth()) <= $withinMonths;
    }

    public function getFullNameAttribute()
    {
        return (string) $this->firstName . " " . $this->lastName;
    }

    public function getCountryNameAttribute()
    {
        static $countries = NULL;
        if( is_null($countries) ) 
        {
            $countries = new \WHMCS\Utility\Country();
        }

        return $countries->getName($this->country);
    }

    public function getSecurityQuestionAnswerAttribute($answer)
    {
        return decrypt($answer);
    }

    public function setSecurityQuestionAnswerAttribute($answer)
    {
        $this->attributes["securityqans"] = encrypt($answer);
    }

    public function generateCreditCardEncryptedField($value)
    {
        return $this->encryptValue($value, $this->generateCreditCardEncryptionKey());
    }

    public function getUsernameAttribute()
    {
        return $this->email;
    }

    public function hasSingleSignOnPermission()
    {
        return (bool) $this->allowSso;
    }

    public function isAllowedToAuthenticate()
    {
        return $this->status != "Closed";
    }

    public function isEmailAddressVerified()
    {
        return (bool) $this->emailVerified;
    }

    public function getEmailVerificationId()
    {
        $transientData = \WHMCS\TransientData::getInstance();
        $transientDataName = $this->id . ":emailVerificationClientKey";
        $verificationId = self::generateEmailVerificationKey();
        $verificationExpiry = 86400;
        $transientData->store($transientDataName, $verificationId, $verificationExpiry);
        return $verificationId;
    }

    public static function generateEmailVerificationKey()
    {
        return sha1(base64_encode(\phpseclib\Crypt\Random::string(64)));
    }

    public function sendEmailAddressVerification()
    {
        $whmcs = \App::self();
        $systemUrl = $whmcs->getSystemURL();
        $templateName = "Client Email Address Verification";
        $verificationId = $this->getEmailVerificationId();
        $verificationLinkPath = (string) $systemUrl . "clientarea.php?verificationId=" . $verificationId;
        $emailVerificationHyperLink = "<a href=\"" . $verificationLinkPath . "\" id=\"hrefVerificationLink\">" . $verificationLinkPath . "</a>";
        sendMessage($templateName, $this->id, array( "client_email_verification_id" => $verificationId, "client_email_verification_link" => $emailVerificationHyperLink ));
        return $this;
    }

    public function updateLastLogin(\Carbon\Carbon $time = NULL, $ip = NULL, $host = NULL)
    {
        if( !$time ) 
        {
            $time = \Carbon\Carbon::now();
        }

        if( !$ip ) 
        {
            $ip = \WHMCS\Utility\Environment\CurrentUser::getIP();
        }

        if( !$host ) 
        {
            $host = \WHMCS\Utility\Environment\CurrentUser::getIPHost();
        }

        $this->update(array( "lastlogin" => (string) $time->format("YmdHis"), "ip" => $ip, "host" => $host, "pwresetkey" => "", "pwresetexpiry" => 0 ));
    }

    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "relid");
    }

    public function hasPermission($permission)
    {
        throw new \RuntimeException("WHMCS\\User\\Client::hasPermission" . " not implemented");
    }

}


