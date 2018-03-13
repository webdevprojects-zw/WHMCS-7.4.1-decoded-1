<?php 
namespace WHMCS\Billing;


class Invoice extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblinvoices";
    protected $dates = array( "date", "duedate", "datepaid", "lastCaptureAttempt" );
    protected $columnMap = array( "clientId" => "userid", "invoiceNumber" => "invoicenum", "dateCreated" => "date", "dateDue" => "duedate", "tax1" => "tax", "taxRate1" => "taxrate", "paymentGateway" => "paymentmethod", "adminNotes" => "notes" );
    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }

    public function transactions()
    {
        return $this->hasMany("WHMCS\\Billing\\Payment\\Transaction", "invoiceid");
    }

    public function items()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice\\Item", "invoiceid");
    }

    public function data()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice\\Data", "invoiceid");
    }

    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "id", "invoiceid");
    }

    public function scopeUnpaid(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Unpaid");
    }

    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Unpaid")->where("duedate", "<", \Carbon\Carbon::now()->format("Y-m-d"));
    }

    public function scopePaid(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Paid");
    }

    public function scopeCancelled(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Cancelled");
    }

    public function scopeRefunded(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Refunded");
    }

    public function scopeCollections(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Collections");
    }

    public function scopePaymentPending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Payment Pending");
    }

    public function scopeMassPay(\Illuminate\Database\Eloquent\Builder $query, $isMassPay = true)
    {
        return $query->where(function($query) use ($isMassPay)
{
    $query->whereHas("items", function($query) use ($isMassPay)
{
    $query->where("type", ($isMassPay ? "=" : "!="), "Invoice");
}

);
    if( !$isMassPay ) 
    {
        $query->orHas("items", "=", 0);
    }

}

);
    }

    public function scopeWithLastCaptureAttempt(\Illuminate\Database\Eloquent\Builder $query, \Carbon\Carbon $date)
    {
        return $query->where("last_capture_attempt", ">=", $date->toDateString())->where("last_capture_attempt", "<=", $date->toDateString() . " 23:59:59");
    }

    public function getBalanceAttribute()
    {
        $totalDue = $this->total;
        foreach( $this->transactions()->get() as $transaction ) 
        {
            $totalDue = $totalDue - $transaction->amountIn + $transaction->amountOut;
        }
        return $totalDue;
    }

}


