<?php

namespace App\Services\IwmsApi\Order;

use App\Models\IwmsAllocationPlanLog;

class IwmsAllocationPlanService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct()
    {

    }

    public function saveAllocationPlanData($warehouseId, $soIds = [], $responseJson)
    {
        $responseData = json_decode($responseJson);
        if (isset($responseData->request_id) && $soIds) {
            foreach ($soIds as $key => $soNo) {
                $object = IwmsAllocationPlanLog::whereSoNo($soNo)->first();
                if (! $object) {
                    $newObject = new IwmsAllocationPlanLog();
                    $newObject->so_no = $soNo;
                    $newObject->warehouse_id = $warehouseId;
                    $newObject->iwms_request_id = $responseData->request_id;
                    $newObject->save();
                }
            }
        }
    }
}
