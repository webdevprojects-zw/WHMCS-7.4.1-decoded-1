<?php 
namespace WHMCS\User\Client;


class Contact extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcontacts";
    protected $columnMap = array( "clientId" => "userid", "isSubAccount" => "subaccount", "passwordHash" => "password", "receivesDomainEmails" => "domainemails", "receivesGeneralEmails" => "generalemails", "receivesInvoiceEmails" => "invoiceemails", "receivesProductEmails" => "productemails", "receivesSupportEmails" => "supportEmails", "receivesAffiliateEmails" => "affiliateemails" );
    protected $dates = array( "passwordResetKeyRequestDate" );
    protected $booleans = array( "isSubAccount", "receivesDomainEmails", "receivesGeneralEmails", "receivesInvoiceEmails", "receivesProductEmails", "receivesSupportEmails", "receivesAffiliateEmails" );
    protected $commaSeparated = array( "permissions" );
    protected $appends = array( "fullName", "countryName" );
    public static $allPermissions = array( "profile", "contacts", "products", "manageproducts", "productsso", "domains", "managedomains", "invoices", "quotes", "tickets", "affiliates", "emails", "orders" );

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function remoteAccountLinks()
    {
        return $this->hasMany("WHMCS\\Authentication\\Remote\\AccountLink", "contact_id");
    }

    public function orders()
    {
        return $this->hasMany("WHMCS\\Order\\Order", "id", "orderid");
    }

    public function getFullNameAttribute()
    {
        return (string) $this->firstname . " " . $this->lastname;
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

    public function updateLastLogin(\Carbon\Carbon $time = NULL, $ip = NULL, $host = NULL)
    {
        return $this->client->updateLastLogin($time, $ip, $host);
    }

    public function getLanguageAttribute()
    {
        return $this->client->language;
    }

    public function getTwoFactorAuthModuleAttribute()
    {
        return "";
    }

}


