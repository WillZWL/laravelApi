<?php

namespace App\Services\IwmsApi\Order;

use App\Models\IwmsAllocationPlanLog;

class IwmsAllocationPlanService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getAllocationPlanRequest($requestData = [])
    {
        $request = [];
        if (isset($requestData['warehouse']) && $requestData['warehouse']) {
            $merchantId = "ESG";
            $batchRequest = $this->getNewBatchId("ALLOCATION_PLAN_REQUEST", $this->wmsPlatform, $merchantId);
            if ($batchRequest) {
                $requestBody = [
                    "merchant_id" => $merchantId,
                    "warehouse_id" => $requestData['warehouse'],
                    "plan_date" => isset($requestData['plan_date']) ? $requestData['plan_date'] : date("Y-m-d"),
                ];
                $batchRequest->request_log = json_encode($requestBody);
                $batchRequest->save();
                $request['requestBody'] = $requestBody;
                $request['batchRequest'] = $batchRequest;
            }
        }
        return $request;
    }

    // public function saveAllocationPlanData($warehouseId, $soIds = [], $responseJson)
    // {
    //     $responseData = json_decode($responseJson);
    //     if (isset($responseData->request_id) && $soIds) {
    //         foreach ($soIds as $key => $soNo) {
    //             $object = IwmsAllocationPlanLog::whereSoNo($soNo)->first();
    //             if (! $object) {
    //                 $newObject = new IwmsAllocationPlanLog();
    //                 $newObject->so_no = $soNo;
    //                 $newObject->warehouse_id = $warehouseId;
    //                 $newObject->iwms_request_id = $responseData->request_id;
    //                 $newObject->save();
    //             }
    //         }
    //     }
    // }
}
