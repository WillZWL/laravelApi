<?php

namespace App\Services\IwmsApi;

use App\Models\So;
use App\Models\IwmsFeedRequest;
use App\Models\IwmsLgsOrderStatusLog;
use App\Models\IwmsDeliveryOrderLog;
use App\Models\IwmsCourierOrderLog;
use App\Models\PlatformMarketOrder;
use App\Models\IwmsMerchantCourierMapping;

use App;

class IwmsFactoryWmsService extends IwmsCoreService
{
    use IwmsBaseService;
    protected $wmsPlatform;
    protected $merchantId = "ESG";
    private $excludeMerchant = array("PREPD");

    private $iwmsCreateDeliveryOrderService = null;
    private $iwmsCancelDeliveryOrderService = null;
    private $iwmsCourierOrderService = null;
    private $iwmsFulfillmentOrderService = null;

    public function __construct($wmsPlatform = "", $debug = 0)
    {
        $this->wmsPlatform = $wmsPlatform;
        parent::__construct($wmsPlatform, $debug);
    }

    public function createDeliveryOrder($merchantId, $esgOrderNoList = null)
    {
        try {
            if(!empty($esgOrderNoList)){
                $request = $this->getIwmsCreateDeliveryOrderService()->getDeliveryCreationRequestByOrderNo($esgOrderNoList, $merchantId);
            }else{
                //cron job request
                $warehouseToIwms = $this->getWarehouseToIwms($this->wmsPlatform, $merchantId);
                $request = $this->getIwmsCreateDeliveryOrderService()->getDeliveryCreationRequest($warehouseToIwms, $merchantId);
            }
            if (!$request["requestBody"]) {
                return false;
            }
            $responseData = $this->curlIwmsApi('wms/create-delivery-order', $request["requestBody"], $merchantId);
            $this->saveBatchFeedIwmsResponseData($request["batchRequest"],$responseData);
        } catch (Exception $e) {
            $msg = "Message: ". $e->getMessage() .", Line: ". $e->getLine() .", File: ".$e->getFile();
            mail('brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com', '[Vanguard] Create Delivery Failed', $msg, 'From: admin@shop.eservciesgroup.com');
        }
    }

    public function createCourierOrder($esgOrderNoList = null)
    {
        try {
            if(!empty($esgOrderNoList)){
                $request = $this->getIwmsCourierOrderService()->getCourierCreationRequestByOrderNo($esgOrderNoList);
            }else{
                //cron job request
                $request = $this->getIwmsCourierOrderService()->getCourierCreationRequest();
            }
            if (!$request["requestBody"]) {
                return false;
            }

            $responseData = $this->curlIwmsApi('courier/create-order', $request["requestBody"]);
            $this->saveBatchFeedIwmsResponseData($request["batchRequest"],$responseData);
            $this->updateSoWaybillStatus($request["batchRequest"], "1");
        } catch (Exception $e) {
            $msg = $e->getMessage();
            mail('brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com', '[Vanguard] Create Courier order Failed', $msg, 'From: admin@shop.eservciesgroup.com');
        }
    }

    public function cancelDeliveryOrder($esgOrderNoList)
    {
        $merchantId = "ESG";
        $batchRequest = $this->getIwmsCancelDeliveryOrderService()->getDeliveryCancelRequest($esgOrderNoList);
        if(!empty($batchRequest->request_log)){
            $requestBody = json_decode($batchRequest->request_log);
            $responseData = $this->curlIwmsApi('cancel-ship-order',$requestBody, $merchantId);
            $this->getIwmsCancelDeliveryOrderService()->responseMsgCancelAction($batchRequest, $responseData);
            return true;
        }else{
            return false;
        }
    }

    public function createProduct($merchantId)
    {
        try {
            //cron job request
            $warehouseToIwms = $this->getWarehouseToIwms($this->wmsPlatform, $merchantId);
            $request = $this->getIwmsCreateProductService()->getProductCreationRequest($warehouseToIwms);
            if (!$request["requestBody"]) {
                return false;
            }
            $responseData = $this->curlIwmsApi('wms/create-product', $request["requestBody"], $merchantId);
            $this->saveBatchFeedIwmsResponseData($request["batchRequest"],$responseData);
        } catch (Exception $e) {
            $msg = "Message: ". $e->getMessage() .", Line: ". $e->getLine() .", File: ".$e->getFile();
            mail('brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com', '[Vanguard] Create OMS Product Failed', $msg, 'From: admin@shop.eservciesgroup.com');
        }
    }

    public function getDeliveryOrderDocument($esgOrderNoList, $documentType)
    {
        $batchRequest = $this->getIwmsOrderDocumentService()->getOrderDocumentRequest($esgOrderNoList);
        if(!empty($batchRequest->request_log)){
            $requestBody = json_decode($batchRequest->request_log);
            $responseData = $this->curlIwmsApi('order-document/'.$documentType.
                "/",$requestBody);
            $document = $this->getIwmsOrderDocumentService()->downloadDocument($batchRequest, $responseData);
            return true;
        }else{
            return false;
        }
    }

    public function sendCreateDeliveryOrderReport($merchantId)
    {
        $iwmsRequestIds = IwmsFeedRequest::where("status","0")->pluck("iwms_request_id")->all();
        $cellData = $this->getCreateDeliveryOrderReport($iwmsRequestIds, $merchantId);
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."orderCreate/";
        $fileName = "deliveryOrderDetail-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "OMS Delivery Order Create Report!";
                $attachment = array("path" => $orderPath,"file_name"=>$fileName.".xlsx");
                $this->sendAttachmentMail('fiona@etradegroup.net',$subject,$attachment);
            }
        }
    }

    private function getCreateDeliveryOrderReport($iwmsRequestIds, $merchantId = "ESG")
    {
        $requestBody = array("request_id" => $iwmsRequestIds);
        $responseJson = $this->curlIwmsApi('wms/get-delivery-order-report', $requestBody, $merchantId);
        if(!empty($responseJson)){
            $responseData = json_decode($responseJson);
            $cellData[] = array('Business Type', 'Merchant', 'Platform', 'Order ID', 'DELIVERY TYPE ID', 'Country', 'Battery Type', 'Rec. Courier', '4PX OMS delivery order ID', 'Pass to 4PX courier');
            foreach ($responseData as $requestId => $deliveryOrders) {
                foreach ($deliveryOrders as $value) {
                    $esgOrder = So::where("so_no",$value->merchant_order_id)
                            ->with("sellingPlatform")
                            ->first();
                    if(!empty($esgOrder)){
                       $cellRow = array(
                            'business_type' => $value->business_type,
                            'merchant' => $esgOrder->sellingPlatform->merchant_id,
                            'platform' => $esgOrder->platform_id,
                            'order_id' => $value->reference_no,
                            'delivery_type_id' => $esgOrder->delivery_type_id,
                            'country' => $value->country,
                            'battery_type' => "",
                            're_courier' => $esgOrder->recommend_courier_id,
                            'wms_order_code' => $value->wms_order_code,
                            'wms_courier' => $value->iwms_courier,
                        );
                        $cellData[] = $cellRow;
                    }
                }
                IwmsFeedRequest::where("iwms_request_id",$requestId)->update(array("status"=> "1"));
            }
            return $cellData;
        }
        return null;
    }

    public function queryDeliveryOrderStatus($iwmsOrderCode, $merchantId = "ESG")
    {
        $requestBody = array(
            "order_code" => $iwmsOrderCode,
            );
        $responseData = $this->curlIwmsApi('wms/query-delivery-order', $requestBody, $merchantId);
        return $responseData;
    }

    public function queryDeliveryOrderByWarehouse($merchantId = "ESG")
    {
        $warehouseId = "4PXDG_PL";
        $iwmsWarehouseCode = $this->getIwmsWarehouseCode($warehouseId, $merchantId);
        $requestBody = array(
            "iwms_warehouse_code" => $iwmsWarehouseCode,
            );
        $responseData = $this->curlIwmsApi('wms/query-delivery-order', $requestBody, $merchantId);
        print_r($responseData);exit();
        return $responseData;
    }

    public function getWarehouseToIwms($wmsPlatform, $merchantId)
    {
        $warehouseIdArr = array(
            '4px' => array(
                'ESG' => array("4PXDG_PL","4PX_B66"),
                "ESG-HK-WMS" => array("ES_HK"),
                )
            )
        );
        return $warehouseIdArr[$wmsPlatform][$merchantId];
    }

    public function cronSetLgsOrderStatus($merchantId)
    {
        $iwmsLgsOrderService = App::make('App\Services\IwmsApi\Order\IwmsLgsOrderService',
            [$this->wmsPlatform, $merchantId]);
        $warehouseToIwms = $this->getWarehouseToIwms($this->wmsPlatform, $merchantId);
        $iwmsLgsOrderService->setLgsOrderStatus($warehouseToIwms);
    }

    public function cronGetLgsOrderDocument($merchantId)
    {
        $iwmsLgsOrderService = App::make('App\Services\IwmsApi\Order\IwmsLgsOrderService', [$this->wmsPlatform, $merchantId]);
        $iwmsLgsOrderService->getIwmsLgsOrderDocument();
    }

    public function getCourierMappingList($wmsPlatform)
    {
        $courierList = IwmsMerchantCourierMapping::where("status", 1)
            ->where("wms_platform", $wmsPlatform)
            ->get();
        return $courierList;
    }

    /* public function getReadyToIwmsDeliveryOrder($wmsPlatform, $pageNum)
    {
        $courierList = $this->getCourierMappingList($wmsPlatform);
        $esgOrderQuery = So::where("status",3)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->where("esg_quotation_courier_id", $courierList)
            ->where("waybill_status", 2)
            ->where("dnote_invoice_status", 2)
            ->whereHas('sellingPlatform', function ($query) {
                $query->whereNotIn('merchant_id', $this->excludeMerchant);
            })
            ->with("client")
            ->with("soItem")
            ->paginate(20);
        return $esgOrder;
        $esgOrder = $this->getIwmsCreateDeliveryOrderService()->getReadyToIwmsDeliveryOrder(null, $wmsPlatform, $pageNum);
        return $esgOrder;
    }*/

    public function getReadyToIwmsCourierOrder($courier = null, $pageNum = null)
    {
        $esgOrder = $this->getIwmsCourierOrderService()->getReadyToIwmsCourierOrder(null, $courier, $pageNum);
        return $esgOrder;
    }

    public function getIwmsDeliveryOrderLogList($pageNum)
    {
        return IwmsDeliveryOrderLog::where("status", 1)
            ->whereNotNull("wms_order_code")
            ->paginate($pageNum);
    }

    public function getFailedIwmsCourierOrderLogList($pageNum)
    {
        return IwmsCourierOrderLog::where("status", -1)
            ->with("so")
            ->paginate($pageNum);
    }

    public function getIwmsCourierOrderLogList($pageNum)
    {
        return IwmsCourierOrderLog::where("status", 1)
            ->whereNotNull("wms_order_code")
            ->with("so")
            ->paginate($pageNum);
    }

    public function requestAllocationPlan($requestData, $merchantId)
    {
        $request = $this->getIwmsAllocationPlanService()->getAllocationPlanRequest($requestData);
        if (isset($request['requestBody']) && isset($request['batchRequest'])) {
            $responseData = $this->curlIwmsApi('allocation/create-allocation-plan', $request['requestBody'], $merchantId);
            //$this->updateBatchIwmsResponseData($request["batchRequest"], $responseData);
            $this->saveBatchFeedIwmsResponseData($request["batchRequest"],$responseData);
        }
    }

    public function getIwmsAllocationPlanService()
    {
        return $this->iwmsAllocationPlanService = App::make("App\Services\IwmsApi\Order\IwmsAllocationPlanService", [$this->wmsPlatform]);
    }

    public function getIwmsOrderDocumentService()
    {
        return $this->iwmsOrderDocumentService = App::make("App\Services\IwmsApi\Order\IwmsOrderDocumentService", [$this->wmsPlatform]);
    }

    public function getIwmsCreateDeliveryOrderService()
    {
        return $this->iwmsCreateDeliveryOrderService = App::make("App\Services\IwmsApi\Order\IwmsCreateDeliveryOrderService", [$this->wmsPlatform]);
    }

    public function getIwmsCancelDeliveryOrderService()
    {
        return $this->iwmsCancelDeliveryOrderService = App::make("App\Services\IwmsApi\Order\IwmsCancelDeliveryOrderService", [$this->wmsPlatform]);
    }

    public function getIwmsCreateProductService()
    {
        return $this->iwmsCreateProductService = App::make("App\Services\IwmsApi\Order\IwmsCreateProductService", [$this->wmsPlatform]);
    }

    public function getIwmsCourierOrderService()
    {
        return $this->iwmsCourierOrderService = App::make("App\Services\IwmsApi\Order\IwmsCourierOrderService", [$this->wmsPlatform]);
    }

    public function getApiLazadaService()
    {
        if ($this->apiLazadaService == null) {
            $this->apiLazadaService = App::make("App\Services\ApiLazadaService");
        }
        return $this->apiLazadaService;
    }

    private function updateSoWaybillStatus($batchRequest, $status)
    {
        $referenceNoList = IwmsCourierOrderLog::where("batch_id", $batchRequest->id)
                    ->pluck("reference_no")
                    ->all();
        if(!empty($referenceNoList)){
            So::whereIn("so_no", $referenceNoList)
                ->update(array("waybill_status" => $status));
        }
    }
}