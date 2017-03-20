<?php

namespace App\Services\IwmsApi\Order;

use App;

use Illuminate\Database\Eloquent\Collection;

class IwmsBaseOrderService
{
    use \App\Services\TraitDeclaredService;

    protected $message;
    protected $wmsPlatform = null;
    protected $lgsCourier = array("4PX-PL-LGS"); 
    protected $bizType = array(
        "ACCELERATOR" => "A",
        "DISPATCH" => "D",
        "EXPANDER" => "E",
        );

    public function getCreationIwmsCourierOrderObject($esgOrder, $iwmsCourierCode, $iwmsWarehouseCode = null, $extraInstruction = null ,$trackingNo = null)
    {
        $declaredObject = $this->getOrderDeclaredObject($esgOrder);
        $merchantId = "ESG";
        $creationOrderObject = array(
            "wms_platform" => $this->wmsPlatform,
            "iwms_warehouse_code" => $iwmsWarehouseCode,
            "reference_no" => $esgOrder->so_no,
            "iwms_courier_code" => $iwmsCourierCode,
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "marketplace_platform_id" => $esgOrder->biz_type."-ESG-".$esgOrder->delivery_country_id,
            "merchant_id" => $merchantId,
            "sub_merchant_id" => $esgOrder->sellingPlatform->merchant_id,
            "tracking_no" => $trackingNo,
            "store_name" => $esgOrder->sellingPlatform->store_name,
            "incoterm" => $esgOrder->incoterm,
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company ? $esgOrder->delivery_company : $esgOrder->delivery_name,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "country_name" => $esgOrder->country->name,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state ? $esgOrder->delivery_state : "NA",
            "address" => $esgOrder->delivery_address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $esgOrder->client->tel_3,
            "declared_value" => $declaredObject["declared_value"],
            "declared_currency" => $declaredObject["declared_currency"],
            "courier_reference_id" => $esgOrder->so_no."-".$esgOrder->sellingPlatform->merchant_id."-".$this->bizType[$esgOrder->sellingPlatform->type],
            "amount_in_hkd" => '0',
            "amount_in_usd" => '0',
            "extra_instruction" => $extraInstruction,
        );
        
        $hscodeCategoryName = array(); 
        foreach ($esgOrder->soItem as $esgOrderItem) {
            $hsCode = null; $hsDescription = null;
            if(isset($declaredObject["items"][$esgOrderItem->prod_sku]["code"])){
                $hsCode = $declaredObject["items"][$esgOrderItem->prod_sku]["code"];
            }
            if(isset($declaredObject["items"][$esgOrderItem->prod_sku]["prod_desc"])){
                $hsDescription = $declaredObject["items"][$esgOrderItem->prod_sku]["declared_desc"];
            }
            $hscodeCategoryName[] = $hsDescription;
            $creationOrderItem = array(
                "sku" => $esgOrderItem->prod_sku,
                "product_name" => (preg_replace( "/\r|\n/", "", $esgOrderItem->prod_name)),
                "quantity" => $esgOrderItem->qty,
                "hscode" => $hsCode,
                "hsdescription" => $hsDescription,
                "commodity_code" => $hsCode,
                "commodity_name" => $hsDescription,
                "unit_price_hkd" => '0',
                "unit_price_usd" => '0',
                "marketplace_items_serial" => $esgOrderItem->ext_item_cd,
            );
            $creationOrderObject["item"][] = $creationOrderItem;
            if(in_array($esgOrderItem->product->battery, [1, 2])){
                $isBattery = 1;
            }
        }
        if(isset($isBattery) && $isBattery){
            $creationOrderObject["battery"] = 1;
        }
        $creationOrderObject["shipment_content"] = $hscodeCategoryName[0];
        return $creationOrderObject;
    }

    private function getValidPostCode($postCode, $deliveryCountry)
    {
        if(empty($postCode)){
            if($deliveryCountry == "HK"){
                return "00000";
            }else{
                return null;
            }
        }else{
            if($deliveryCountry == "US" && strlen($postCode) < 5){
                return str_pad($esgOrder->delivery_postcode, 5, "0", STR_PAD_LEFT);
            }else{
                return $postCode;
            }
        }
    }

    private function getEsgOrderPhone($esgOrder)
    {
        $phone = "0-0-0";
        $soPhone =  $esgOrder->del_tel_1.$esgOrder->del_tel_2.$esgOrder->del_tel_3;
        $clientPhone = $esgOrder->client->tel_1.$esgOrder->client->tel_2.$esgOrder->client->tel_3;
        if ($soPhone ) {
            $phone = $soPhone;
        } else if ($clientPhone) {
            $phone = $clientPhone;  
        }
        return $phone;
    }

    public function sendAlertEmail($message)
    {
        $subject = "Alert, Lack ESG with IWMS data mapping, It's blocked some order into the WMS, Please in time check it";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        if (isset($this->message['warehouse'])) {
            $msg .= "Here ESG warehouse ID need with IWMS fisrt mapping\r\n";
            $warehouseNotes = array_unique($this->message['warehouse']);
            foreach ($warehouseNotes as $merchantId => $warehouseNote) {
                foreach ($warehouseNote as $key => $warehouseId) {
                    $msg .= "Merchant ID: $merchantId, Warehouse ID: $warehouseId\r\n";
                }
            }
        }
        $msg .= "\r\n";
        if (isset($this->message['courier'])) {
            $msg .= "Here ESG Courier ID need with IWMS fisrt mapping\r\n";
            $courierNotes = array_unique($this->message['courier']);
            foreach ($courierNotes as $merchantId => $courierNote) {
                foreach ($courierNote as $key => $courierId) {
                    $msg .= "Merchant ID: $merchantId, Courier ID: $courierId\r\n";
                }
            }
        }
        if (isset($this->message['so_no'])) {
            $msg .= "Has been blocked some orders: \r\n";
            $msg .= implode(", ", $this->message['so_no']) ."\r\n";
        }
        if($msg){
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    public function _setWarehouseMessage($merchantId, $warehouseId)
    {
        if (! isset($this->message['warehouse'])) {
            $this->message['warehouse'] = [];
        }
        if (isset($this->message['warehouse']) 
            && ! isset($this->message['warehouse'][$merchantId])
        ) {
            $this->message['warehouse'][$merchantId] = [];
        }
        $this->message['warehouse'][$merchantId][] = $warehouseId;
    }

    public function _setCourierMessage($merchantId, $courierId)
    {
        if (! isset($this->message['courier'])) {
            $this->message['courier'] = [];
        }
        if (isset($this->message['courier']) 
            && ! isset($this->message['courier'][$merchantId])
        ) {
            $this->message['courier'][$merchantId] = [];
        }
        $this->message['courier'][$merchantId][$courierId] = $courierId;
    }

    public function _setSoNoMessage($so_no)
    {
        if (! isset($this->message['so_no'])) {
            $this->message['so_no'] = [];
        }
        $this->message['so_no'][] = $so_no;
    }
}