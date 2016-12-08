<?php

namespace App\Services\IwmsApi;

class IwmsFactoryWmsService extends IwmsCoreService
{
    use IwmsCreateDeliveryOrderService;

    public function __construct($wmsPlatform = "4px",$debug = 0)
    {
        parent::__construct($wmsPlatform,$debug);
        
    }

    public function createDeliveryOrder()
    {
        $warehouseId = $this->getWarehouseId($this->wmsPlatform);
        $request = $this->getDeliveryCreationRequest($warehouseId);
        $responseData = $this->curlIwmsApi('create-delivery-order', $request["requestBody"]);
        $this->_saveIwmsDeliveryOrderResponseData($request["batchId"],$responseData);
    }

    public function queryProduct()
    {
        $requestBody = array(
            "sku" => "sku123"
            );
        $this->curlIwmsApi('query-product', $requestBody);
    }

    public function getWarehouseId($wmsPlatform)
    {
        $warehouseIdArr = array(
            '4px' => '4PXDG_PL',
        );
        return $warehouseIdArr[$wmsPlatform];
    }

}