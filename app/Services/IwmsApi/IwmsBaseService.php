<?php

namespace App\Services\IwmsApi;

use App\Models\IwmsMerchantWarehouseMapping;
use App\Models\IwmsMerchantCourierMapping;
use App;

trait IwmsBaseService
{
    private $iwmStatus = array("Success" => "1","Failed" => "2");
    private $token = "iwms-esg";

    public function getNewBatchId($name, $requestLog = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getNewBatch($name, $requestLog);
    }

    public function getIwmsWarehouseCode($warehouseId,$merchantId)
    {
        return IwmsMerchantWarehouseMapping::where("merchant_warehouse_id",$warehouseId)
            ->where("merchant_id", $merchantId)
            ->value("iwms_warehouse_code");
    }

    public function getIwmsCourierCode($courierId,$merchantId)
    {
        return IwmsMerchantCourierMapping::where("merchant_courier_id",$courierId)
            ->where("merchant_id", $merchantId)
            ->value("iwms_courier_code");
    }

    public function saveBatchIwmsResponseData($batchObject,$responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->saveBatchIwmsResponseData($batchObject,$responseJson);
    }

    public function validIwmsCallBackApiToken()
    {   
        $iwmsCallbackApiService = App::make("App\Services\IwmsCallbackApiService");
        return $iwmsCallbackApiService->valid();
    }

}