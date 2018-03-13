<?php 
namespace WHMCS\Billing;


class Cycles
{
    protected $cycles = array( "free" => "Free Account", "onetime" => "One Time", "monthly" => "Monthly", "quarterly" => "Quarterly", "semiannually" => "Semi-Annually", "annually" => "Annually", "biennially" => "Biennially", "triennially" => "Triennially" );
    protected $months = array( "1" => "Monthly", "3" => "Quarterly", "6" => "Semi-Annually", "12" => "Annually", "24" => "Biennially", "36" => "Triennially" );

    public function getSystemBillingCycles($excludeRecurring = false)
    {
        $cycles = array(  );
        foreach( $this->cycles as $k => $v ) 
        {
            if( $excludeRecurring && !in_array($v, $this->months) ) 
            {
                continue;
            }

            $cycles[] = $k;
        }
        return $cycles;
    }

    public function getRecurringSystemBillingCycles()
    {
        return $this->getSystemBillingCycles(true);
    }

    public function isValidSystemBillingCycle($cycle)
    {
        return in_array($cycle, $this->getSystemBillingCycles());
    }

    public function isValidPublicBillingCycle($cycle)
    {
        return in_array($cycle, $this->getPublicBillingCycles());
    }

    public function getPublicBillingCycles()
    {
        $cycles = array(  );
        foreach( $this->cycles as $k => $v ) 
        {
            $cycles[] = $v;
        }
        return $cycles;
    }

    public function getBillingCyclesArray()
    {
        return $this->cycles;
    }

    public function getPublicBillingCycle($cycle)
    {
        $cycles = $this->getBillingCyclesArray();
        return (array_key_exists($cycle, $cycles) ? $cycles[$cycle] : "");
    }

    public function getNormalisedBillingCycle($cycle)
    {
        $cycle = strtolower($cycle);
        $cycle = preg_replace("/[^a-z]/i", "", $cycle);
        if( $cycle == "freeaccount" ) 
        {
            $cycle = "free";
        }

        return ($this->isValidSystemBillingCycle($cycle) ? $cycle : "");
    }

    public function getNameByMonths($months)
    {
        return (isset($this->months[$months]) ? $this->months[$months] : "");
    }

    public function getNumberOfMonths($cycle)
    {
        $cycles = array_flip($this->months);
        if( array_key_exists($cycle, $cycles) ) 
        {
            return $cycles[$cycle];
        }

        $normalisedCycle = $this->getNormalisedBillingCycle($cycle);
        $cycle = $this->getPublicBillingCycle($normalisedCycle);
        if( array_key_exists($cycle, $cycles) ) 
        {
            return $cycles[$cycle];
        }

        throw new \WHMCS\Exception("Invalid billing cycle provided");
    }

}


