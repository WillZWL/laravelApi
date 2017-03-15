<?php

namespace App\Services\IwmsApi\Order;

use App\Models\Product;
use App\Models\IwmsProductLog;

class IwmsRemoveProductService
{
    private $warehouseIds = null;
    private $message = null;
    private $wmsPlatform = null;

    use \App\Services\IwmsApi\IwmsBaseService;
    
    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getProductRemoveRequest($esgOrderSoList)
    {
        $cancelProductObj = null; $merchantId = "ESG";
        $iwmsProductLogs = $this->getIwmsProductLogs($esgProductSkuList);
        if(!$iwmsProductLogs->isEmpty()){
            $batchRequest = $this->getNewBatchId("CANCEL_DELIVERY",$this->wmsPlatform,$merchantId);
            foreach ($iwmsProductLogs as $iwmsProductLog) {
                $productObj = array(
                    "wms_platform" => $iwmsProductLog->wms_platform,
                    "merchant_id" => $iwmsProductLog->merchant_id,
                    "sub_merchant_id" => $iwmsProductLog->sub_merchant_id,
                    "sku" => $iwmsProductLog->sku,
                );
                $cancelProductObj[] = $productObj;
            }
            $batchRequest->request_log = json_encode($cancelProductObj);
            $batchRequest->save();
            return $batchRequest;
        }
    }

    public function responseMsgRemoveAction($batchObject, $postContent)
    {
        $postMessage = json_decode($postContent); 
        $msg = null;
        if(isset($postMessage->request_id) && !empty($batchObject)){
            $batchObject->iwms_request_id = $postMessage->request_id;
            $batchObject->status = "R";
            $batchObject->response_log = $postContent;
            $batchObject->save();
            foreach ($postMessage->response_log as $responseLog) {
                if(isset($responseLog->error)){
                    $msg .= "Sku: ".$responseLog->sku."
                    error:".$responseLog->error."\r\n";
                }
            }
            if($msg){
                $subject = "Remove OMS Product Failed,Please Check Error";
                $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                $alertEmail = "privatelabel-log@eservicesgroup.com";
                mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            }
        }
    }

    public function getIwmsProductLogs($esgProductSkuList)
    {
       return IwmsProductLog::whereIn('sku', $esgProductSkuList)
                    ->where("status", 1)
                    ->get();
    }

}