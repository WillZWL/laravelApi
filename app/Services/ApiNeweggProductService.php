<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use newegg api package
use App\Repository\NeweggMws\NeweggCore;

class ApiNeweggProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    private $storeCurrency;
    private $resourceMethod;
    public function __construct()
    {
        $this->setResourceMethod("POST");
    }

    public function getPlatformId()
    {
        return 'Newegg';
    }

    public function submitProductPrice($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_PRICE);
        if(!$processStatusProduct->isEmpty())
        {
            foreach ($processStatusProduct as $index => $pendingSku) 
            {
                $requestXml[] = '<ItemPriceInfo>';
                $requestXml[] =     '<Type>1</Type>';
                $requestXml[] =     '<Value>'.$pendingSku->marketplace_sku.'</Value>';
                $requestXml[] =     '<PriceList>';
                $requestXml[] =         '<Price>';
                $requestXml[] =             '<CountryCode>'.$pendingSku->id_3_digit.'</CountryCode>';
                $requestXml[] =             '<Currency>'.$pendingSku->currency.'</Currency>';
                $requestXml[] =             '<SellingPrice>'.$pendingSku->price.'</SellingPrice>';
                $requestXml[] =             '<MSRP>'.number_format(($pendingSku->price*1.3), 2, '.', '').'</MSRP>';
                $requestXml[] =         '</Price>';
                $requestXml[] =     '</PriceList>';
                $requestXml[] = '</ItemPriceInfo>';
                $requestXml = implode("\n", $requestXml);
                $requestParams = $this->getRequestParams();
                $this->neweggCore = new NeweggCore($storeName);
                $result = $this->neweggCore->query("contentmgmt/item/international/price", $this->getResourceMethod(), $requestParams, $requestXml);
                if($result)
                {
                    $this->updatePendingProductProcessStatus($processStatusProduct,self::PENDING_PRICE);
                }
            }
        }
    }

    public function submitProductPriceAndInventory($storeName)
    {

    }

    public function submitProductCreate($storeName)
    {

    }

    public function getProductList($storeName)
    {

    }

    protected function getRequestParams()
    {
        $requestParams = [""=>""];
        return $requestParams;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl($value)
    {
        $this->resourceUrl = $value;
    }

    private function convertFromUtcToPst($timestamp, $format = "Y-m-d")
    {
        if($timestamp) {
             // change timezone to Pacific Standard
            $dt = new \DateTime($timestamp);
            $dt->setTimezone(new \DateTimeZone("PST"));
            $dateTime = $dt->format($format);
            return $dateTime;
        }

        return "";
    }

    private function convertFromPstToUtc($timestamp, $timestampFormat = "d/m/Y H:i:s", $format = "Y-m-d H:i:s")
    {
        if($timestamp) {
            # Newegg's time is in PST
            # let DateTime know the format of your $timestamp
            $dtOrderdate = \DateTime::createFromFormat($timestampFormat, $timestamp, new \DateTimeZone("PST"));
            $dtOrderdate->setTimezone(new \DateTimeZone("UTC"));
            $utcOrderDate = $dtOrderdate->format($format);
            return $utcOrderDate;
        }

        return "";
    }
}
