<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\IwmsCourierOrderLog;

class IwmsCourierOrderService extends IwmsBaseCallbackService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct()
    {
    }

    public function createCourierOrder($postMessage)
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
                    $this->generateCreateCourierErrorCsv($responseMessage, $batchObject->id);
                }
            }
        }
    }

    public function cancelCourierOrder($postMessage)
    {
    
    }   

    public function updateIwmCallbackOrderSuccess($responseMessage, $batchId)
    {
        $this->updateEsgOrderWaybillSataus($responseMessage, "2");
        $this->saveWaybillToPickListFolder($responseMessage);
        $this->updateIwmsCourierOrderSuccess($responseMessage, $batchId);
        $this->sendMsgCreateCourierOrderReport($responseMessage);
    }   

    private function updateIwmCallbackOrderFaild($responseMessage, $batchId)
    {
        $this->updateEsgOrderWaybillSataus($responseMessage, "3");
        $this->updateIwmsCourierOrderFaild($responseMessage, $batchId);
        $this->sendMsgCreateCourierErrorEmail($responseMessage, $batchId);
    }

    private function updateEsgOrderWaybillSataus($responseMessage, $waybillStatus)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                $soNoList[] = $value->merchant_order_id;
            }
        }
        if(!empty($soNoList)){
            So::whereIn("so_no", $soNoList)
            ->update(array("waybill_status" => $waybillStatus));
        }
    }

    private function saveWaybillToPickListFolder($responseMessage)
    {
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                $filePath = $this->getCourierPickListFilePath($value->merchant_order_id);
                $waybillLabel= file_get_contents($value->waybill_url);
                $file = $filePath.$value->merchant_order_id.'_awb.pdf';
                file_put_contents($file, $waybillLabel);
            }
        }
    }

    private function updateIwmsCourierOrderSuccess($responseMessage, $batchId)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                IwmsCourierOrderLog::where("reference_no",$value->merchant_order_id)
                ->where("batch_id",$batchId)
                ->update(array("status" => 1, "wms_order_code" => $value->courier_order_code));
            }
        }    
    }
    
    private function updateIwmsCourierOrderFaild($responseMessage, $batchId)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                IwmsCourierOrderLog::where("reference_no",$value->merchant_order_id)
                ->where("batch_id",$batchId)
                ->update(array("status" => -1, "response_message" => $value->error_remark));
            }
        }    
    }

    public function sendMsgCreateCourierOrderReport($successResponseMessage)
    {
        
    }

    public function sendMsgCreateCourierErrorEmail($faildResponseMessage,$batchId)
    {
        $subject = "Create Courier Order Failed,Please Check Error";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($faildResponseMessage as $value) {
            $msg .= "Order ID: $value->merchant_order_id, Error Message: $value->error_remark\r\n";
        }
        if($msg){
            $msg .= "\r\n";
            $this->_sendEmail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    public function generateCreateCourierErrorCsv($faildResponseMessage, $batchId)
    {
        $cellData = $this->getMsgCreateCourierOrderReport($faildResponseMessage, $batchId);
        $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$esgOrder->pick_list_no."/".$folderName."/".$esgOrder->courierInfo->courier_name."/";
        $fileName = "DHL_so_delivery_".date("YmdHis");
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $filePath, $cellData);
        }
    }

    private function getMsgCreateCourierOrderReport($responseMessage, $batchId)
    {
        $cellData = null;
        if(!empty($responseMessage)){
            foreach ($responseMessage as $value) {
                if(!empty($value->merchant_order_id)){
                    $iwmsCourierOrderLog = IwmsCourierOrderLog::where("reference_no",$value->merchant_order_id)
                    ->where("batch_id",$batchId)
                    ->first();
                }
                if(!empty($iwmsCourierOrderLog)){
                    $battery = " "; $battery_1 = " ";
                    $requestLog = json_decode($iwmsCourierOrderLog->request_log);
                    if($requestLog->incoterm == "DDU"){
                        $deliveryDuty = 'Delivery Duty Unpaid';
                    }else if ($requestLog->incoterm == "DDP"){
                        $deliveryDuty = 'Delivery Duty Paid'; 
                    }
                    if(isset($requestLog->battery) && $requestLog->battery == 1){
                        $battery = 'Lithium-ion batteries in compliance with';
                        $battery_1 = 'section II of PI967';
                    }
                    $cellRow = array(
                        'Field1' => 'ME',
                        'shipper' => 'INSPIRING WORLD LTD-ES PRIORITY',
                        'Field3' => 'Kary',
                        'Field4' => 'Workshop A 10/F',
                        'Field5' =>'Wah Shing Industrial Building',
                        'Field6' => 'Hong Kong',
                        'Field7' => 'Lai Chi Kok',
                        'Field8' => '35430892',
                        'Field9' => 'HKG',
                        'Field10' => '631158448',
                        'delivery_name' => $requestLog->delivery_name,
                        'delivery_company' => $requestLog->company,
                        'delivery_address1' => $requestLog->address,
                        'delivery_address2' => '.',
                        'delivery_address3' => '.',
                        'delivery_city' => $requestLog->city,
                        'delivery_state' => $requestLog->state,
                        'delivery_postcode' => $requestLog->postal,
                        'delivery_country_id' => $requestLog->country,
                        'tel' => $requestLog->phone,
                        'Field21' => 'P',
                        'qty' => 1,
                        'weight' => 0.5,
                        'currency_courier_id' => $requestLog->declared_currency,
                        'declared_value' => $requestLog->declared_value,
                        'cc_desc' => $requestLog->item[0]->hsdescription,
                        'battery' => $battery,
                        'battery_1' => $battery_1,
                        'cc_code' => $requestLog->item[0]->hscode,
                        'Field27' => FALSE,
                        'incoterm_3' => TRUE,
                        'Field29' => 'CHINA',
                        'so_no' => $iwmsCourierOrderLog->courier_reference_id,
                        'incoterm_1' => 1,
                        'Field32' => 'DD',
                        'incoterm' => $requestLog->incoterm,
                        'Field34' => ' ',
                        'Field35' => 0,
                        'Field36' => 0,
                        'Field37' => 0,
                        'incoterm' => $requestLog->incoterm,
                        'incoterm_2' => $deliveryDuty,
                        'Field40' => '631158448',
                        'Field41' => 'HK',
                        'Field42' => ' ',
                    );
                    $cellData[] = $cellRow;
                }
            }
            return $cellData;
        }
        return null;
    }
}