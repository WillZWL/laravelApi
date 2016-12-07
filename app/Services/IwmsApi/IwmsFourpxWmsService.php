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
        $requestBody = $this->gerEsgAllocateOrderRequest($this->warehouseId);
        $this->curlIwmsApi('create-delivery-order', $requestBody);
    }

    public function queryProduct()
    {
        $requestBody = array(
            "sku" => "sku123"
            );
        $this->curlIwmsApi('query-product', $requestBody);
    }

}