<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\IwmsAllocatedOrder;
use App\Models\IwmsAllocatedOrderItem;

class IwmsAllocatedOrderService extends IwmsBaseCallbackService
{
    public function __construct()
    {
    }

    public function createAllocatedOrder($postMessage)
    {
        $batchObject = $this->saveFeedRequestAndGetBatchObject($postMessage);
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    $this->updateIwmCallbackOrderSuccess($responseMessage, $batchObject->id);
                }else if ($key === "failed"){
                    $this->updateIwmCallbackOrderFaild($responseMessage, $batchObject->id);
                }
            }
        }
    }

    public function cancelAllocatedOrder($postMessage)
    {
        
    }   

    public function updateIwmCallbackOrderSuccess($responseMessage, $batchId)
    {
        $allocatedOrder = $this->createIwmsAllocatedOrder($responseMessage, $batchId);
        if(!empty($allocatedOrder)){
            $this->createIwmsAllocatedOrderItems($responseMessage, $allocatedOrder);
        }
        // $this->updateEsgOrderAllocatedSataus($responseMessage, "2");
        // $this->sendMsgAllocatedOrderReport($responseMessage);
    }   

    private function updateIwmCallbackOrderFaild($responseMessage, $batchId)
    {
        $allocatedOrder = $this->updateIwmsAllocatedOrder($responseMessage);
        if(!empty($allocatedOrder)){
            $this->updateIwmsAllocatedOrderItems($responseMessage, $allocatedOrder);
        }
        // $this->updateEsgOrderAllocatedSataus($responseMessage, "2");
        // $this->sendMsgAllocatedOrderReport($responseMessage);
    }

    private function createIwmsAllocatedOrder($responseMessage, $batchId)
    {
        
    }

    private function createIwmsAllocatedOrderItems($responseMessage, $allocatedOrder)
    {
        
    }

    public function updateEsgOrderAllocatedSataus()
    {
        
    }

    public function sendMsgAllocatedOrderReport($successResponseMessage)
    {
        
    }

}