<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use tanga api package
use App\Repository\TangaMws\TangaProductList;
use App\Repository\TangaMws\TangaProductUpdate;

class ApiTangaProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    public function __construct()
    {

    }

    public function getPlatformId()
    {
        return 'Tanga';
    }

    public function getProductList($storeName)
    {

    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = self::PENDING_PRICE | self::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);

        if(!$processStatusProduct->isEmpty()){
            $this->tangaProductUpdate = new TangaProductUpdate($storeName);
            $this->storeCurrency = $this->tangaProductUpdate->getStoreCurrency();

            $result = [];
            $success = 0;

            foreach ($processStatusProduct as $index => $pendingSku) {
                $this->tangaProductUpdate->setVendorSkuCode($pendingSku->marketplace_sku);
                $this->tangaProductUpdate->setInStock($pendingSku->inventory);
                if ($respose = $this->tangaProductUpdate->updateInventoryToTanga()) {
                    if (
                        isset($respose['sku_code'])
                        && $respose['sku_code'] == $pendingSku->marketplace_sku
                        && $respose['available_to_sell_count'] == $pendingSku->inventory
                        && $respose['vendor_in_stock_count'] == $pendingSku->inventory
                    ) {
                        $success += 1;
                        $result[] = $respose;
                    }
                }

            }

            if (count($processStatusProduct) == $success) {
                $this->saveDataToFile(serialize($result), 'submitProductPriceOrInventory');
                $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus);
                return $result;
            }
        }
    }

    public function submitProductCreate($storeName)
    {

    }

    public function submitProductUpdate()
    {

    }

}