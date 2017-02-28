<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\SoItemDetail;
use App\Models\ExchangeRate;
use App\Models\IwmsMerchantCourierMapping;

use App\Services\ShippingService;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;

class IwmsShippedOrderService extends IwmsBaseCallbackService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct()
    {
    }

    public function deliveryConfirmShipped($postMessage)
    {
        $shippedCollection = [];
        $responseMessage = $postMessage->responseMessage;
        if (isset($responseMessage)) {
            foreach ($responseMessage as $shippedOrder) {
                $shipped = $this->confirmShippedEsgOrder($shippedOrder);
                $shippedCollection[$shippedOrder->reference_no] = $shipped;
            }
            $this->sendDeliveryOrderShippedReport($responseMessage, $shippedCollection);
        }

        return $shippedCollection;
    }

    public function confirmShippedEsgOrder($shippedOrder)
    {
        try {
            if ($shippedOrder->tracking_no
                && $esgOrder = So::UnshippedOrder()->where("so_no", $shippedOrder->reference_no)->first()
            ) {
                if ($firstSoAllocate =  $esgOrder->soAllocate->where('status', 2)->first()) {
                    $soShipment = $firstSoAllocate->soShipment;
                    $soShipment->status = 2;
                    $soShipment->tracking_no = $shippedOrder->tracking_no;
                    $soShipment->modify_by = 'system';
                    $soShipment->save();

                    if ($soAllocates = $esgOrder->soAllocate) {
                        foreach ($soAllocates as $soAllocate) {
                            $soAllocate->status = 3;
                            $soAllocate->modify_by = 'system';
                            $soAllocate->save();
                            SoItemDetail::where('so_no', $soAllocate->so_no)
                                ->where('line_no', $soAllocate->line_no)
                                ->update(['status' => 1]);
                        }
                    }

                    $esgOrder->auto_stockout = 2;
                    $esgOrder->status = 6;
                    $esgOrder->actual_weight = $shippedOrder->weight_charge;
                    $esgOrder->dispatch_date = $shippedOrder->ship_date ? $shippedOrder->ship_date : date("Y-m-d H:i:s");
                    $esgOrder->modify_by = 'system';
                    $esgOrder->save();
                    $this->setRealDeliveryCost($esgOrder);
                    return true;
                }
            }
        } catch (Exception $e) {
            $to = "privatelabel-log@eservicesgroup.com";
            $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
            $subject = "[ESG] Alert, Confirm Shipped Exception, ESG so_no: ". $shippedOrder->reference_no;
            $message = "Error: ". $e->getMessage();
            $this->_sendEmail($to, $subject, $message, $header);
        }

        return false;
    }

    public function sendDeliveryOrderShippedReport($responseMessage, $shippedCollection)
    {
        $cellData = $this->geMsgShippedReport($responseMessage, $shippedCollection);
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."ConfirmDispatch/";
        $fileName = "deliveryShipment-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG]By IWMS Confirm Dispatch Collection Report!";
                $attachment = array("path" => $orderPath,"file_name"=>$fileName.".xlsx");
                $this->sendAttachmentMail('privatelabel-log@eservicesgroup.com',$subject,$attachment, "brave.liu@eservicesgroup.com, roland.rao@eservicesgroup.com");
            }
        }
    }

    public function setRealDeliveryCost($esgOrder)
    {
        $this->shippingService = new ShippingService(
            new AcceleratorShippingRepository,
            new MarketplaceProductRepository
        );
        $deliveryInfo = $this->shippingService->orderDeliveryCost($esgOrder->so_no);
        if (!isset($deliveryInfo['error'])
            && isset($deliveryInfo['delivery_cost'])
        ) {
            $rate = ExchangeRate::getRate($deliveryInfo['currency_id'], $esgOrder->currency_id);
            $esgOrder->real_delivery_cost = $deliveryInfo['delivery_cost'] * $rate;
            $esgOrder->final_surcharge = $deliveryInfo['surcharge'];
            $esgOrder->modify_by = 'system';
            $esgOrder->save();
        }
    }

    public function geMsgShippedReport($responseMessage, $shippedCollection)
    {
        if(!empty($responseMessage)){
            $cellData[] = [
                'Request Id',
                'Merchant Id',
                'Sub Merchant Id',
                'Wms Order Code',
                'Order No.',
                'Platform Order No.',
                'Iwms Courier Code',
                'Merchant Courier ID',
                'Ship Date',
                'Tracking No.',
                'Tracking Length',
                'Weight Predict',
                'Weight Actual',
                'Finacl Weight Actual',
                'Confirm Dispatch',
            ];
            foreach ($responseMessage as $shippedOrder) {
                $esgSoNo = $shippedOrder->reference_no;
                if (isset($shippedCollection[$esgSoNo])
                    && isset($shippedOrder->tracking_no)
                    && $shippedCollection[$esgSoNo] === true
                ) {
                    $confirmDispatch = "Shipped Success";
                } else {
                    if (empty($shippedOrder->tracking_no)) {
                        $confirmDispatch = "Skipped, No tracking No.";
                    } else {
                        if (So::ShippedOrder()->where("so_no", $esgSoNo)->first()) {
                            $confirmDispatch = "Skipped, Has been shipped";
                        } else {
                            $confirmDispatch = "Shipped Failed";
                        }
                    }
                }

                $iwmsMerchantCourierMapping = IwmsMerchantCourierMapping::where("iwms_courier_code", $shippedOrder->iwms_courier_code)
                ->where("merchant_id", $shippedOrder->merchant_id)
                ->first();
                $merchantCourierId = $iwmsMerchantCourierMapping ? $iwmsMerchantCourierMapping->merchant_courier_id : "";
                $cellData[] = array(
                    'request_id' => $shippedOrder->request_id,
                    'merchant_id' => $shippedOrder->merchant_id,
                    'sub_merchant_id' => $shippedOrder->sub_merchant_id,
                    'wms_order_code' => $shippedOrder->wms_order_code,
                    'reference_no' => $esgSoNo,
                    'marketplace_reference_no' => $shippedOrder->marketplace_reference_no,
                    'iwms_courier_code' => $shippedOrder->iwms_courier_code,
                    'merchant_courier_id' => $merchantCourierId,
                    'ship_date' => $shippedOrder->ship_date,
                    'tracking_no' => $shippedOrder->tracking_no,
                    'tracking_length' => strlen("{$shippedOrder->tracking_no}"),
                    'weight_predict' => $shippedOrder->weight_predict,
                    'weight_actual' => $shippedOrder->weight_actual,
                    'weight_charge' => $shippedOrder->weight_charge,
                    'confirm_dispatch' => $confirmDispatch,
                );
            }
            return $cellData;
        }
        return null;
    }



}