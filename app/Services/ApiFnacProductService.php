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

    public function submitProductPrice($storeName)
    {
        return $this->runProductUpdate($storeName, 'pendingPrice');
    }

    public function submitProductInventory($storeName)
    {
        return $this->runProductUpdate($storeName, 'pendingInventory');
    }

    protected function runProductUpdate($storeName,$action)
    {
        $processStatus = array(
            'pendingPrice' => self::PENDING_PRICE,
            'pendingInventory' => self::PENDING_INVENTORY,
            'pendingProduct' => self::PENDING_PRODUCT,
        );
        $pendingProducts = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus[$action]);
        if(!$pendingProducts->isEmpty()){
            if ($processStatus[$action] == self::PENDING_PRICE) {
                $updateAction = 'Price';
            } else if ($processStatus[$action] == self::PENDING_INVENTORY) {
                $updateAction = 'Inventory';
            } else if ($processStatus[$action] == self::PENDING_PRODUCT) {
                $updateAction = 'ALL';
            }

            $this->fnacProductUpdate = new FnacProductUpdate($storeName);

            $xmlData = $this->fnacProductUpdate->setRequestUpdateOfferXml($pendingProducts, $updateAction);
            $this->saveDataToFile(serialize($xmlData), 'pendingProduct'.$updateAction);

            $responseBatchData = $this->fnacProductUpdate->requestFnacUpdateOffer();
            $this->saveDataToFile(serialize($responseBatchData), 'responseBatchProduct'.$updateAction);

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
                    $this->updatePendingProductProcessStatus($pendingProducts,$processStatus[$action]);

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
