<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use Config;

//use newegg api package
use App\Repository\NeweggMws\NeweggProductUpdate;

class ApiNeweggProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    public function __construct()
    {
        $this->stores =  Config::get('newegg-mws.store');
    }

    public function getPlatformId()
    {
        return 'Newegg';
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $this->submitProductPrice($storeName);
        $this->submitProductInventory($storeName);
    }

    public function submitProductPrice($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_PRICE);
        if(!$processStatusProduct->isEmpty()){
            $this->neweggProductUpdate = new NeweggProductUpdate($storeName);
            $message = null;
            foreach ($processStatusProduct as $object) {
                $result = $this->neweggProductUpdate->updateInternationalPrice($object);
                if($result){
                    if(isset($result["error"]["response"])){
                       $message .= $this->getAlertMailMessage($result,$object);
                    }
                    $this->updatePendingProductProcessStatusBySku($object,self::PENDING_PRICE);
                }
            }
            if($message){
                $alertEmail = $this->stores[$storeName]["userId"];
                $subject = $storeName." update price failed!";
                $this->sendMailMessage($alertEmail, $subject, $message);
            }
        }
        return false;
    }

    public function submitProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty()){
            $this->neweggProductUpdate = new NeweggProductUpdate($storeName);
            $errorMessage = null; $successMessage = null;
            foreach ($processStatusProduct as $object){
                $responesData = $this->neweggProductUpdate->getAvailabilityWarehose($object->marketplace_sku);
                if($responesData["error"]){
                    $errorMessage .= $this->getAlertMailMessage($responesData,$object);
                }else{
                    $result = $this->neweggProductUpdate->updateInternationalInventory($object,$responesData["data"]["InventoryAllocation"]);
                    if($result){
                        if(isset($result["error"]["response"])){
                            $errorMessage .= $this->getAlertMailMessage($result,$object);
                        }else{
                            foreach ($result["data"]["InventoryList"] as $inventoryList) {
                                $successMessage .= "Marketplace SKU: ".$object->marketplace_sku."\r\n";                   
                                $successMessage .= "Warehouse Location: ".$inventoryList['WarehouseLocation']."\r\n";
                                $successMessage .= "Updated inventory: ".$object->inventory."\r\n\n";
                            }
                        }
                        $this->updatePendingProductProcessStatusBySku($object,self::PENDING_PRICE);
                    }
                }
            }
            if($errorMessage){
                $subject = $storeName." update inventory failed!";
                $this->sendInventoryAlertMailMessage($subject, $errorMessage, 0);
            }
            if($successMessage){
                $subject = $storeName." update inventory success!";
                $this->sendInventoryAlertMailMessage($subject, $successMessage, 1);
            }
            return true;
        }
        return false;
    }

    public function getProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty()){
            $this->neweggProductUpdate = new NeweggProductUpdate($storeName);
            foreach ($processStatusProduct as $object) {
                $result = $this->neweggProductUpdate->getAvailabilityWarehose($object);
                $productInventoryList[] = $result;
            }
            return $productInventoryList;
        }
    }

    public function submitProductCreate($storeName)
    {

    }

    public function getProductList($storeName)
    {

    }

    private function convertFromUtcToPst($timestamp, $format = "Y-m-d")
    {
        if($timestamp) {
             // change timezone to Pacific Standard
            $dt = new \DateTime($timestamp);
            $dt->setTimezone(new \DateTimeZone("PST"));
            $dateTime = $dt->format($format);
            return $dateTime;
        }

        return "";
    }

    private function convertFromPstToUtc($timestamp, $timestampFormat = "d/m/Y H:i:s", $format = "Y-m-d H:i:s")
    {
        if($timestamp) {
            # Newegg's time is in PST
            # let DateTime know the format of your $timestamp
            $dtOrderdate = \DateTime::createFromFormat($timestampFormat, $timestamp, new \DateTimeZone("PST"));
            $dtOrderdate->setTimezone(new \DateTimeZone("UTC"));
            $utcOrderDate = $dtOrderdate->format($format);
            return $utcOrderDate;
        }
        return "";
    }

    public function sendAlertMailMessage($subject,$message)
    {
        mail("newegg@brandsconnect.net, serene.chung@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservicesgroup.com');
        return true;
    }

    public function sendInventoryAlertMailMessage($subject,$message,$status)
    {
        if ($status == 1) //success, send to myself
            $recipient = "willy.dharman@eservicesgroup.com";
        else //failed, also send to newegg
            $recipient = "newegg@brandsconnect.net, willy.dharman@eservicesgroup.com";

        mail($recipient, $subject, $message, $headers = 'From: admin@shop.eservicesgroup.com');
        return true;
    }

    private function getAlertMailMessage($result,$object)
    {
        if(isset($result["error"]["response"])){
            $response = json_decode($result["error"]["response"]);
            foreach ($response as $errorMessage) {
                $message .= "ESG SKU ".$object->sku." error: ".$errorMessage->Message."\r\n";
            }
            return $message;
        }
    }

}
