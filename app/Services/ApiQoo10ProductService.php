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

    public function getProduct($sku, $storeName)
    {
        $this->qoo10Product = new Qoo10Product($storeName);
        $this->qoo10Product->setSku($sku);
        $responseProductData = $this->qoo10Product->getProduct();
        $this->saveDataToFile(serialize($responseProductData), 'responseProductData');

        return $responseProductData;
    }

    public function submitProductPriceAndInventory($storeName)
    {

    }

    public function submitProductCreate($storeName,$productGroup)
    {

    }

    public function submitProductUpdate()
    {

    }

}