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

    public function getProductList()
    {
        //
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);

        if(!$processStatusProduct->isEmpty()){
            $csvData = "vendor_sku_code,quantity";

            foreach ($processStatusProduct as $index => $pendingSku) {
                $csvData .= "\r\n$pendingSku->marketplace_sku,$pendingSku->inventory";
            }

            $this->saveDataToFile($csvData, 'requestProductInventory', 'csv');

            $this->tangaProductUpdate = new TangaProductUpdate($storeName);
            $this->storeCurrency = $this->tangaProductUpdate->getStoreCurrency();
            $responseData = $this->tangaProductUpdate->updateInventory($csvData);

            $this->saveDataToFile(serialize($responseData), 'responseProductInventory');

            if ( $responseData['status'] == 'ok') {
                $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus);

                return $responseData;
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