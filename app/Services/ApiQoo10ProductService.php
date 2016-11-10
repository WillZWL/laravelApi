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
        $response = $this->qoo10Product->getProduct();
        $this->saveDataToFile(serialize($response), 'responseProductData');
        if (isset($response['ResultCode'])
            && $response['ResultCode'] == 0
        ) {
            return $response;
        }
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
            $reqsponseAllData = [];
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
                    $reqsponseAllData[] = $updatePriceData;

                    if (isset($response['ResultCode'])
                        && $response['ResultCode'] == 0
                        && isset($response['ResultMsg'])
                    ) {
                        $this->updatePendingProductProcessStatusBySku($object,PlatformMarketConstService::PENDING_PRICE);
                    } else {
                        $to = 'qoo10sg@brandsconnect.net, celine@eservicesgroup.net';
                        $header = "From: admin@eservicesgroup.com\r\n";
                        $header .= "Cc: brave.liu@eservicesgroup.com\r\n";
                        $message = serialize($updatePriceData);
                        mail($to, "Alert, Update price to qoo10 failed", $message, $header);
                    }
                }
            }
            if ($reqsponseAllData) {
                $this->saveDataToFile(serialize($reqsponseAllData), 'responseUpdatePrice');
            }
        }
    }

    public function submitProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::where('marketplace_sku_mapping.asin', '!=', '')
            ->ProcessStatusProduct($storeName,PlatformMarketConstService::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty()){
            $this->qoo10ProductUpdate = new Qoo10ProductUpdate($storeName);
            $reqsponseAllData = [];
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
                    $reqsponseAllData[] = $updateInventoryData;

                    if (isset($response['ResultCode'])
                        && $response['ResultCode'] == 0
                        && isset($response['ResultMsg'])
                    ) {
                        $this->updatePendingProductProcessStatusBySku($object,PlatformMarketConstService::PENDING_INVENTORY);
                    } else {
                        $to = 'qoo10sg@brandsconnect.net, celine@eservicesgroup.net';
                        $header = "From: admin@eservicesgroup.com\r\n";
                        $header .= "Cc: brave.liu@eservicesgroup.com\r\n";
                        $message = serialize($updateInventoryData);
                        mail($to, "Alert, Update inventory to qoo10 failed", $message, $header);
                    }
                }
            }

            if ($reqsponseAllData) {
                $this->saveDataToFile(serialize($reqsponseAllData), 'responseUpdateInventory');
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