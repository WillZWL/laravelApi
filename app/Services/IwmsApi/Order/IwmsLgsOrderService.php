<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsLgsOrderStatusLog;
use App;

class IwmsLgsOrderService extends IwmsBaseOrderService
{
    private $excludeMerchant = array("PREPD");
    private $courierList = array();
    private $apiLazadaService = null;

    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function setLgsOrderStatus($warehouseToIwms)
    {
        $esgOrders = $this->getReadyToShipLgsOrder($warehouseToIwms, 2);
        $this->setLgsOrderStatusAndGetTracking($esgOrders);
    }

    public function setLgsOrderStatusByOrderNo($esgOrderNoList)
    {
        $esgOrders = $this->getEsgLgsOrdersByOrderNo($esgOrderNoList);
        $this->setLgsOrderStatusAndGetTracking($esgOrders);
    }

    private function setLgsOrderStatusAndGetTracking($esgOrders)
    {
        foreach ($esgOrders as $esgOrder) {
           $iwmsLgsOrderStatusLog = $this->setIwmsLgsOrderStatusToReadyToShip($esgOrder);
           if(empty($esgOrder->iwmsLgsOrderStatusLog) || $esgOrder->iwmsLgsOrderStatusLog->status != "1"){
                $error[] = $esgOrder->so_no;
            }
        }
        if( isset($error) && $error ){
            $subject = "LGS order get trackingNo failed.";
            $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
            foreach ($error as $key => $soNo) {
               $msg = "LGS Order ID: ".$soNo." can not get trackingNO.\r\n";
            }
            mail("jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    public function getIwmsLgsOrderDocument()
    {
        $esgOrders = $this->getReadyToGetDocumentLgsOrder();
        foreach ($esgOrders as $esgOrder) {
            if(!empty($esgOrder->iwmsLgsOrderStatusLog) && $esgOrder->iwmsLgsOrderStatusLog->status == 1){
                $result = $this->saveLgsOrderDocument($esgOrder);
                if($result){
                    $esgOrder->waybill_status = "2";
                    $esgOrder->dnote_status = "2";
                    $esgOrder->save();
                }
            }
        }
    }

    public function setIwmsLgsOrderStatusToReadyToShip($esgOrder)
    {
        $result = null;
        $iwmsLgsOrderStatusLog = $esgOrder->iwmsLgsOrderStatusLog;
        if(empty($iwmsLgsOrderStatusLog)) {
            $result = $this->getApiLazadaService()->iwmsSetLgsOrderReadyToShip($esgOrder);
            if(isset($result["tracking_no"]) && $result["tracking_no"]){
                return $this->createIwmsLgsOrderStatusLog($esgOrder, $result);
            }else{
                return false;
            }
        } else if($iwmsLgsOrderStatusLog->status != 1){
            $result = $this->getApiLazadaService()->iwmsSetLgsOrderReadyToShip($esgOrder, false);
            if(isset($result["valid"]) && $result["valid"]){
                $iwmsLgsOrderStatusLog->status = 1;
                $iwmsLgsOrderStatusLog->save();
            }
            return $iwmsLgsOrderStatusLog;
        }
    }

    public function createIwmsLgsOrderStatusLog($esgOrder, $result)
    {
        $object['iwms_platform'] = "iwms";
        $object['esg_courier_id'] = $esgOrder->esg_quotation_courier_id;
        $object['so_no'] = $esgOrder->so_no;
        $object['platform_order_no'] = $esgOrder->platform_order_id;
        $object['tracking_no'] = $result["tracking_no"];
        if(isset($result["valid"]) && $result["valid"]){
            $object['status'] = 1;
        }
        return IwmsLgsOrderStatusLog::updateOrCreate(['so_no' => $esgOrder->so_no],$object);
    }

    public function getReadyToShipLgsOrder($warehouseToIwms, $limit = null, $pageNum = null)
    {
        $this->warehouseIds = $warehouseToIwms;
        $courierIdList = $this->getLgsOrderMerchantCourierIdList($this->wmsPlatform);
        $esgOrderQuery = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("esg_quotation_courier_id", $courierIdList)
            ->where("waybill_status", 0)
            //->whereNotNull('pick_list_no')
            ->whereHas('sellingPlatform', function ($query) {
                $query->whereNotIn('merchant_id', $this->excludeMerchant);
            })
            ->whereHas('soAllocate', function ($query) {
                $query->whereIn('warehouse_id', $this->warehouseIds)
                    ->where("status", 1);
            })
            ->with("iwmsLgsOrderStatusLog");
        if(!empty($limit)){
            $esgOrder = $esgOrderQuery->limit($limit);
        }
        if(!empty($pageNum)){
            $esgOrder = $esgOrderQuery->paginate($pageNum);
        }else{
            $esgOrder = $esgOrderQuery->get();
        }
        return $esgOrder;
    }

    public function getReadyToGetDocumentLgsOrder()
    {
        $courierIdList = $this->getLgsOrderMerchantCourierIdList($this->wmsPlatform);
        $esgOrders = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("esg_quotation_courier_id", $courierIdList)
            ->where("waybill_status", 0)
            ->whereHas('sellingPlatform', function ($query) {
                $query->whereNotIn('merchant_id', $this->excludeMerchant);
            })
            ->with("iwmsLgsOrderStatusLog")
            ->get();
        return $esgOrders;
    }

    private function getEsgLgsOrdersByOrderNo($esgOrderNoList)
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

    public function saveLgsOrderDocument($esgOrder)
    {
        $doucmentTypeArr = [
            "invoice" => "invoice",
            //"manifest" => "carrierManifest",
            "AWB" => "shippingLabel"
            ];
        $storeName = $esgOrder->platformMarketOrder->platform;
        $orderItemIds = array();
        foreach($esgOrder->soItem as $soItem){
            $itemIds = array_filter(explode("||",$soItem->ext_item_cd));
            foreach($itemIds as $itemId){
                $orderItemIds[] = $itemId;
            }
        }
        $orderItemId = json_encode($orderItemIds);
        foreach($doucmentTypeArr as $key => $doucmentType ){
            $fileHtml = $this->getApiLazadaService()->getDocument($storeName, $orderItemId, $doucmentType);
            if($fileHtml){
                if($doucmentType == "invoice"){
                    $fileHtml = preg_replace(array('/class="logo"/'), array('class="page"'), $fileHtml,2);
                }
                $this->saveWaybillToPickListFolder($esgOrder, $doucmentType, $fileHtml);
            }else{
                return null;
            }
        }
        return true;
    }

    private function saveWaybillToPickListFolder($esgOrder, $folderName, $label)
    {
        if(!empty($esgOrder) && !empty($label)){
            //$pickListNo = $this->getSoAllocatedPickListNo($soNo);
            $filePath = getLgsOrderPickListFilePath($esgOrder->pick_list_no, $folderName);
            if($folderName == "AWB"){
                $file = $filePath.$esgOrder->so_no.'_awb.pdf';
            }else if($folderName == "invoice"){
                $file = $filePath.$esgOrder->so_no.'_invoice.pdf';
            }
            file_put_contents($file, $label);
        }
    }

    public function getLgsOrderPickListFilePath($pickListNo, $folderName)
    {
        $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo."/".$folderName."/";
        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true);
        }
        return $filePath;
    }

    public function getApiLazadaService()
    {
        if ($this->apiLazadaService == null) {
            $this->apiLazadaService = App::make("App\Services\ApiLazadaService");
        }
        return $this->apiLazadaService;
    }

}