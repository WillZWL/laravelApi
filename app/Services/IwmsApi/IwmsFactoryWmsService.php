<?php

namespace App\Services\IwmsApi;

use App\Models\So;
use App\Models\IwmsFeedRequest;

class IwmsFactoryWmsService extends IwmsCoreService
{
    use IwmsCreateDeliveryOrderService;
    protected $wmsPlatform;
    protected $merchantId = "ESG";

    public function __construct($wmsPlatform = "4px",$debug = 0)
    {
        $this->$wmsPlatform = $wmsPlatform;
        parent::__construct($wmsPlatform,$debug);
    }

    public function createDeliveryOrder()
    {
        $warehouseToIwms = $this->getWarehouseId($this->wmsPlatform);
        $request = $this->getDeliveryCreationRequest($warehouseToIwms);
        $responseData = $this->curlIwmsApi('create-delivery-order', $request["requestBody"]);
        $this->saveBatchIwmsResponseData($request["batchRequest"],$responseData);
    }

    public function cancelDeliveryOrder($iwmsOrderCode)
    {
        $requestBody = array(
            "order_code" => $iwmsOrderCode,
            );
        $responseData = $this->curlIwmsApi('cancel-delivery-order', $requestBody);
    }

    public function sendCreateDeliveryOrderReport()
    {
        $iwmsRequestIds = IwmsFeedRequest::where("status","0")->pluck("iwms_request_id")->all();
        $cellData = $this->getCreateDeliveryOrderReport($iwmsRequestIds);
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."orderCreate/";
        $fileName = "deliveryOrderDetail-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "WMS Delivery Order Create Report!";
                $attachment = array("path" => $orderPath,"file_name"=>$fileName.".xlsx");
                $this->sendAttachmentMail('fiona@etradegroup.net',$subject,$attachment);
            }
        }
    }

    public function getCreateDeliveryOrderReport($iwmsRequestIds)
    {
        $requestBody = array("request_id" => $iwmsRequestIds);
        $responseJson = $this->curlIwmsApi('get-delivery-order-report', $requestBody);
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

    public function queryDeliveryOrderStatus($iwmsOrderCode)
    {
        $requestBody = array(
            "order_code" => $iwmsOrderCode,
            );
        $responseData = $this->curlIwmsApi('query-delivery-order', $requestBody);
        return $responseData;
    }

    public function queryDeliveryOrderByWarehouse()
    {
        $warehouseId = "4PXDG_PL";
        $iwmsWarehouseCode = $this->getIwmsWarehouseCode($warehouseId,$this->merchantId);
        $requestBody = array(
            "iwms_warehouse_code" => $iwmsWarehouseCode,
            );
        $responseData = $this->curlIwmsApi('query-delivery-order', $requestBody);
        print_r($responseData);exit();
        return $responseData;
    }

    public function queryProduct()
    {
        $requestBody = array(
            "sku" => "21695-AA-WH"
            );
        $this->curlIwmsApi('query-product', $requestBody);
    }

    public function getWarehouseToIwms($wmsPlatform)
    {
        $warehouseIdArr = array(
            '4px' => array("4PXDG_PL"),
        );
        return $warehouseIdArr[$wmsPlatform];
    }


}