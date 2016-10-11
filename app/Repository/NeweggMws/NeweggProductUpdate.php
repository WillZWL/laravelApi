<?php

namespace App\Repository\NeweggMws;

class NeweggProductUpdate extends NeweggOrderCore
{
    private $resourceMethod;
    private $xsdFile = "\OrderMgmt\GetOrderInfo\GetOrderInfoRequest.xsd";

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setResourceMethod("POST");
    }

    /**
     * update product inventory
     */
    public function updateInternationalInventory($requestObject,$warehouseInventory)
    {
        $resourceUrl = 'contentmgmt/item/international/inventory';
        $requestXml = $this->getInternationalInventoryRequestXml($requestObject,$warehouseInventory);
        $requestParams = parent::initRequestParams();
        $result = parent::query($resourceUrl, $this->getResourceMethod(), $requestParams, $requestXml);
        return  $result;
    }

    /**
     * update product price
     */
    public function updateInternationalPrice($requestObject)
    {
        $resourceUrl = 'contentmgmt/item/international/price';
        $requestXml = $this->getInternationalPriceRequestXml($requestObject);
        $requestParams = parent::initRequestParams();
        $result = parent::query($resourceUrl, $this->getResourceMethod(), $requestParams, $requestXml);
        return  $result;
    }


    /**
     * update product inventory and price
     */
    public function updateInventoryandPrice($requestObject)
    {
        $resourceUrl = 'contentmgmt/item/inventoryandprice';
        $requestXml = $this->getInventoryandPriceRequestXml($requestObject);
        $requestParams = parent::initRequestParams();
        $result = parent::query($resourceUrl, $this->getResourceMethod(), $requestParams, $requestXml);
        return  $result;
    }

    /**
     * Get update inventory request xml data
     */
    private function getInternationalInventoryRequestXml($requestObject,$warehouseInventory)
    {
        $requestXml = array();
        $requestXml[] = '<ItemInventoryInfo>';
        $requestXml[] =     '<Type>1</Type>';
        $requestXml[] =     '<Value>'.$requestObject->marketplace_sku.'</Value>';
        $requestXml[] =     '<InventoryList>';
        foreach($warehouseInventory as $inventoryDetails){
            if ($inventoryDetails["FulfillmentOption"] == 0) //Only update item shipped by seller
            {
                $requestXml[] =         '<Inventory>';
                $requestXml[] =             '<WarehouseLocation>'.$inventoryDetails["WarehouseLocation"].'</WarehouseLocation>';
                $requestXml[] =             '<AvailableQuantity>'.$requestObject->inventory.'</AvailableQuantity>';
                $requestXml[] =         '</Inventory>';
            }
        }
        $requestXml[] =     '</InventoryList>';
        $requestXml[] = '</ItemInventoryInfo>';
        
        return implode("\n", $requestXml);
    }

    /**
     * Get update price request xml data
     */
    private function getInternationalPriceRequestXml($requestObject)
    {
        $requestXml = array();
        $requestXml[] = '<ItemPriceInfo>';
        $requestXml[] =     '<Type>1</Type>';
        $requestXml[] =     '<Value>'.$requestObject->marketplace_sku.'</Value>';
        $requestXml[] =     '<PriceList>';
        $requestXml[] =         '<Price>';
        $requestXml[] =             '<CountryCode>'.$requestObject->id_3_digit.'</CountryCode>';
        $requestXml[] =             '<Currency>'.$requestObject->currency.'</Currency>';
        $requestXml[] =             '<SellingPrice>'.$requestObject->price.'</SellingPrice>';
        $requestXml[] =             '<MSRP>'.number_format(($requestObject->price*1.3), 2, '.', '').'</MSRP>';
        $requestXml[] =         '</Price>';
        $requestXml[] =     '</PriceList>';
        $requestXml[] = '</ItemPriceInfo>';

        return implode("\n", $requestXml);
    }

    /**
     * Get update inventory and price request xml data
     */
    private function getInventoryandPriceRequestXml($requestObject)
    {
        $requestXml = array();
        $requestXml[] = '<ItemInventoryAndPriceInfo>';
        $requestXml[] =     '<Type>1</Type>';
        $requestXml[] =     '<Value>'.$requestObject->marketplaceSku.'</Value>';
        $requestXml[] =     '<Inventory>'.$requestObject->inventory.'</Inventory>';
        $requestXml[] =     '<MSRP>'.$requestObject->msrp.'</MSRP>';
        $requestXml[] =     '<MAP>'.$requestObject->map.'</MAP>';
        $requestXml[] =     '<CheckoutMAP>'.$requestObject->checkoutMAP.'</CheckoutMAP>';
        $requestXml[] =     '<SellingPrice>'.$requestObject->sellingPrice.'</SellingPrice>';
        $requestXml[] =     '<EnableFreeShipping>'.$requestObject->enableFreeShipping.'</EnableFreeShipping>';
        $requestXml[] =     '<Active>'.$requestObject->active.'</Active>';
        $requestXml[] =     '<Condition>'.$requestObject->condition.'</Condition>';
        $requestXml[] =     '<FulfillmentOption>'.$requestObject->fulfillmentOption.'</FulfillmentOption>';
        $requestXml[] = '</ItemInventoryAndPriceInfo>';
        
        return implode("\n", $requestXml);
    }

    public function getAvailabilityWarehose($marketplaceSku)
    {
        $resourceUrl = 'contentmgmt/item/international/inventory';
        $requestXml = array();
        $requestXml[] = '<ContentQueryCriteria>';
        $requestXml[] =     '<Type>1</Type>';
        $requestXml[] =     '<Value>'.$marketplaceSku.'</Value>';
        $requestXml[] = '</ContentQueryCriteria>';
        $requestXml = implode("\n", $requestXml);
        $requestParams = parent::initRequestParams();
        $result = parent::query($resourceUrl, 'PUT', $requestParams, $requestXml);

        return  $result;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }
}
