<?php

namespace App\Services\IwmsApi\Order;

use App;

use Illuminate\Database\Eloquent\Collection;

class IwmsBaseOrderService
{
    protected $message;
    protected $wmsPlatform = null;
    protected $lgsCourier = array("4PX-PL-LGS"); 

    public function getCreationIwmsOrderObject($esgOrder, $iwmsCourierCode, $iwmsWarehouseCode = null, $extraInstruction = null ,$trackingNo = null)
    {
        $merchantId = "ESG";
        if(in_array($iwmsCourierCode, $this->lgsCourier)){
            $address = mb_substr($esgOrder->delivery_address, 0, 120, "utf-8");
            $address = (preg_replace( "/\r|\n/", "", $address));
        }else{
            $address = $esgOrder->delivery_address;
        }
        $postcode = preg_replace('/[^A-Za-z0-9\-]/', '', $esgOrder->delivery_postcode);
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
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state ? $esgOrder->delivery_state : "x",
            "address" => $address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $this->getEsgOrderPhone($esgOrder),
            "amount_in_hkd" => '0',
            "amount_in_usd" => '0',
            "extra_instruction" => $extraInstruction,
        );
        foreach ($esgOrder->soItem as $esgOrderItem) {
            $hscode = null; $hsDescription = null;
            if($esgOrderItem->hscodeCategory){
                $hscode = $esgOrderItem->hscodeCategory->general_hscode;
                $hsDescription = $esgOrderItem->hscodeCategory->description;
            }
            $creationOrderItem = array(
                "sku" => $esgOrderItem->prod_sku,
                "product_name" => (preg_replace( "/\r|\n/", "", $esgOrderItem->prod_name)),
                "quantity" => $esgOrderItem->qty,
                "hscode" => $hscode,
                "hsDescription" => $hsDescription,
                "unit_price_hkd" => '0',
                "unit_price_usd" => '0',
                "marketplace_items_serial" => $esgOrderItem->ext_item_cd,
            );
            $creationOrderObject["item"][] = $creationOrderItem;
        }
        return $creationOrderObject;
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