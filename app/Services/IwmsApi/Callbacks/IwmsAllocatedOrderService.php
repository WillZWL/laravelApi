<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\ProductAssemblyMapping;
use App\Models\Inventory;
use App\Models\SoAllocate;
use App\Models\InvMovement;
use App\Models\SoItemDetail;

class IwmsAllocatedOrderService extends IwmsBaseCallbackService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    private $notAllocateOrder = [];
    private $allocateFailedOrder = [];
    private $beforeAllocatedOrder = [];
    private $allocatedOrder = [];
    private $exceptionInfo = [];
    private $notAllocateInfo = [];
    protected $prodAssembMapping = [];
    private $userName = "system_allocated";
    protected $newWarehouseInventory = null;
    protected $newSoAllocation = null;
    protected $newInvMovement = null;
    private $orderStatus = [
        '0' => 'Inactive',
        '1' => 'New',
        '2' => 'Paid',
        '3' => 'Fulfilment AKA Credit Checked',
        '4' => 'Partial Allocated',
        '5' => 'Full Allocated',
        '6' => 'Shipped',
    ];

    public function __construct()
    {
        $this->setProductAssemblyMapping();
    }

    public function allocationPlan($postMessage)
    {
        $wmsOrders = $this->getWmsAllocationPlanData($postMessage);
        $readyOrders = $this->readyAllocateOrders($wmsOrders);
        $this->processAllocationPlan($readyOrders, $wmsOrders);
        $this->unsetFailedByAllocatedOrder();
        $allocationResults = $this->allocationResults();
        return $allocationResults;
    }

    public function allocationResults()
    {
        return [
            'notAllocateOrder'=> $this->notAllocateOrder,
            'allocateFailedOrder' => $this->allocateFailedOrder,
            'beforeAllocatedOrder' => $this->beforeAllocatedOrder,
            'allocatedOrder' => $this->allocatedOrder,
            'exceptionInfo' => $this->exceptionInfo,
            'notAllocateInfo'=> $this->notAllocateInfo,
        ];
    }

    public function getWmsAllocationPlanData($postMessage)
    {
        if (isset($postMessage->responseMessage)) {
            $responseMessage = $postMessage->responseMessage;
            if (isset($responseMessage->result)
                && $responseMessage->result == 'success'
                && isset($responseMessage->orders)
                && $responseMessage->orders
            ) {
                $wmsOrders = [];
                $wh = [];
                foreach ($responseMessage->orders as $order) {
                    $orderItems = $order->items;
                    $newItem = [];
                    foreach ($orderItems as $item) {
                        $newItem[$item->line_no] = [
                            'line_no' => $item->line_no,
                            'sku' => $item->sku,
                            'allocated_qty' => $item->allocated_qty,
                            'quantity' => $item->quantity,
                        ];
                    }
                    $iwmsWh = $order->iwms_warehouse_code;
                    $merchantId = $order->merchant_id;
                    if (! isset($wh[$iwmsWh][$merchantId])) {
                        $wh[$iwmsWh][$merchantId] = $this->getMerchantWarehouseCode($iwmsWh, $merchantId);
                    }
                    if (isset($wh[$iwmsWh][$merchantId])) {
                        $wmsOrders[$order->reference_no] = [
                            'allocation_batch_id' => $order->allocation_batch_id,
                            'so_no' => $order->reference_no,
                            'warehouse_id' => $wh[$iwmsWh][$merchantId],
                            'wms_picklist_no' => $order->wms_picklist_no,
                            'items' => $newItem,
                        ];
                    } else {
                        $this->notAllocateOrder[] = $order->reference_no;
                        $this->notAllocateInfo[] = "Order number[$soNo] iwms_warehouse[$iwmsWh] with merchant ID[$merchantId] not found available merchant warehouse ID";

                    }

                }
                return $wmsOrders;
            }
        }
        return false;
    }

    public function readyAllocateOrders($wmsOrders = [])
    {
        if ($wmsOrders) {
            $readyOrders = [];
            $pendingOrders = [];
            $soNoCollection = array_keys($wmsOrders);
            if ($soNoCollection) {
                $orders = So::AllocateOrders($soNoCollection);
                if (! $orders->isEmpty()) {
                    foreach ($orders as $order) {
                        $pendingOrders[$order->so_no] = $order;
                    }
                }
            }
            foreach ($soNoCollection as $soNo) {
                if (isset($pendingOrders[$soNo])) {
                    $readyOrders[$soNo] = $pendingOrders[$soNo];
                } else {
                    $this->checkSkippedAllocationPlan($soNo);
                }
            }
            return $readyOrders;
        }
        return false;
    }

    public function checkSkippedAllocationPlan($soNo)
    {
        $so = So::where('so_no', $soNo)->first();
        $holdText = '';
        if ($so->status == 5 || $so->status == 6) {
            $this->beforeAllocatedOrder[] = $soNo;
        } else {
            $this->notAllocateOrder[] = $soNo;
            $holdText = ", Refund Status: ". $so->refund_status
                    .", Hold Status". $so->hold_status
                    .", Merchant Hold Status: ". $so->merchant_hold_status
                    .", Billing Status: ". $so->billing_status
                    .", Prepay Hold Status". $so->prepay_hold_status;
        }
        $this->notAllocateInfo[] = "Order number[$soNo] not do allocation plan, Order Status: ". $so->status. " - ". $this->orderStatus[$so->status] . $holdText;
    }

    public function processAllocationPlan($orders, $wmsOrders)
    {
        if ($orders) {
            foreach ($orders as $soNo => $order) {
                \DB::connection('mysql_esg')->beginTransaction();
                try {
                    $wmsOrder = $wmsOrders[$soNo];
                    $this->allocation($order, $wmsOrder);
                    $order->pick_list_no = $wmsOrder['wms_picklist_no'];
                    $order->status = 5;
                    $order->modify_by = $this->userName;
                    $order->save();
                    $this->allocatedOrder[] = $soNo;
                    \DB::connection('mysql_esg')->commit();
                } catch (\Exception $e) {
                    \DB::connection('mysql_esg')->rollBack();
                    $this->allocateFailedOrder[] = $soNo;
                    $this->exceptionInfo[] = $e->getMessage(). ", Line: ".$e->getLine();
                }
            }
        }
    }

    public function unsetFailedByAllocatedOrder()
    {
        if ($this->allocateFailedOrder) {
            $allocatedOrders = array_flip($this->allocatedOrder);
            $failedOrders = $this->allocateFailedOrder;
            foreach ($failedOrders as $key => $soNo) {
                if (isset($allocatedOrders[$soNo])) {
                    unset($allocatedOrders[$soNo]);
                }
            }
            $this->allocatedOrder =  array_values(array_flip($allocatedOrders));
        }
    }

    public function allocation($order, $wmsOrder)
    {
        $soNo = $order->so_no;
        $soItemDetail = $order->soItemDetail;
        $warehouseId = $wmsOrder['warehouse_id'];
        foreach ($soItemDetail as $soid) {
            $itemSku = $soid->item_sku;
            $lineNo = $soid->line_no;
            $qty = $soid->qty;
            $outstandingQty = $soid->outstanding_qty;

            if (isset($this->prodAssembMapping[$itemSku])) {
                $prodAssemb = $this->prodAssembMapping[$itemSku];
                $itemSku = $prodAssemb['sku'];
                $qty = $soid->qty * $prodAssemb['replace_qty'];
                $outstandingQty = $soid->outstanding_qty * $prodAssemb['replace_qty'];
            }

            $wmsAllocatedQty = $wmsOrder['items'][$lineNo]['allocated_qty'];
            $wmsSku = $wmsOrder['items'][$lineNo]['sku'];

            if ($qty <> $outstandingQty || $outstandingQty <> $wmsAllocatedQty) {
                throw new \Exception("This order[{$soNo}] line_no[$lineNo] qty[$qty] with outstanding_qty[$outstandingQty] and wms allocated qty[$wmsAllocatedQty] is not equal");
            }

            if ($itemSku <> $wmsSku) {
                throw new \Exception("This order[{$soNo}] line_no[$lineNo] item sku[$itemSku] with wms sku[$wmsSku] is diff");
            }

            $soAllocation = SoAllocate::whereSoNo($soNo)
                ->whereLineNo($lineNo)
                ->whereItemSku($itemSku)
                ->get();
            if ($soAllocation->isEmpty()) {
                $newSoAllocation = $this->getNewSoAllocation();
                $soal = clone $newSoAllocation;
                $soal->so_no = $soNo;
                $soal->line_no = $lineNo;
                $soal->item_sku = $itemSku;
                $soal->qty = $outstandingQty;
                $soal->warehouse_id = $warehouseId;
                $soal->status = 1;
                $soal->create_by = $this->userName;
                $soal->modify_by = $this->userName;
                $soal->save();

                $this->warehouseInventory($itemSku, $warehouseId);

                $invMovement = InvMovement::whereShipRef($soal->id)
                    ->get();
                if ($invMovement->isEmpty()) {
                    $newInvMovement = $this->getNewInvMovement();
                    $invMv = clone $newInvMovement;
                    $invMv->ship_ref = $soal->id;
                    $invMv->sku = $soal->item_sku;
                    $invMv->type = 'C';
                    $invMv->qty = $soal->qty;
                    $invMv->from_location = $soal->warehouse_id;
                    $invMv->status = "AL";
                    $invMv->create_by = $this->userName;
                    $invMv->modify_by = $this->userName;
                    $invMv->save();
                } else {
                    throw new \Exception("This order[{$soNo}] line_no[$lineNo] ship_ref[$ship_ref] inv_movement already exist record");
                }

                $this->updateAllocatedSoidOutstandingQty($soNo, $lineNo, $soid->item_sku);
            } else {
                throw new \Exception("Order[{$soNo}] line_no[$lineNo] so_allocate[". $soAllocation->id ."] already has allocated record");
            }
        }

        $this->validateAllocatedOutstandingQty($soNo);

        $this->validateOrderAllocated($soItemDetail->count(), $soNo);
    }

    public function warehouseInventory($itemSku, $warehouseId)
    {
        $whInv =Inventory::warehouseInventory($itemSku, $warehouseId);
        if ($whInv->count() == 0) {
            throw new \Exception("Item sku[$itemSku] in warehouse[$warehouseId] never no inventory record");
            // $newWarehouseInventory = $this->getNewWarehouseInventory();
            // $inventory = clone $newWarehouseInventory;
            // $inventory->prod_sku = $itemSku;
            // $inventory->warehouse_id = $warehouseId;
            // $inventory->inventory = 0;
            // $inventory->git = 0;
            // $inventory->create_by = $this->userName;
            // $inventory->modify_by = $this->userName;
            // $inventory->save();
        }
    }

    public function updateAllocatedSoidOutstandingQty($soNo, $lineNo, $sku)
    {
        SoItemDetail::whereSoNo($soNo)
            ->whereLineNo($lineNo)
            ->whereItemSku($sku)
            ->update(['outstanding_qty' => 0, 'modify_by' => $this->userName]);
    }

    public function validateAllocatedOutstandingQty($soNo)
    {
        $remainSoid = SoItemDetail::whereSoNo($soNo)
            ->where('outstanding_qty', '>', '0')
            ->get();
        if ($remainSoid->count() > 0) {
            throw new \Exception("Order[{$soNo}] ". $remainSoid->count() ." item detail remain outstanding_qty not fully allocated ");
        }
    }

    public function validateOrderAllocated($soidCount, $soNo)
    {
        $soAllocated = SoAllocate::whereSoNo($soNo)
            ->get();
        if ($soidCount <> $soAllocated->count()) {
            throw new \Exception("Order[{$soNo}] item detail count[". $soItemDetail->count() ."] <>  so allocated count[". $soAllocated->count() ."]");
        }
    }

    public function setProductAssemblyMapping()
    {
        $prodAssembData = [];
        $prodAssMaps = ProductAssemblyMapping::whereStatus('1')
            ->whereIsReplaceMainSku('1')
            ->get();
        if (! $prodAssMaps->isEmpty()) {
            foreach ($prodAssMaps as $prodAssMap) {
                $prodAssembData[$prodAssMap->main_sku] = [
                    'sku' => $prodAssMap->sku,
                    'replace_qty' => $prodAssMap->replace_qty,
                ];
            }
        }
        $this->prodAssembMapping = $prodAssembData;
    }

    public function getNewWarehouseInventory()
    {
        if ($this->newWarehouseInventory === null) {
            $this->newWarehouseInventory = new Inventory();
        }
        return $this->newWarehouseInventory;
    }

    public function getNewSoAllocation()
    {
        if ($this->newSoAllocation === null) {
            $this->newSoAllocation = new SoAllocate();
        }
        return $this->newSoAllocation;
    }

    public function getNewInvMovement()
    {
        if ($this->newInvMovement === null) {
            $this->newInvMovement = new InvMovement();
        }
        return $this->newInvMovement;
    }

}