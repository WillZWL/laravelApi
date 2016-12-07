<?php

namespace App\Services\IwmsApi;

class IwmsFourpxWmsService extends IwmsCoreService
{
    use IwmsBaseService;

    public function __construct($wmsPlatform = "4px",$debug = 0)
    {
        parent::__construct($wmsPlatform,$debug);
        $this->warehouseId = '4PXDG_PL';
    }

    public function createDeliveryOrder()
    {
        $esgOrder = $this->gerEsgAllocateOrder($this->warehouseId);
        $this->curlIwmsApi('create-delivery-order', $esgOrder);
    }

    public function queryProduct()
    {
        $requestBody = array(
            "sku" => "sku123"
            );
        $this->curlIwmsApi('query-product', $requestBody);
    }

}