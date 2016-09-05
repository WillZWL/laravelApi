<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use fnac api package
use App\Repository\FnacMws\FnacProductList;
use App\Repository\FnacMws\FnacProductUpdate;

class ApiFnacProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    public function __construct()
    {

    }

    public function getPlatformId()
    {
        return 'Fnac';
    }

    public function getProductList($storeName)
    {
        $this->fnacProductList = new FnacProductList($storeName);

        $orginProductList = $this->fnacProductList->fetchProductList();

        $this->saveDataToFile(serialize($orginProductList), 'getProductList');

        return $orginProductList;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = self::PENDING_PRICE | self::PENDING_INVENTORY;
        $pendingProducts = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);          
        if(!$pendingProducts->isEmpty()){
            $this->fnacProductUpdate = new FnacProductUpdate($storeName);
            $xmlData = $this->fnacProductUpdate->setRequestUpdateOfferXml($pendingProducts);
            $this->saveDataToFile(serialize($xmlData), 'pendingPriceAndInventory');
            $responseBatchData = $this->fnacProductUpdate->requestFnacUpdateOffer();
            $this->saveDataToFile(serialize($responseBatchData), 'responseBatchPriceAndInventory');
            if ($responseBatchData['@attributes']['status'] == 'OK'
                || $responseBatchData['@attributes']['status'] == 'ACTIVE'
                || $responseBatchData['@attributes']['status'] == 'RUNNING'
            ) {
                if (
                    $responseBatchData['@attributes']['status'] == 'ACTIVE'
                    || $responseBatchData['@attributes']['status'] == 'RUNNING'
                ) {
                    sleep(60);
                }

                $batchId = $responseBatchData['batch_id'];
                $responseData = $this->fnacProductUpdate->sendFnacBatchStatusRequest($batchId);
                $this->saveDataToFile(serialize($responseData), 'responseResultProduct'.$updateAction);
                if ($responseData['@attributes']['status'] == 'OK') {
                    $this->updatePendingProductProcessStatus($pendingProducts,$processStatus);
                    return $responseData;
                }
            }
        }
    }

    public function submitProductCreate($storeName)
    {

    }

    public function submitProductUpdate($storeName)
    {
        $this->runProductUpdate($storeName, 'pendingProduct');
    }
}
