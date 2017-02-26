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
        foreach ($responseMessage as $responseOrder) {
            $allocatedOrder = $this->updateIwmsAllocatedOrder($responseOrder);
            if(!empty($allocatedOrder)){
                $this->updateIwmsAllocatedOrderItems($responseOrder->items, $allocatedOrder);
            }
        }
        // $this->updateEsgOrderAllocatedSataus($responseMessage, "2");
        // $this->sendMsgAllocatedOrderReport($responseMessage);
    }

    private function createIwmsAllocatedOrder($responseOrder, $batchId)
    {
       $iwmsAllocatedOrder = new IwmsAllocatedOrder();
       $iwmsAllocatedOrder->batch_id = $batchId;
       $iwmsAllocatedOrder->wms_platform = $responseOrder->wms_platform;
       $iwmsAllocatedOrder->merchant_id = $responseOrder->merchant_id;
       $iwmsAllocatedOrder->sub_merchant_id = $responseOrder->sub_merchant_id;
       $iwmsAllocatedOrder->reference_no = $responseOrder->reference_no;
       $iwmsAllocatedOrder->picklist_no = $responseOrder->picklist_no;
       $iwmsAllocatedOrder->status = 1;
       $iwmsAllocatedOrder->save();
       return $iwmsAllocatedOrder;
    }

    private function createIwmsAllocatedOrderItems($responseOrderItems, $allocatedOrder)
    {
        foreach ($responseOrderItems as $responseOrderItem) {
            $iwmsAllocatedOrderItem = new IwmsAllocatedOrderItem();
            $iwmsAllocatedOrderItem->iwms_allocated_order_id = $allocatedOrder->id;
            $iwmsAllocatedOrderItem->reference_no = $allocatedOrder->reference_no;
            $iwmsAllocatedOrderItem->line_no = $responseOrderItem->line_no;
            $iwmsAllocatedOrderItem->sku = $responseOrderItem->sku;
            $iwmsAllocatedOrderItem->quantity = $responseOrderItem->quantity;
            $iwmsAllocatedOrderItem->allocated_qty = $responseOrderItem->allocated_qty;
            $iwmsAllocatedOrderItem->save();
        }
    }

    public function updateEsgOrderAllocatedSataus()
    {
        
    }

    public function sendMsgAllocatedOrderReport($successResponseMessage)
    {
        
    }

}