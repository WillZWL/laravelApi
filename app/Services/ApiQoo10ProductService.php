<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use qoo10 api package
use App\Repository\Qoo10Mws\Qoo10Product;
use App\Repository\Qoo10Mws\Qoo10ProductUpdate;

class ApiQoo10ProductService implements ApiPlatformProductInterface
{
    use ApiBaseProductTraitService;

    public function getPlatformId()
    {
        return 'Qoo10';
    }

    public function getProductList($storeName)
    {
        //
    }

    public function getProduct($itemCode, $storeName)
    {
        $this->qoo10Product = new Qoo10Product($storeName);
        $this->qoo10Product->setItemCode($itemCode);
        $responseProductData = $this->qoo10Product->getProduct();
        $this->saveDataToFile(serialize($responseProductData), 'responseProductData');

        return $responseProductData;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $this->submitProductPrice($storeName);
        $this->submitProductInventory($storeName);
    }

 public function submitProductPrice($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::where('marketplace_sku_mapping.asin', '!=', '')
            ->ProcessStatusProduct($storeName,PlatformMarketConstService::PENDING_PRICE);
        if(!$processStatusProduct->isEmpty()){
            $this->qoo10ProductUpdate = new Qoo10ProductUpdate($storeName);
            foreach ($processStatusProduct as $object) {
                if ($object->asin && $object->asin != 'X') {
                    $this->qoo10ProductUpdate->setItemCode($object->asin);
                    $this->qoo10ProductUpdate->setSellerCode($object->marketplace_sku);
                    $this->qoo10ProductUpdate->setItemPrice($object->price);
                    $this->qoo10ProductUpdate->setItemQty($object->inventory);
                    $response = $this->qoo10ProductUpdate->setGoodsPrice();

                    $updatePriceData = [
                        'ItemCode' => $object->asin,
                        'SellerCode' => $object->marketplace_sku,
                        'ItemPrice' => $object->price,
                        'ItemQty' => $object->inventory,
                        'response' => $response,
                    ];

                    $this->saveDataToFile(serialize($updatePriceData), 'responseUpdatePrice');

                    if ($response['ResultCode'] == 0) {
                        $this->updatePendingProductProcessStatusBySku($object,PlatformMarketConstService::PENDING_PRICE);
                    }
                }
            }
        }
    }

    public function submitProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::where('marketplace_sku_mapping.asin', '!=', '')
            ->ProcessStatusProduct($storeName,PlatformMarketConstService::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty()){
            $this->qoo10ProductUpdate = new Qoo10ProductUpdate($storeName);
            foreach ($processStatusProduct as $object) {
                if ($object->asin && $object->asin != 'X') {
                    $this->qoo10ProductUpdate->setItemCode($object->asin);
                    $this->qoo10ProductUpdate->setSellerCode($object->marketplace_sku);
                    $this->qoo10ProductUpdate->setItemQty($object->inventory);
                    $response = $this->qoo10ProductUpdate->setGoodsInventory();

                    $updateInventoryData = [
                        'ItemCode' => $object->asin,
                        'SellerCode' => $object->marketplace_sku,
                        'ItemQty' => $object->inventory,
                        'response' => $response,
                    ];

                    $this->saveDataToFile(serialize($updateInventoryData), 'responseUpdateInventory');

                    if ($response['ResultCode'] == 0) {
                        $this->updatePendingProductProcessStatusBySku($object,PlatformMarketConstService::PENDING_INVENTORY);
                    }
                }
            }
        }
    }

    public function submitProductCreate($storeName,$productGroup)
    {

    }

    public function submitProductUpdate()
    {

    }

}