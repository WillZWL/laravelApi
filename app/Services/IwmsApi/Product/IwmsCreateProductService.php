<?php

namespace App\Services\IwmsApi\Product;

use App\Models\Product;
use App\Models\IwmsProductLog;

use App;

use Illuminate\Database\Eloquent\Collection;

class IwmsCreateProductService
{
    private $fromData = null;
    private $toDate = null;
    private $warehouseIds = null;
    private $message = null;
    private $wmsPlatform = null;
    private $excludeMerchant = array("PREPD");

    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getProductCreationRequest($warehouseIds)
    {
        try {
            $productCreationRequest = null;
            $esgProducts = $this->getEsgProducts($warehouseIds);
            $batchRequest = $this->getProductCreationRequestBatch($esgProducts);
            return $this->getProductCreationBatchRequest($batchRequest);
        } catch (\Exception $e) {
            mail("brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", "[Vanguard] delivery order Exception", "Message: ". $e->getMessage() .", Line: ". $e->getLine() .", File: ". $e->getFile());
        }
    }

    public function getProductCreationRequestBatch($esgProducts)
    {
         $merchantId = "ESG";
        if(!$esgProducts->isEmpty()){
            $batchRequest = $this->getNewBatchId("CREATE_PRODUCT",$this->wmsPlatform,$merchantId);
            foreach ($esgProducts as $esgProduct) {
                $this->productCreationRequest($batchRequest->id , $esgProduct);
            }
            if(!empty($this->message)){
                $this->sendAlertEmail($this->message);
            }
            return $batchRequest;
        }
    }

    public function getProductCreationBatchRequest($batchRequest)
    {
        if(!empty($batchRequest)){
            $requestLogs = IwmsProductLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
            if(!empty($requestLogs)){
                foreach ($requestLogs as $requestLog) {
                    $productCreationRequest[] = json_decode($requestLog);
                }
                $request = array(
                    "batchRequest" => $batchRequest,
                    "requestBody" => $productCreationRequest
                );
                $batchRequest->request_log = json_encode($deliveryCreationRequest);
                $batchRequest->save();
                return $request;
            } else {
                $batchRequest->remark = "No Product request need send to wms";
                $batchRequest->status = "CE";
                $batchRequest->save();
            }
        }
    }

    private function productCreationRequest($batchId , $esgOrder)
    {
        $productCreationRequest = $this->getProductCreationObject($esgOrder);
        if ($productCreationRequest) {
            $this->_saveIwmsProductRequestData($batchId, $productCreationRequest);
        }
    }

    public function sendAlertEmail($message)
    {
        $subject = "Alert, Lack ESG with IWMS data mapping, It's blocked some order into the WMS, Please in time check it";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        
        if (isset($this->message['sku'])) {
            $msg .= "Has been blocked some orders: \r\n";
            $msg .= implode(", ", $this->message['sku']) ."\r\n";
        }
        if($msg){
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    private function getProductCreationObject($esgProduct, $warehouseId, $picklistNo = null)
    {
        $merchantId = "ESG"; $trackingNo = null;
        $courierId = $esgProduct->esg_quotation_courier_id;
        $iwmsWarehouseCode = $this->getIwmsWarehouseCode($warehouseId,$merchantId);
        $declaredDescAndCode = $this->getDeclaredDescAndCode($esgProduct->hscodeCategory);
        $postcode = preg_replace('/[^A-Za-z0-9\-]/', '', $esgOrder->delivery_postcode);
        $deliveryOrderObj = array(
            "wms_platform" => $this->wmsPlatform,
            "merchant_id" => $merchantId,
            "sub_merchant_id" => $esgProduct->merchantProductMapping->merchant_id,
            "sku" => $esgProduct->sku,
            "product_name" => $esgProduct->name,
            "business_type" => "E",
            "category_id" => "12",
            "reference_code" => $esgProduct->ean,
            "upc" => $esgProduct->ean,
            "battery_type" => $esgProduct->battery,
            "origin_country" => "HK",
            "iwms_measure_uom" => "1",
            "brand" => $esgProduct->brand->name,
            //"units" => '2',
            "weight" => $esgProduct->weight,
            "length" => $esgProduct->length,
            "width" => $esgProduct->width,
            "height" => $esgProduct->height,
            "declare_name" => $declaredDescAndCode["name"],
            "hscode" => $declaredDescAndCode["code"],
            "declare_price" => "12.2",
            "declare_description" => $declaredDescAndCode["description"],
            "merchant_image_url" => "http://shop.eservicesgroup.com/images/product/imageunavailable.jpg",
        );
        return $deliveryOrderObj;
    }

    public function _saveIwmsDeliveryOrderRequestData($batchId,$requestData)
    {
        $iwmsProductLog = new IwmsProductLog();
        $iwmsProductLog->batch_id = $batchId;
        $iwmsProductLog->wms_platform = $requestData["wms_platform"];
        $iwmsProductLog->merchant_id = $requestData["merchant_id"];
        $iwmsProductLog->sub_merchant_id = $requestData["sub_merchant_id"];
        $iwmsProductLog->sku = $requestData["sku"];
        $iwmsProductLog->category_id = $requestData["category_id"];
        $iwmsProductLog->reference_code = $requestData["reference_code"];
        $iwmsProductLog->upc = $requestData["upc"];
        $iwmsProductLog->battery_type = $requestData["battery_type"];
        $iwmsProductLog->hscode = $requestData["hscode"];
        $iwmsProductLog->declare_name = $requestData["declare_name"];
        $iwmsProductLog->declare_price = $requestData["declare_price"];
        $iwmsProductLog->request_log = json_encode($requestData);
        $iwmsProductLog->status = "0";
        $iwmsProductLog->repeat_request = "0";
        $iwmsProductLog->save();
        return $iwmsProductLog;
    }

    public function getEsgProducts($warehouseToIwms)
    {
        $esgOrders = Product::where("status",1)
            ->whereIn('default_ship_to_warehouse', $warehouseToIwms)
            ->whereHas('merchantProductMapping', function ($query) {
                $query->where('merchant_id', "ESG");
            })
            ->with("merchantProductMapping")
            ->with("category")
            ->with("subSubCategory")
            ->with("hscodeCategory")
            ->with("brand")
            ->with("productImages")
            ->limit(2)
            ->get();
        return $this->checkEsgProducts($esgProducts);
    }

    private function checkEsgProducts($esgProducts)
    {
        $validEsgProducts = new Collection();
        if(!$esgProducts->isEmpty()){
            foreach($esgProducts as $esgProduct) {
                $valid = null;
                $repeatResult = $this->validRepeatRequestProduct($esgProduct);
                if($repeatResult){
                    $validEsgProducts[] = $esgProduct;
                }
            }
        }
        return $validEsgOrders;
    }

    private function validRepeatRequestProduct($esgProduct)
    {
        $this->merchantId = "ESG";
        $this->esgProductSku = $esgProduct->sku;
        $requestProductLog = IwmsProductLog::where("merchant_id", $this->merchantId)
                        ->where("sku", $esgProduct->sku)
                        ->where("status", 1)
                        ->orWhere(function ($query) {
                            $query->where("merchant_id", $this->merchantId)
                                ->where("sku", $this->esgProductSku)
                                ->whereIn("status", array("0","-1"))
                                ->where("repeat_request", "!=", 1);
                            })
                        ->first();
        if(!empty($requestProductLog)){
            return false;
        }else{
            return true;
        }
    }

    public function getProductReportRequest($warehouseIds)
    {
        $productCreationRequest = null;
        $batchRequest = $this->getProductCreationRequestBatch($warehouseIds);
        if(!empty($batchRequest)){
            $requestLogs = IwmsProductLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
            if(!empty($requestLogs)){
                foreach ($requestLogs as $requestLog) {
                    $productCreationRequest[] = json_decode($requestLog);
                }
                $request = array(
                    "batchRequest" => $batchRequest,
                    "requestBody" => $productCreationRequest
                );
                return $request;
            }
        }
    }

    public function getDeclaredDescAndCode($hscodeCategory)
    {
        if ($hscodeCategory) {
            $hscodeDutyCountry = HscodeDutyCountry::where("hscode_cat_id", $hscodeCatId)->where("country_id", $deliveryCountryId)->first();
            if ($hscodeDutyCountry) {
                $declaredObject["code"] = $hscodeDutyCountry->optimized_hscode;
            }
            if (!$code) {
                $declaredObject["code"] = $hscodeCategory->general_hscode;
            }
            $declaredObject["name"] = $hscodeCategory->name;
            $declaredObject["description"] = $hscodeCategory->description;
            return $declaredObject;
        }
    }
    
    private function _setSkuMessage($sku)
    {
        if (! isset($this->message['sku'])) {
            $this->message['sku'] = [];
        }
        $this->message['sku'][] = $sku;
    }

}