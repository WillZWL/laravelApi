<?php

namespace App\Services;
use App\Models\BatchRequest;
use App\Models\IwmsFeedRequest;

class BatchRequestService
{
    public function __construct()
    {
    }

    public function getNewBatch($name, $wmsPlatform, $merchantId, $requestLog = null)
    {
        $batch = new BatchRequest();
        $batch->name = $name;
        $batch->wms_platform = $wmsPlatform;
        $batch->merchant_id = $merchantId;
        $batch->status = 'N';
        if ($requestLog)
            $batch->request_log = $requestLog;
        $batch->save();
        return $batch;
    }

    public function saveBatchIwmsResponseData($batchObject,$responseJson)
    {
        $responseData = json_decode($responseJson);
        $batchObject->response_log = $responseJson;
        if (isset($responseData->request_id)) {
            $batchObject->iwms_request_id = $responseData->request_id;
            $object = [
                'merchant_id' => $batchObject->merchant_id,
                'wms_platform' => $batchObject->wms_platform,
                'batch_request_id' => $batchObject->id,
                'iwms_request_id' => $responseData->request_id,
            ];
            $iwmsFeedRequest = IwmsFeedRequest::updateOrCreate(
                [
                    'batch_request_id' => $batchObject->id,
                    'iwms_request_id' => $responseData->request_id
                ],
                $object
            );
        } else {
            $batchObject->status = "F";
            $this->sendAlertEmail($responseData);
        }
        $batchObject->save();
    }

    public function sendAlertEmail($responseData)
    {
        $subject = "Wms Create Deliveyr Order Vaild Failed ";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $message = null;
        foreach ($responseData as $esgSoNo => $orderError) {
            $message .= "Order no: ".$esgSoNo." Error: \r\n";
            foreach ($orderError as $key => $error) {
                if ($key == "itemError") {
                    foreach ($error as $sku => $itemError) {
                        $message .= "Item Sku :".$sku." Error: \r\n";
                        foreach ($itemError as $k => $v) {
                            $message .= $k.": ".$v."\r\n";
                        }
                    }
                } else {
                   $message .= $key.": ".$error."\r\n";
                }
            }
            $message .= "\r\n";
        }
        if($message){
            mail("{$alertEmail}, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
        }
    }
}
