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
        $batch->wms_Platform = $wmsPlatform;
        $batch->merchant_id = $merchantId;
        $batch->status = 'N';
        if ($requestLog)
            $batch->request_log = $requestLog;
        $batch->save();
        return $batch;
    }

    public function saveBatchIwmsResponseData($batchObject,$responseJson)
    {
        $batchObject->response_log = $responseJson;
        $responseData = json_decode($responseJson);
        $batchObject->iwms_request_id = $responseData->request_id;
        $batchObject->save();
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
    }
}
