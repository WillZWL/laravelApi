<?php

namespace App\Services;

use App\Models\So;
use App\Models\ProductAssemblyMapping;
use App\Models\Inventory;
use App\Models\SoAllocate;
use App\Models\InvMovement;
use App\Models\SoItemDetail;

use Config;

class AllocationPlanService
{
    protected $configName = "central-link-wms";
    protected $prodAssembMapping = [];
    protected $requestData = [];
    protected $notAlloctionPlan = [];
    protected $errorMessages = [];
    protected $exceptionMessages = [];
    protected $newSoAllocation = null;
    protected $newInvMovement = null;

    public function getAllocationPlan($warehouseId="ES_HK", $requestData = [])
    {
        $this->requestData = $requestData;

        $wmsOrders = $this->getWmsAllocationPlanData();

        $readyOrderData = $this->readyAllocateOrders($wmsOrders);

        $validatePassOrders = $this->validateAllocationPlanOrders($readyOrderData, $wmsOrders, $warehouseId);

        $this->processAllocationPlan($validatePassOrders, $warehouseId);

        $this->processEmailAlert();
    }

    public function readyAllocateOrders($wmsOrders = [])
    {
        $orderItemCollection = [];
        $skus = [];
        $readyOrders = [];
        if ($wmsOrders) {
            $soNoCollection = array_keys($wmsOrders);
            if ($soNoCollection) {
                $orders = So::AllocateOrders($soNoCollection);
                if (! $orders->isEmpty()) {
                    $this->setProductAssemblyMapping();
                    foreach ($orders as $order) {
                        $readyOrders[$order->so_no] = $order;
                        $soItemDetail = $order->soItemDetail;
                        foreach ($soItemDetail as $soid) {
                            $itemSku = $soid->item_sku;
                            $outstandingQty = $soid->outstanding_qty;
                            $qty = $soid->qty;
                            if (isset($this->prodAssembMapping[$itemSku])) {
                                $prodAssemb = $this->prodAssembMapping[$itemSku];
                                $itemSku = $prodAssemb['sku'];
                                $outstandingQty = $soid->outstanding_qty * $prodAssemb['replace_qty'];
                                $qty = $soid->qty * $prodAssemb['replace_qty'];
                            }
                            $orderItemCollection[$order->so_no][$soid->line_no] = [
                                'line_no' => $soid->line_no,
                                'item_sku' => $itemSku,
                                'qty' => $qty,
                                'outstanding_qty' => $outstandingQty,
                                'status' => $soid->status,
                            ];
                            $skus[$itemSku] = $itemSku;
                        }
                    }
                }
            }
        }
        return [
            'readyOrders' => $readyOrders,
            'orderItemCollection' => $orderItemCollection,
            'skus' => $skus
        ];
    }

    public function validateAllocationPlanOrders($readyOrderData = [], $wmsOrders = [], $warehouseId)
    {
        $calculatedItems = [];
        $orderItemCollection = $readyOrderData['orderItemCollection'];
        $readyOrders = $readyOrderData['readyOrders'];
        if ($orderItemCollection) {
            $skus = $readyOrderData['skus'];
            $invQties = $this->getInventoryQuantities($skus, $warehouseId);
            foreach ($orderItemCollection as $soNo => $order) {
                if (isset($wmsOrders[$soNo])) {
                    $wmsOrder = $wmsOrders[$soNo];
                    if (count($wmsOrder) === count($order) && $order) {
                        foreach ($order as $lineNo => $item) {
                            $status = $item['status'];
                            $itemSku = $item['item_sku'];
                            $qty = $item['qty'];
                            $outstandingQty = $item['outstanding_qty'];
                            $wmsQty = $wmsOrder[$itemSku];
                            if ($status <> 0) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] status[{$status}] is not normal";
                            } else if ($qty <> $outstandingQty) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] qty[{$qty}] with outstanding_qty[{$outstandingQty}] is not equal";
                            } else if ($wmsQty <> $outstandingQty) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] outstanding_qty[{$outstandingQty}] with wms allocation qty[$wmsQty] is not equal";
                            } else if ($outstandingQty == 0) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] outstanding_qty[{$outstandingQty}] not for 0";
                            } else if (! isset($invQties[$itemSku])) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] not enough inventory";
                            } else if ($invQties[$itemSku] - $outstandingQty < 0) {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] line_no[{$lineNo}] outstanding_qty[{$outstandingQty}] not enough inventory[{$invQties[$itemSku]}]";
                            } else if ($invQties[$itemSku] - $outstandingQty >= 0) {
                                $invQties[$itemSku] -= $outstandingQty;
                                $calculatedItems[$soNo][$lineNo] = $item;
                            } else {
                                $this->notAlloctionPlan[] = $soNo;
                                $this->errorMessages[] = "Order[{$soNo}] unknown mistake";
                            }
                        }
                    } else {
                        $this->notAlloctionPlan[] = $soNo;
                        $this->errorMessages[] = "Order[{$soNo}][".count($order) ." <-> ". count($wmsOrder)."] item with wms are inconsistent";
                    }
                }
                if (in_array($soNo, $this->notAlloctionPlan) && isset($calculatedItems[$soNo])) {
                    foreach ($calculatedItems[$soNo] as $lineNo => $item) {
                        $invQties[$item['item_sku']] += $item['outstanding_qty'];
                    }
                    unset($calculatedItems[$soNo]);
                }
            }
        }
        $passOrders = [];
        if ($calculatedItems) {
            foreach ($calculatedItems as $soNo => $item) {
                $passOrders[$soNo] = $readyOrders[$soNo];
            }
        }
        return $passOrders;
    }

    public function processAllocationPlan($orders, $warehouseId)
    {
        if ($orders) {
            foreach ($orders as $soNo => $order) {
                \DB::beginTransaction();
                \DB::connection('mysql_esg')->beginTransaction();
                try {
                    $this->allocation($order, $warehouseId);
                    $order->status = 5;
                    $order->save();
                    \DB::connection('mysql_esg')->commit();
                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::connection('mysql_esg')->rollBack();
                    \DB::rollBack();
                    $this->exceptionMessages[] = $e->getMessage(). ", Line: ".$e->getLine();
                }
            }
        }
    }

    public function allocation($order, $warehouseId)
    {
        $soNo = $order->so_no;
        $soItemDetail = $order->soItemDetail;
        foreach ($soItemDetail as $soid) {
            $itemSku = $soid->item_sku;
            $lineNo = $soid->line_no;
            $outstandingQty = $soid->outstanding_qty;
            if (isset($this->prodAssembMapping[$itemSku])) {
                $prodAssemb = $this->prodAssembMapping[$itemSku];
                $itemSku = $prodAssemb['sku'];
                $outstandingQty = $soid->outstanding_qty * $prodAssemb['replace_qty'];
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
                $soal->save();

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
                    $invMv->save();
                } else {
                    throw new \Exception("This order[{$soNo}] ship_ref[$ship_ref] inv_movement already exist record");
                }
                SoItemDetail::whereSoNo($soNo)
                    ->whereLineNo($lineNo)
                    ->whereItemSku($soid->item_sku)
                    ->update(['outstanding_qty' => 0]);
            } else {
                throw new \Exception("Order[{$soNo}] line_no[$lineNo] so_allocate[". $soAllocation->id ."] already has allocated record");
            }
        }
        $remainOutstandingQty = SoItemDetail::whereSoNo($soNo)
            ->select(\DB::raw("SUM(outstanding_qty) AS out_qty"))
            ->groupBy("so_no")
            ->first()
            ->out_qty;
        if ($remainOutstandingQty != 0) {
            throw new \Exception("Order[{$soNo}] item detail remain outstanding_qty[{$remainOutstandingQty}] not fully allocated ");
        }
    }

    public function processEmailAlert()
    {
        if (isset($this->requestData['email']) && $this->requestData['email']) {
            $toMail = $this->requestData['email'];
        } else {
            $toMail = "brave.liu@eservicesgroup.com";
        }
        $soNoContent = implode(", ", $this->notAlloctionPlan);
        $errorMessageContent = implode("\r\n", $this->errorMessages);
        $exceptionMessageContent = implode("\r\n", $this->exceptionMessages);
        $tansSoNo = $this->getOutTransferOrder($this->notAlloctionPlan);
        $message = "";
        if ($soNoContent) {
            $message .= "These orders alloction Failed, so_no: " . $soNoContent. "\r\n\r\n";
        }
        if ($tansSoNo) {
            $tansSoNoContent = implode(", ", $tansSoNo);
            $message .= "Transfer order so_no: " . $soNoContent. "\r\n\r\n";
            $toMail .= ', accelerator-ops@eservicesgroup.com';
        }
        if ($errorMessageContent) {
            $message .= "Error Message: \r\n" . $errorMessageContent. "\r\n\r\n";
        }
        if ($exceptionMessageContent) {
            $message .= "Exception Message: \r\n" . $exceptionMessageContent. "\r\n\r\n";
        }

        if ($message) {
            $subject = "[ESG] Alert, By WMS allocation plan contains some questions, Please check it";
            $header = "From: admin@eservicesgroup.com\r\n";
            $header .= "Cc: brave.liu@eservicesgroup.com\r\n";
            mail($toMail, $subject, $message, $header);
            print_r(str_replace("\r\n", "<br/>", $message));
        }
    }

    public function getOutTransferOrder($soNoCollection)
    {
        $orders = So::whereIn('so_no', $soNoCollection)
            ->where('platform_id', 'like', 'TF%')
            ->select('so_no')
            ->get();
        if (! $orders->isEmpty()) {
            $tansSoNo = [];
            foreach ($orders as $order) {
                $tansSoNo[] = $order->so_no;
            }
            return $tansSoNo;
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

    public function getInventoryQuantities($skus, $warehouseId)
    {
        $invQties = [];
        if ($skus && $warehouseId) {
            $inventories = Inventory::inventoryQuantities($skus, $warehouseId);
            if (! $inventories->isEmpty()) {
                foreach ($inventories as $inv) {
                    $invQties[$inv->prod_sku] = $inv->inventory;
                }
            }
        }
        return $invQties;
    }

    public function getWmsAllocationPlanData()
    {
        $xmlResponse = $this->curl("allocation");
        $data = $this->convert($xmlResponse);
        $wmsOrders = [];
        if ($data['result'] == "success" && $data['order']) {
            foreach ($data['order'] as $key => $order) {
                $soNo = $order['retailer_order_reference'];
                foreach ($order['skus'] as $item) {
                    $sku = $item['retailer_sku'];
                    $wmsOrders[$soNo][$sku] =  $item['quantity'];
                }
            }
        }
        return $wmsOrders;
    }

    public function curl($action)
    {
        $requestOption = [];
        $requestOption["http_errors"] = TRUE;
        $client = new \GuzzleHttp\Client();
        $response = $client->request("GET", $this->getRequestUrl($action), $requestOption);
        return $response->getBody()->getContents();
    }

    private function getRequestUrl($action)
    {
        if (isset($this->requestData['date'])) {
            $date = $this->requestData['date'];
        }
        $date = $date ? $date : date("Y-m-d");
        $config = Config::get($this->configName);
        $requestParams = [
            'clLogin' => $config['clLogin'],
            'clPwd' => $config['clPwd'],
            'retailers' => $config['retailers'],
            'datefrom' => $date,
            'dateto' => $date,
        ];
        $queryString = http_build_query($requestParams, '', '&', PHP_QUERY_RFC3986);
        return $config['url'] . $config['uri'] . $config['action'][$action] ."?". $queryString;
    }

    private function convert($xml)
    {
        if ($xml != "") {
            $obj = simplexml_load_string(trim($xml), null, LIBXML_NOCDATA);
            $array = json_decode(json_encode($obj), true);
            if (is_array($array)) {
                $array = $this->sanitize($array);
            }
                return $array;
        }
        return null;
    }

    private function sanitize($arr)
    {
        foreach($arr AS $k => $v) {
            if (is_array($v)) {
                if (count($v) > 0) {
                    $arr[$k] = $this->sanitize($v);
                } else {
                    $arr[$k] = "";
                }
            }
        }
        return $arr;
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