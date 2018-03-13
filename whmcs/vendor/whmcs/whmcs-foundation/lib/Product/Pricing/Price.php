<?php 
namespace WHMCS\Product\Pricing;


class Price
{
    protected $price = NULL;

    public function __construct($price)
    {
        $this->price = $price;
        if( !isset($price["breakdown"]) ) 
        {
            $this->price["breakdown"] = array(  );
            if( $this->isYearly() ) 
            {
                $yearlyPrice = $price["price"]->toNumeric() / (int) $this->cycleInYears();
                $this->price["breakdown"]["yearly"] = new \WHMCS\View\Formatter\Price($yearlyPrice, $this->price()->getCurrency());
            }
            else
            {
                $cycleMonths = $this->cycleInMonths();
                if( $cycleMonths < 1 ) 
                {
                    $cycleMonths = 1;
                }

                $yearlyPrice = $price["price"]->toNumeric() / (int) $cycleMonths;
                $this->price["breakdown"]["monthly"] = new \WHMCS\View\Formatter\Price($yearlyPrice, $this->price()->getCurrency());
            }

        }

    }

    public function cycle()
    {
        return $this->price["cycle"];
    }

    public function setup()
    {
        return $this->price["setupfee"];
    }

    public function price()
    {
        return $this->price["price"];
    }

    public function breakdown()
    {
        return $this->price["breakdown"];
    }

    public function toPrefixedString()
    {
        $priceString = "";
        $price = $this->price();
        if( !is_null($price) ) 
        {
            $priceString .= $price->toPrefixed() . "/" . $this->getShortCycle();
        }

        $setup = $this->setup();
        if( !is_null($setup) && 0 < $setup->toNumeric() ) 
        {
            $priceString .= " + " . $price->toPrefixed() . " Setup Fee";
        }

        return $priceString;
    }

    public function toSuffixedString()
    {
        $priceString = "";
        $price = $this->price();
        if( !is_null($price) ) 
        {
            $priceString .= $price->toSuffixed() . "/" . $this->getShortCycle();
        }

        $setup = $this->setup();
        if( !is_null($setup) && 0 < $setup->toNumeric() ) 
        {
            $priceString .= " + " . $price->toSuffixed() . " Setup Fee";
        }

        return $priceString;
    }

    public function toFullString()
    {
        $priceString = "";
        $price = $this->price();
        if( !is_null($price) ) 
        {
            $priceString .= $price->toFull() . "/" . $this->getShortCycle();
        }

        $setup = $this->setup();
        if( !is_null($setup) && 0 < $setup->toNumeric() ) 
        {
            $priceString .= " + " . $price->toFull() . " Setup Fee";
        }

        return $priceString;
    }

    public function getShortCycle()
    {
        switch( $this->cycle() ) 
        {
            case "monthly":
                return "mo";
            case "quarterly":
                return "qr";
            case "semiannually":
                return "6mo";
            case "annually":
                return "yr";
            case "biennially":
                return "2yr";
            case "triennially":
                return "3yr";
        }
    }

    public function isYearly()
    {
        return in_array($this->cycle(), array( "annually", "biennially", "triennially" ));
    }

    public function cycleInYears()
    {
        switch( $this->cycle() ) 
        {
            case "annually":
                return "1 Year";
            case "biennially":
                return "2 Years";
            case "triennially":
                return "3 Years";
        }
    }

    public function yearlyPrice()
    {
        return $this->breakdown()["yearly"]->toFull() . "/yr";
    }

    public function cycleInMonths()
    {
        switch( $this->cycle() ) 
        {
            case "monthly":
                return "1 Month";
            case "quarterly":
                return "3 Months";
            case "semiannually":
                return "6 Months";
        }
    }

    public function monthlyPrice()
    {
        return $this->breakdown()["monthly"]->toFull() . "/mo";
    }

    public function breakdownPrice()
    {
        if( $this->isYearly() ) 
        {
            return $this->yearlyPrice();
        }

        return $this->monthlyPrice();
    }

    public function breakdownPriceNumeric()
    {
        if( $this->isYearly() ) 
        {
            return (double) $this->breakdown()["yearly"]->toNumeric();
        }

        return (double) $this->breakdown()["monthly"]->toNumeric();
    }

}


