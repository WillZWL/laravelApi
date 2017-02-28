<?php

namespace App\Services\IwmsApi;

use App\Models\IwmsMerchantWarehouseMapping;
use App\Models\IwmsMerchantCourierMapping;
use App\Models\So;
use App;
use Excel;

trait IwmsBaseService
{
    private $iwmStatus = array("Success" => "1","Failed" => "2");
    private $token = "iwms-esg";
    private $awbLabelCourierList = null;
    private $invoiceLabelCourierList = null;

    public function getNewBatchId($name,$wmsPlatform, $merchantId, $requestLog = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getNewBatch($name,$wmsPlatform, $merchantId, $requestLog);
    }

    public function getDeliveryOrderLogBatch($batchRequestId = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getDeliveryOrderLogBatch($batchRequestId);
    }

    public function getIwmsWarehouseCode($warehouseId,$merchantId)
    {
        $iwmsMerchantWarehouseMapping = IwmsMerchantWarehouseMapping::where("merchant_warehouse_id",$warehouseId)
            ->where("merchant_id", $merchantId)
            ->first();
        if ($iwmsMerchantWarehouseMapping) {
            return $iwmsMerchantWarehouseMapping->iwms_warehouse_code;
        }
        return null;
    }

    public function getMerchantWarehouseCode($iwmsWarehouseCode, $merchantId)
    {
        $iwmsMerchantWarehouseMapping = IwmsMerchantWarehouseMapping::where("iwms_warehouse_code", $iwmsWarehouseCode)
            ->where("merchant_id", $merchantId)
            ->first();
        if ($iwmsMerchantWarehouseMapping) {
            return $iwmsMerchantWarehouseMapping->merchant_warehouse_id;
        }
        return null;
    }

    public function getIwmsCourierCode($courierId,$merchantId)
    {
        $iwmsMerchantCourierMapping = IwmsMerchantCourierMapping::where("merchant_courier_id",$courierId)
            ->where("merchant_id", $merchantId)
            ->first();

        if ($iwmsMerchantCourierMapping) {
            return $iwmsMerchantCourierMapping->iwms_courier_code;
        }
        return null;
    }

    public function getIwmsCourierApiMappingList($wmsPlatform, $merchantId)
    {
        return IwmsMerchantCourierMapping::where("wms_platform", $wmsPlatform)
            ->where("merchant_id", $merchantId)
            ->where("status", 1)
            ->pluck("merchant_courier_id")
            ->all();
    }

    public function saveBatchFeedIwmsResponseData($batchObject,$responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->saveBatchFeedIwmsResponseData($batchObject,$responseJson);
    }

    public function updateBatchIwmsResponseData($batchObject, $responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->updateBatchIwmsResponseData($batchObject, $responseJson);
    }

    public function validIwmsCallBackApiToken()
    {
        $iwmsCallbackApiService = App::make("App\Services\IwmsCallbackApiService");
        return $iwmsCallbackApiService->valid();
    }

    public function getSoAllocatedPickListNo($esgOrderNo)
    {
        $esgOrder = So::where("so_no", $esgOrderNo)
                    ->first();
        if(!empty($esgOrder)){
            return $esgOrder->pick_list_no;
        }
    }

    public function getEsgOrderAwbLabelUrl($esgOrder)
    {
        if(!empty($esgOrder->pick_list_no)){
            $url = "order/.$esgOrder->pick_list_no./AWB?so_no=".$esgOrder->so_no;
            return url($url);
        }
        return null;
    }

    public function getEsgOrderInvoiceLabelUrl($esgOrder)
    {
        if(!empty($esgOrder->pick_list_no)){
            $url = "order/.$esgOrder->pick_list_no./invoice?so_no=".$esgOrder->so_no;
            return url($url);
        }
        return null;
    }

    public function getEsgOrderMsdsLabelUrl()
    {
        return null;
    }

    public function getPostAwbLabelToIwmsCourierList()
    {
        if(empty($this->awbLabelCourierList)){
            $this->awbLabelCourierList = IwmsMerchantCourierMapping::where("wms_platform","4px")
                    ->whereIn("iwms_courier_code", ["4PX-DHL","4PX-PL-LGS"])
                    ->where("merchant_id", "ESG")
                    ->pluck("merchant_courier_id")
                    ->all();
        }
        return $this->awbLabelCourierList;
    }

    public function getPostInvoiceLabelToIwmsCourierList()
    {
        if(empty($this->invoiceLabelCourierList)){
            $this->invoiceLabelCourierList = IwmsMerchantCourierMapping::where("wms_platform", "4px")
                    ->whereIn("iwms_courier_code", ["4PX-DHL","4PX-PL-LGS"])
                    ->where("merchant_id", "ESG")
                    ->pluck("merchant_courier_id")
                    ->all();
        }
        return $this->invoiceLabelCourierList;
    }

    public function getLgsOrderMerchantCourierIdList($wmsPlatform)
    {
        return IwmsMerchantCourierMapping::where("wms_platform", $wmsPlatform)
                    ->whereIn("iwms_courier_code", ["4PX-PL-LGS"])
                    ->where("merchant_id", "ESG")
                    ->pluck("merchant_courier_id")
                    ->all();
    }

    public function sendAttachmentMail($alertEmail,$subject,$attachment, $cc = "")
    {
        /* Attachment File */
        $fileName = $attachment["file_name"];
        $path = $attachment["path"];

        // Read the file content
        $file = $path.'/'.$fileName;
        $fileSize = filesize($file);
        $handle = fopen($file, "r");
        $content = fread($handle, $fileSize);
        fclose($handle);
        $content = chunk_split(base64_encode($content));

        /* Set the email header */
        // Generate a boundary
        $boundary = md5(uniqid(time()));

        // Email header
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        if ($cc) {
            $header .= "Cc: ". $cc .PHP_EOL;
        }
        $header .= "MIME-Version: 1.0".PHP_EOL;

        // Multipart wraps the Email Content and Attachment
        $header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"".PHP_EOL;
        $header .= "This is a multi-part message in MIME format.".PHP_EOL;
        $header .= "--".$boundary.PHP_EOL;

        // Email content
        // Content-type can be text/plain or text/html
        $message = "The attachment is iwms delivery order Report, Please check it!".PHP_EOL;
        $message .= "Thanks".PHP_EOL.PHP_EOL;
        $message .= "--".$boundary.PHP_EOL;

        // Attachment
        // Edit content type for different file extensions
        $message .= "Content-Type: application/xml; name=\"".$fileName."\"".PHP_EOL;
        $message .= "Content-Transfer-Encoding: base64".PHP_EOL;
        $message .= "Content-Disposition: attachment; filename=\"".$fileName."\"".PHP_EOL.PHP_EOL;
        $message .= $content.PHP_EOL;
        $message .= "--".$boundary."--";
        mail("{$alertEmail}, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
    }

}