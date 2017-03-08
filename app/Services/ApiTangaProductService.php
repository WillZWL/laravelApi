<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use tanga api package
use App\Repository\TangaMws\TangaProductList;
use App\Repository\TangaMws\TangaProductUpdate;

class ApiTangaProductService implements ApiPlatformProductInterface
{
    use ApiBaseProductTraitService;

    public function getPlatformId()
    {
        return 'Tanga';
    }

    public function getProductList($storeName)
    {
        //
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);

        if(!$processStatusProduct->isEmpty()){
            foreach ($processStatusProduct as $index => $pendingSku) {
                $requestData = [];
                $requestData['vendor_product_listing_code'] = $pendingSku->marketplace_sku;
                $requestData['skus'][] = [
                    'cost' => $pendingSku->price,
                    'quantity' => $pendingSku->inventory,
                    'vendor_sku_code' => $pendingSku->marketplace_sku,
                ];
                $this->saveDataToFile(serialize($requestData), 'requestUpdatePriceAndInventory');
                $this->tangaProductUpdate = new TangaProductUpdate($storeName);
                $responseData = $this->tangaProductUpdate->updatePriceAndInventory($requestData);
                $this->saveDataToFile(serialize($responseData), 'responseUpdatePriceAndInventory');

                if (! isset($responseData['errors'])
                    && isset($responseData['vendor_product_listing_code'])
                    && $responseData['vendor_product_listing_code'] == $pendingSku->marketplace_sku
                ) {
                    $this->updatePendingProductProcessStatusBySku($pendingSku, $processStatus);
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
