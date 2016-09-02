<?php

namespace App\Repository\FnacMws;

class FnacProductUpdate extends FnacProductsCore
{

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setFnacAction('offers_update');
    }

    public function requestFnacUpdateOffer()
    {
        return parent::callFnacApi($this->getRequestXml());
    }

    public function setRequestUpdateOfferXml($processStatusProduct, $updateAction)
    {
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<offers_update '. $this->getAuthKeyWithToken() .'>';
        foreach ($processStatusProduct as $index => $pendingSku) {
            $msgDom = '<offer>';
            $msgDom .=      '<offer_reference type="SellerSku">'. $pendingSku->marketplace_sku .'</offer_reference>';
            if ($updateAction == 'Price') {
                $msgDom .= '<price>'. $pendingSku->price .'</price>';
            } else if ($updateAction == 'Inventory') {
                $msgDom .= '<quantity>'. $pendingSku->inventory .'</quantity>';
            }
            $msgDom .= '</offer>';

            $xmlData .= $msgDom;
        }
        $xmlData .= '</offers_update>';

        return $this->requestXml = $xmlData;
    }

    public function sendFnacBatchStatusRequest($batchId)
    {
        $this->setFnacAction('batch_status');

        $this->setBatchStatusXml($batchId);

        return parent::query($this->getRequestXml());
    }

    public function setBatchStatusXml($batchId)
    {
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<batch_status '. $this->getAuthKeyWithToken() .'>';
        $xmlData .=     '<batch_id>'. $batchId .'</batch_id>';
        $xmlData .= '</batch_status>';

        $this->requestXml = $xmlData;
    }
}
