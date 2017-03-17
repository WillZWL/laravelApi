<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsLgsOrderStatusLog;
use App\Models\InvMovement;
use App\Models\SoShipment;

use App;
use PDF;

class IwmsLgsOrderService extends IwmsBaseOrderService
{
    private $excludeMerchant = array("PREPD");
    private $courierList = array();
    private $apiLazadaService = null;
    protected $merchantId = null;

    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform, $merchantId)
    {
        $this->wmsPlatform = $wmsPlatform;
        $this->merchantId = $merchantId;
    }

    public function setLgsOrderStatus($warehouseToIwms)
    {
        $esgOrders = $this->getReadyToShipLgsOrder($warehouseToIwms, 20);
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
            if(empty($esgOrder->iwmsLgsOrderStatusLog)){
                $error[] = $esgOrder->so_no;
                $subject = "LGS order set Order status and get trackingNo failed.";
            }else if(!empty($esgOrder->iwmsLgsOrderStatusLog) && $esgOrder->iwmsLgsOrderStatusLog->status != 1){
                $error[] = $esgOrder->so_no;
                $subject = "LGS order set Order Status Failed.";
            }
        }
        if( isset($error) && $error ){
            $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
            $msg = "LGS Order ID: \r\n";
            foreach ($error as $key => $soNo) {
               $msg .= $soNo." .\r\n";
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
                    $esgOrder->save();
                    /*if(in_array($iwmsLgsOrderStatusLog->iwmsLgsOrderStatusLog)){
                        $this->updateEsgLgsOrderStatusToDispatch($esgOrder);
                    }*/
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
        $object['iwms_platform'] = $this->wmsPlatform;
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
        $courierIdList = $this->getLgsOrderMerchantCourierIdList($this->wmsPlatform, $this->merchantId);
        $esgOrderQuery = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("esg_quotation_courier_id", $courierIdList)
            ->where("waybill_status", 0)
            ->whereNotNull('pick_list_no')
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
        $courierIdList = $this->getLgsOrderMerchantCourierIdList($this->wmsPlatform, $this->merchantId);
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
        if(!empty($orderItemIds)){
            foreach($doucmentTypeArr as $folderName => $doucmentType ){
                $fileHtml = $this->getApiLazadaService()->getDocument($storeName, $orderItemIds, $doucmentType);
                if($fileHtml){
                    if($doucmentType == "invoice"){
                        $fileHtml = preg_replace(array('/class="logo"/'), array('class="page"'), $fileHtml,2);
                    }
                    $this->saveWaybillToPickListFolder($esgOrder, $folderName, $fileHtml);
                }else{
                    return false;
                }
            }
            return true;
        } 
    }

    private function saveWaybillToPickListFolder($esgOrder, $folderName, $document)
    {
        if(!empty($esgOrder) && !empty($document)){
            $filePath = $this->getLgsOrderPickListFilePath($esgOrder, $folderName);
            if($folderName == "AWB"){
                $file = $filePath.$esgOrder->so_no.'_awb.pdf';
                $this->generateAwbLabel($document, $file);
            }else if($folderName == "invoice"){
                $file = $filePath.$esgOrder->so_no.'_invoice.pdf';
                $this->generateInvoiceLabel($document, $file);
            }
        }
    }

    private function generateInvoiceLabel($document, $file)
    {
        PDF::loadHTML($document)->setPaper('a4')
            ->setOption('margin-bottom', 0)
            ->setOption("encoding","UTF-8")
            ->save($file, true);
    }

    private function generateAwbLabel($document, $file)
    {
        PDF::loadHTML($document)->setOption('page-width', '100')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 2)
            ->setOption('margin-top', 2)
            ->setOption('margin-bottom', 2)
            ->setOption('page-height', '150.40')
            ->setOption("encoding","UTF-8")
            ->save($file);
    }

    public function getLgsOrderPickListFilePath($esgOrder, $folderName)
    {
        if(!empty($esgOrder)){
            $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$esgOrder->pick_list_no."/".$folderName."/".$esgOrder->courierInfo->courier_name."/";
            if (!file_exists($filePath)) {
                mkdir($filePath, 0755, true);
            }
            return $filePath;
        }
    }

    public function getApiLazadaService()
    {
        if ($this->apiLazadaService == null) {
            $this->apiLazadaService = App::make("App\Services\ApiLazadaService");
        }
        return $this->apiLazadaService;
    }

    private function updateEsgLgsOrderStatusToDispatch($esgOrder)
    {
        $soShipment = $this->createEsgSoShipment($esgOrder);
        if(!empty($soShipment)){
            foreach ($esgOrder->soAllocate as $soAllocate) { 
                if($soAllocate->status != 1){
                    continue;
                }
                $invMovement = InvMovement::where("ship_ref", $soAllocate->id)
                    ->where("status", "AL")
                    ->first();
                if(!empty($invMovement)){
                    $invMovement->ship_ref = $soShipment->sh_no;
                    $invMovement->status = "OT";
                    $invMovement->save();
                    $soAllocate->status = 2;
                    $soAllocate->sh_no = $soShipment->sh_no;
                    $soAllocate->save();
                }
            }
        }
    }

    public function createEsgSoShipment($esgOrder)
    {
        $soShipment = SoShipment::where("sh_no", $esgOrder->so_no."-01")->first();
        if(!empty($soShipment)){
            return null;
        }else{
            $object['sh_no'] = $esgOrder->so_no."-01";
            $object['courier_id'] = $esgOrder->esg_quotation_courier_id;
            $object['status'] = 1;
            $soShipment = SoShipment::updateOrCreate(['sh_no' => $object['sh_no']],$object);
            return $soShipment;
        }
    }

}