<?php

namespace App\Services\IwmsApi;

use App\Models\IwmsMerchantWarehouseMapping;
use App\Models\IwmsMerchantCourierMapping;
use App;
use Excel;

trait IwmsBaseService
{
    private $iwmStatus = array("Success" => "1","Failed" => "2");
    private $token = "iwms-esg";

    public function getNewBatchId($name,$wmsPlatform, $merchantId, $requestLog = null)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getNewBatch($name,$wmsPlatform, $merchantId, $requestLog);
    }

    public function getIwmsWarehouseCode($warehouseId,$merchantId)
    {
        $iwmsMerchantWarehouseMapping = IwmsMerchantWarehouseMapping::where("merchant_warehouse_id",$warehouseId)
            ->where("merchant_id", $merchantId)
            ->first();

        if ($iwmsMerchantWarehouseMapping) {
            return $iwmsMerchantWarehouseMapping->value("iwms_warehouse_code");
        }

        return null;
    }

    public function getIwmsCourierCode($courierId,$merchantId)
    {
        $iwmsMerchantCourierMapping = IwmsMerchantCourierMapping::where("merchant_courier_id",$courierId)
            ->where("merchant_id", $merchantId)
            ->first();

        if ($iwmsMerchantCourierMapping) {
            return $iwmsMerchantCourierMapping->value("iwms_courier_code");
        }

        return null;
    }

    public function saveBatchIwmsResponseData($batchObject,$responseJson)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        $batchRequestService->saveBatchIwmsResponseData($batchObject,$responseJson);
    }

    public function validIwmsCallBackApiToken()
    {   
        $iwmsCallbackApiService = App::make("App\Services\IwmsCallbackApiService");
        return $iwmsCallbackApiService->valid();
    }

    public function createExcelFile($fileName, $orderPath, $cellData)
    {
        //Excel文件导出功能
        $excelFile = Excel::create($fileName, function ($excel) use ($cellData) {
            $excel->sheet('sheet1', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->store("xlsx",$orderPath);
        if($excelFile){
            return true;
        }
    }

    public function sendAttachmentMail($alertEmail,$subject,$attachment)
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