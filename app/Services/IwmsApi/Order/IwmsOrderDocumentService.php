<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;

class IwmsOrderDocumentService
{
    private $warehouseIds = null;
    private $message = null;
    private $wmsPlatform = null;

    use \App\Services\IwmsApi\IwmsBaseService;
    
    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getOrderDocumentRequest($esgOrderSoList)
    {
        $esgAllocateOrder = null; $merchantId = "ESG";
        $iwmsDeliveryOrderLogs = $this->getIwmsDeliveryEsgOrderLogs($esgOrderSoList);
        if(!$iwmsDeliveryOrderLogs->isEmpty()){
            $batchRequest = $this->getNewBatchId("ORDER_DOCUMENT",$this->wmsPlatform,$merchantId);
            foreach ($iwmsDeliveryOrderLogs as $iwmsDeliveryOrderLog) {
                $deliveryOrderObj = array(
                    "wms_platform" => $iwmsDeliveryOrderLog->wms_platform,
                    "merchant_id" => $iwmsDeliveryOrderLog->merchant_id,
                    "sub_merchant_id" => $iwmsDeliveryOrderLog->sub_merchant_id,
                    "reference_no" => $iwmsDeliveryOrderLog->reference_no,
                    "order_code" => $iwmsDeliveryOrderLog->wms_order_code,
                );
                $cancelDeliveryOrderObj[] = $deliveryOrderObj;
            }
            $batchRequest->request_log = json_encode($cancelDeliveryOrderObj);
            $batchRequest->save();
            return $batchRequest;
        }
    }

    public function downloadDocument($batchObject, $postContent)
    {
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix()."label/";
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/";
        $postMessage = json_decode($postContent); 
        $msg = null;
        if(isset($postMessage->request_id) && !empty($batchObject)){
            foreach ($postMessage->response_log as $responseLog) {
                if(isset($responseLog->error)){
                    $msg .= "Order ID:".$responseLog->merchant_order_id."
                    error:".$responseLog->error."\r\n";
                }else{
                    $document = $this->getDocumentSaveToDirectory($document, $pdfFilePath);
                }
            }
            if($msg){
                $subject = "Cancel OMS Delivery Order Failed,Please Check Error";
                $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                $alertEmail = "privatelabel-log@eservicesgroup.com";
                mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            }
        }
    }

    private function getDocumentSaveToDirectory($document, $pdfFilePath)
    {
        $documentPdf = array();
        $fileDate = date("h-i-s");
        if (!file_exists($pdfFilePath)) {
            mkdir($pdfFilePath, 0755, true);
        }
        if($documentFile){
            $file = $pdfFilePath.$documentType.$fileDate.'.pdf';
            PDF::loadHTML($document)->setOption("encoding","UTF-8")->save($file);
            return $document;
        }
    }

    private function getIwmsDeliveryEsgOrderLogs($esgOrderSoList)
    {
       return IwmsDeliveryOrderLog::whereIn('reference_no', $esgOrderSoList)
                    ->where("status", 1)
                    ->get();
    }

}