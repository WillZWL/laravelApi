<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsCourierOrderLog;
use App;
use Illuminate\Database\Eloquent\Collection;

class IwmsCourierOrderService extends IwmsBaseOrderService
{
    private $excludeMerchant = array("PREPD");

    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getCourierCreationRequest()
    {
        $deliveryCreationRequest = null;
        $esgOrders = $this->getReadyToIwmsCourierOrder(50);
        $batchRequest = $this->getCourierCreationRequestBatch($esgOrders);
        return $this->getCourierCreationBatchRequest($batchRequest);
    }

    public function getCourierCreationRequestByOrderNo($esgOrderNoList)
    {
        $deliveryCreationRequest = null;
        $esgOrders = $this->getEsgCourierOrdersByOrderNo($esgOrderNoList);
        $batchRequest = $this->getCourierCreationRequestBatch($esgOrders);
        return $this->getCourierCreationBatchRequest($batchRequest);
    }

    public function getCourierCreationRequestBatch($esgOrders)
    {
        $esgAllocateOrder = null; $merchantId = "ESG"; 
        if(!$esgOrders->isEmpty()){
            $batchRequest = $this->getNewBatchId("CREATE_COURIER",$this->wmsPlatform,$merchantId);
            foreach ($esgOrders as $esgOrder) {
                $this->courierOrderCreationRequest($batchRequest->id , $esgOrder);
            }
            if(!empty($this->message)){
                $this->sendAlertEmail($this->message);
            }
            return $batchRequest;
        }
    }

    private function courierOrderCreationRequest($batchId , $esgOrder)
    {
        $courierOrderCreationRequest = $this->getCourierOrderCreationObject($esgOrder);
        if ($courierOrderCreationRequest) {
            $this->_saveIwmsCourierOrderRequestData($batchId,$courierOrderCreationRequest);
        }
    }

    private function getCourierCreationBatchRequest($batchRequest)
    {
        if(!empty($batchRequest)){
            $requestLogs = IwmsCourierOrderLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
            if(!empty($requestLogs)){
                foreach ($requestLogs as $requestLog) {
                    $creationRequest[] = json_decode($requestLog);
                }
                $request = array(
                    "batchRequest" => $batchRequest,
                    "requestBody" => $creationRequest
                );
                $batchRequest->request_log = json_encode($creationRequest);
                $batchRequest->save();
                return $request;
            } else {
                $batchRequest->remark = "No courier order request need send to iwms";
                $batchRequest->status = "CE";
                $batchRequest->save();
            }
        }
    }

    private function getCourierOrderCreationObject($esgOrder)
    {
        $merchantId = "ESG"; 
        $courierId = $esgOrder->esg_quotation_courier_id;
        $iwmsCourierCode = $this->getIwmsCourierCode($courierId, $merchantId, $this->wmsPlatform);
        if ($iwmsCourierCode === null) {
            $this->_setCourierMessage($merchantId, $courierId);
            $this->_setSoNoMessage($esgOrder->so_no);
            return false;
        }
        //send remark for depx and fedx for 4px
        return  $this->getCreationIwmsCourierOrderObject($esgOrder, $iwmsCourierCode);
    }

    public function _saveIwmsCourierOrderRequestData($batchId,$requestData)
    {
        $iwmsCourierOrderLog = new IwmsCourierOrderLog();
        $iwmsCourierOrderLog->batch_id = $batchId;
        $iwmsCourierOrderLog->wms_platform = $requestData["wms_platform"];
        $iwmsCourierOrderLog->merchant_id = $requestData["merchant_id"];
        $iwmsCourierOrderLog->sub_merchant_id = $requestData["sub_merchant_id"];
        $iwmsCourierOrderLog->tracking_no = $requestData["tracking_no"];
        $iwmsCourierOrderLog->store_name = $requestData["store_name"];
        $iwmsCourierOrderLog->reference_no = $requestData["reference_no"];
        $iwmsCourierOrderLog->marketplace_platform_id = $requestData["marketplace_platform_id"];
        $iwmsCourierOrderLog->iwms_courier_code = $requestData["iwms_courier_code"];
        $iwmsCourierOrderLog->platform_order_id = $requestData["marketplace_reference_no"];
        $iwmsCourierOrderLog->request_log = json_encode($requestData);
        $iwmsCourierOrderLog->status = "0";
        $iwmsCourierOrderLog->save();
        return $iwmsCourierOrderLog;
    }

    public function getReadyToIwmsCourierOrder($limit = null, $courier = null, $pageNum = null)
    {
        $this->fromData = date("2017-03-16 00:00:00");
        $this->toDate = date("Y-m-d 23:59:59");
        $wmsPlatform = "iwms"; $merchantId = "ESG";
        if($courier){
            $courierList = array($courier);
        }else{
            $courierList = $this->getIwmsCourierApiMappingList($wmsPlatform, $merchantId);
        }
        $esgOrderQuery = So::where("status", 5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("esg_quotation_courier_id", $courierList)
            ->where("waybill_status", 0)
            ->whereNotNull('pick_list_no')
            ->whereHas('sellingPlatform', function ($query) {
                $query->whereNotIn('merchant_id', $this->excludeMerchant);
            })
            ->whereHas('soAllocate', function ($query) {
                $query->where("modify_on", ">=", $this->fromData)
                    ->where("modify_on", "<=", $this->toDate)
                    ->where("status", 1);
            })
            ->with("client")
            ->with("soItem");
        if(!empty($limit)){
            $esgOrders = $esgOrderQuery->limit($limit);
        }
        if(!empty($pageNum)){
            $esgOrders = $esgOrderQuery->paginate($pageNum);
        }else{
            $esgOrders = $esgOrderQuery->get();
        }
        return $this->checkEsgAllocateCourierOrders($esgOrders);
    }

    private function getEsgCourierOrdersByOrderNo($esgOrderNoList)
    {
        $esgOrders = So::where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("so_no", $esgOrderNoList)
            ->with("sellingPlatform")
            ->with("client")
            ->with("soItem")
            ->get();
        return $esgOrders;
    }

    private function checkEsgAllocateCourierOrders($esgOrders)
    {
        $validEsgOrders = new Collection();
        if(!$esgOrders->isEmpty()){
            foreach($esgOrders as $esgOrder) {
                $valid = null;
                $postCode = $this->getValidPostCode($esgOrder->delivery_postcode, $esgOrder->delivery_country_id);
                if(!empty($postCode)){
                    $esgOrder->delivery_postcode = $postCode;
                }else{
                    $errorPostCodes[] =  $esgOrder->so_no;
                    continue;
                }
                $validEsgOrders[] = $esgOrder;
            }
            if(isset($errorPostCodeOrders) && $errorPostCodeOrders){
                $msg = null;
                $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                $subject = "create courier order failed.";
                foreach ($errorPostCodeOrders as $errorOrderNo) {
                    $msg .= "Order ID".$errorOrderNo." postal is null";
                }
                mail("dispatcher@eservicesgroup.com,  jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            }
        }
        return $validEsgOrders;
    }

    private function validRepeatRequestCourierOrder($esgOrder)
    {
        $this->esgOrderNo = $esgOrder->so_no;
        $requestOrderLog = IwmsCourierOrderLog::where("reference_no",$esgOrder->so_no)
                        ->where("status", 1)
                        ->orWhere(function ($query) {
                            $query->where("reference_no", $this->esgOrderNo)
                                ->whereIn("status", array("0","-1"))
                                ->where("repeat_request", "!=", 1);
                            })
                        ->first();
        if(!empty($requestOrderLog)){
            return false;
        }else{
            return true;
        }
    }

}