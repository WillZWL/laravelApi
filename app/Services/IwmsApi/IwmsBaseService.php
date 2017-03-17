<?php

namespace App\Services\IwmsApi;

use App\Models\IwmsMerchantWarehouseMapping;
use App\Models\IwmsMerchantCourierMapping;
use App\Models\So;
use App;
use Excel;

trait IwmsBaseService
{
    use \App\Services\BaseMailService;

    private $iwmStatus = array("Success" => "1","Failed" => "2");
    private $token = "iwms-esg";
    private $awbLabelCourierList = null;
    private $invoiceLabelCourierList = null;
    //private $accessInfo = "&email=openapi@4px.com&password=^4pxOpenApi/ygO";
    private $downloadToken = "&token=ik5oHdNIn3luiB4AQ7hyFoQmWu6aGJC1Qahjrbxm";

    public function getNewBatchId($name,$wmsPlatform, $merchantId, $requestLog = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getNewBatch($name,$wmsPlatform, $merchantId, $requestLog);
    }

    public function getDeliveryOrderLogBatch($batchRequestId = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getDeliveryOrderLogBatch($batchRequestId);
    }

    public function getIwmsWarehouseCode($warehouseId,$merchantId)
    {
        $iwmsMerchantWarehouseMapping = IwmsMerchantWarehouseMapping::where("merchant_warehouse_id",$warehouseId)
            ->where("merchant_id", $merchantId)
            ->first();
        if ($iwmsMerchantWarehouseMapping) {
            return $iwmsMerchantWarehouseMapping->iwms_warehouse_code;
        }
        return null;
    }


    public function getMerchantWarehouseCode($iwmsWarehouseCode, $merchantId)
    {
        $iwmsMerchantWarehouseMapping = IwmsMerchantWarehouseMapping::where("iwms_warehouse_code", $iwmsWarehouseCode)
            ->where("merchant_id", $merchantId)
            ->where("status", 1)
            ->first();
        if ($iwmsMerchantWarehouseMapping) {
            return $iwmsMerchantWarehouseMapping->merchant_warehouse_id;
        }
        return null;
    }

    public function getIwmsCourierCode($courierId, $merchantId, $wmsPlatform)
    {
        $iwmsMerchantCourierMapping = IwmsMerchantCourierMapping::where("merchant_courier_id",$courierId)
            ->where("wms_platform", $wmsPlatform)
            ->where("merchant_id", $merchantId)
            ->where("status", 1)
            ->first();

        if ($iwmsMerchantCourierMapping) {
            return $iwmsMerchantCourierMapping->iwms_courier_code;
        }
        return null;
    }

    public function getIwmsCourierApiMappingList($wmsPlatform, $merchantId)
    {
        return IwmsMerchantCourierMapping::where("wms_platform", $wmsPlatform)
            ->where("merchant_id", $merchantId)
            ->where("status", 1)
            ->pluck("merchant_courier_id")
            ->all();
    }

    public function saveBatchFeedIwmsResponseData($batchObject,$responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->saveBatchFeedIwmsResponseData($batchObject,$responseJson);
    }

    public function updateBatchIwmsResponseData($batchObject, $responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->updateBatchIwmsResponseData($batchObject, $responseJson);
    }

    public function validIwmsCallBackApiToken()
    {
        $iwmsCallbackApiService = App::make("App\Services\IwmsCallbackApiService");
        return $iwmsCallbackApiService->valid();
    }

    public function getSoAllocatedPickListNo($esgOrderNo)
    {
        $esgOrder = So::where("so_no", $esgOrderNo)
                    ->first();
        if(!empty($esgOrder)){
            return $esgOrder->pick_list_no;
        }
    }

    public function getCourierPickListFilePathByType($esgOrderNo, $documentType)
    {
        $esgOrder = So::where("so_no", $esgOrderNo)
                    ->with("courierInfo")
                    ->first();
        if(!empty($esgOrder)){
           $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix();
            $filePath .= $esgOrder->pick_list_no."/".$documentType."/".$esgOrder->courierInfo->courier_name."/";
            return $urlPath.$this->downloadToken;
        }
    }

    public function getEsgOrderAwbLabelUrl($esgOrder)
    {
        if(!empty($esgOrder->pick_list_no)){
            $baseUrl = config('app.url');
            $urlPath = $baseUrl."/order/".$esgOrder->pick_list_no."/AWB?so_no=".$esgOrder->so_no;
            return $urlPath.$this->downloadToken;
        }
        return null;
    }

    public function getEsgOrderInvoiceLabelUrl($esgOrder)
    {
        if(!empty($esgOrder->pick_list_no)){
            $baseUrl = config('app.url');
            $urlPath = $baseUrl."/order/".$esgOrder->pick_list_no."/invoice?so_no=".$esgOrder->so_no;
           return $urlPath.$this->downloadToken;
        }
        return null;
    }

    public function getEsgOrderMsdsLabelUrl($esgOrder)
    {
        /*if(!empty($esgOrder->pick_list_no)){
            $baseUrl = config('app.url');
            $urlPath = $baseUrl."/product/".$esgOrder->pick_list_no."/msds?sku=".$esgOrder->so_no;
           return $urlPath;
        }*/
        return null;
    }

    public function getPostAwbLabelToIwmsCourierList($merchantId)
    {
        if(empty($this->awbLabelCourierList)){
            $this->awbLabelCourierList = IwmsMerchantCourierMapping::where("wms_platform","4px")
                    ->whereIn("iwms_courier_code", ["4PX-DHL","4PX-PL-LGS"])
                    ->where("merchant_id", $merchantId)
                    ->pluck("merchant_courier_id")
                    ->all();
        }
        return $this->awbLabelCourierList;
    }

    public function getPostInvoiceLabelToIwmsCourierList($merchantId)
    {
        if(empty($this->invoiceLabelCourierList)){
            $this->invoiceLabelCourierList = IwmsMerchantCourierMapping::where("wms_platform", "4px")
                    //->whereIn("iwms_courier_code", ["4PX-DHL","4PX-PL-LGS"])
                    ->where("merchant_id", $merchantId)
                    ->pluck("merchant_courier_id")
                    ->all();
        }
        return $this->invoiceLabelCourierList;
    }

    public function getLgsOrderMerchantCourierIdList($wmsPlatform, $merchantId)
    {
        $iwmsLgsCode = array(
            "4px" => array("4PX-PL-LGS")
        );
        return IwmsMerchantCourierMapping::where("wms_platform", $wmsPlatform)
                    ->whereIn("iwms_courier_code", $iwmsLgsCode[$wmsPlatform])
                    ->where("merchant_id", $merchantId)
                    ->pluck("merchant_courier_id")
                    ->all();
    }

}