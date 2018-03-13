<?php 
namespace WHMCS\Product;


class Pricing
{
    protected $product = NULL;
    protected $pricing = NULL;

    public function __construct($product, $currency)
    {
        if( $product instanceof Product ) 
        {
            $this->product = $product;
            $entityType = "product";
            $paymentTypeKey = "paymentType";
        }
        else
        {
            if( $product instanceof Addon ) 
            {
                $this->product = $product;
                $entityType = "addon";
                $paymentTypeKey = "billingCycle";
            }
            else
            {
                throw new \WHMCS\Exception("Product input must be of type Product or Addon");
            }

        }

        switch( $this->product->$paymentTypeKey ) 
        {
            case "free":
                $this->pricing = array( "free" => array( "cycle" => "free", "setupfee" => new \WHMCS\View\Formatter\Price(0), "price" => new \WHMCS\View\Formatter\Price(0) ) );
                break;
            case "onetime":
                $productPricing = new \WHMCS\Pricing();
                $productPricing->loadPricing($entityType, $this->product->id, $currency);
                $cycles = $productPricing->getAllCycleOptionsIndexedByCycle();
                $this->pricing = array( "onetime" => $cycles["monthly"] );
                $this->pricing["onetime"]["cycle"] = "onetime";
                break;
            case "recurring":
            default:
                $productPricing = new \WHMCS\Pricing();
                $productPricing->loadPricing($entityType, $this->product->id, $currency);
                $this->pricing = $productPricing->getAllCycleOptionsIndexedByCycle();
                break;
        }
    }

    public function allAvailableCycles()
    {
        $cyclesToReturn = array(  );
        foreach( $this->pricing as $cycle => $data ) 
        {
            $cyclesToReturn[] = new Pricing\Price($data);
        }
        return $cyclesToReturn;
    }

    public function months($months)
    {
        $map = array( 1 => "monthly", 3 => "quarterly", 6 => "semiannual", 12 => "annual", 24 => "biennial", 36 => "triennial" );
        $key = $map[$months];
        return $this->$key();
    }

    public function byCycle($cycle)
    {
        $cycle = str_replace("lly", "l", $cycle);
        if( method_exists($this, $cycle) ) 
        {
            return $this->$cycle();
        }

        return null;
    }

    public function monthly()
    {
        return (isset($this->pricing["monthly"]) ? new Pricing\Price($this->pricing["monthly"]) : null);
    }

    public function quarterly()
    {
        return (isset($this->pricing["quarterly"]) ? new Pricing\Price($this->pricing["quarterly"]) : null);
    }

    public function semiannual()
    {
        return (isset($this->pricing["semiannually"]) ? new Pricing\Price($this->pricing["semiannually"]) : null);
    }

    public function semiannually()
    {
        return $this->semiannual();
    }

    public function annual()
    {
        return (isset($this->pricing["annually"]) ? new Pricing\Price($this->pricing["annually"]) : null);
    }

    public function annually()
    {
        return $this->annual();
    }

    public function biennial()
    {
        return (isset($this->pricing["biennially"]) ? new Pricing\Price($this->pricing["biennially"]) : null);
    }

    public function biennially()
    {
        return $this->biennial();
    }

    public function triennial()
    {
        return (isset($this->pricing["triennially"]) ? new Pricing\Price($this->pricing["triennially"]) : null);
    }

    public function triennially()
    {
        return $this->triennial();
    }

    public function best()
    {
        $bestPrice = null;
        $bestPriceCycle = null;
        $bestPriceInfo = null;
        foreach( $this->pricing as $cycle => $priceinfo ) 
        {
            $thisPrice = $priceinfo["breakdown"]["yearly"];
            if( is_null($bestPrice) || $thisPrice < $bestPrice ) 
            {
                $bestPrice = $thisPrice;
                $bestPriceInfo = $priceinfo;
            }

        }
        return new Pricing\Price($bestPriceInfo);
    }

    public function first()
    {
        return $this->allAvailableCycles()[0];
    }

}


