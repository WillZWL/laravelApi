<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use newegg api package
use App\Repository\NeweggMws\NeweggCore;

class ApiNeweggProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    private $storeCurrency;
    private $resourceMethod;
    public function __construct()
    {
        $this->setResourceMethod("POST");
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
        if(!$processStatusProduct->isEmpty())
        {
            $failed_sku_list = array();
            $failed_sku_name = array();
                
            foreach ($processStatusProduct as $index => $pendingSku) 
            {
                $requestXml = array();
                $requestXml[] = '<ItemPriceInfo>';
                $requestXml[] =     '<Type>1</Type>';
                $requestXml[] =     '<Value>'.$pendingSku->marketplace_sku.'</Value>';
                $requestXml[] =     '<PriceList>';
                $requestXml[] =         '<Price>';
                $requestXml[] =             '<CountryCode>'.$pendingSku->id_3_digit.'</CountryCode>';
                $requestXml[] =             '<Currency>'.$pendingSku->currency.'</Currency>';
                $requestXml[] =             '<SellingPrice>'.$pendingSku->price.'</SellingPrice>';
                $requestXml[] =             '<MSRP>'.number_format(($pendingSku->price*1.3), 2, '.', '').'</MSRP>';
                $requestXml[] =         '</Price>';
                $requestXml[] =     '</PriceList>';
                $requestXml[] = '</ItemPriceInfo>';
                $requestParams = $this->getRequestParams();
                $this->neweggCore = new NeweggCore($storeName);
                $result = $this->neweggCore->query("contentmgmt/item/international/price", $this->getResourceMethod(), $requestParams, implode("\n", $requestXml));
                if($result)
                {
                    if($result["error"])
                    {
                        $error_string = $result["error"][2];
                        $error_split = explode("\"",$error_string);

                        $failed_sku_list[$pendingSku->marketplace_sku][$pendingSku->id_3_digit] = $error_split[7];
                        $failed_sku_name[$pendingSku->marketplace_sku][$pendingSku->id_3_digit] = $pendingSku->name; // SBF#10337 add product name
                    }
                    else
                    {
                        $this->updatePendingProductProcessStatusBySku($pendingSku,self::PENDING_PRICE);
                    }
                }
                else
                {
                    $failed_sku_list[$pendingSku->marketplace_sku][$pendingSku->id_3_digit] = "Failed to update this sku price";
                    $failed_sku_name[$pendingSku->marketplace_sku][$pendingSku->id_3_digit] = $pendingSku->name;
                }
            }
        
            if($failed_sku_list)
            {
                $subject = "[NEWEGG] Price update failed!";
                $message = "The following sku has error in updating their price to newegg marketplace. \r\n\r\n";
                foreach ($failed_sku_list as $key => $failed_sku) 
                {
                    foreach ($failed_sku as $country_code => $error_message) 
                    {
                        $message.="Marketplace SKU: ".$key."\r\n";
                        $message.="Product Name: ".$failed_sku_name[$key][$country_code]."\r\n";
                        $message.="Country code: ".$country_code."\r\n";
                        $message.="Message: ". $error_message."\r\n\r\n";    
                    }
                }
                $message .= "\r\nThanks\r\n"; 
                $this->sendAlertMailMessage($subject, $message);
            }
            return true;
        }
        return false;
    }

    public function submitProductInventory($storeName)
    {
        //For debugging purpose, uncomment the following line to get the list of items to update
        //dd(print_r($this->getProductInventory($storeName)));

        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty())
        {
            $failed_sku_list = array();
            $failed_sku_name = array();
            $successful_sku_list = array();
            $counterSku = 0;
            foreach ($processStatusProduct as $index => $pendingSku) 
            {
                //Set the sku counter to ensure the api calling doesn't reach the limit of 300 per min
                $counterSku++;
                if ($counterSku > 300) //once limit, sleep for 1 min before continue to the next 300 skus
                {
                    sleep(60);
                    $counterSku = 0;
                }

                //First get the warehouse availability for each item
                $requestXml = array();
                $requestXml[] = '<ContentQueryCriteria>';
                $requestXml[] =     '<Type>1</Type>';
                $requestXml[] =     '<Value>'.$pendingSku->marketplace_sku.'</Value>';
                $requestXml[] = '</ContentQueryCriteria>';
                $requestXml = implode("\n", $requestXml);
                $requestParams = $this->getRequestParams();
                $this->neweggCore = new NeweggCore($storeName);
                $result = $this->neweggCore->query("contentmgmt/item/international/inventory", "PUT", $requestParams, $requestXml);
                unset($requestXml); //clear the requestXml before start the next api call

                //Finally, update the inventory of the current item
                //But before we proceed, check if the current item retrieved have error or not, proceed only if current item got no error
                if ($result["error"])
                {
                    $error_string = $result["error"][2];
                    $error_split = explode("\"",$error_string);

                    $failed_sku_list[$pendingSku->marketplace_sku] = $error_split[7];
                    $failed_sku_name[$pendingSku->marketplace_sku] = $pendingSku->name; // SBF#10337 add product name                        
                }

                else
                {
                    $requestXml = array();
                    $requestXml[] = '<ItemInventoryInfo>';
                    $requestXml[] =     '<Type>1</Type>';
                    $requestXml[] =     '<Value>'.$pendingSku->marketplace_sku.'</Value>';
                    $requestXml[] =     '<InventoryList>';

                    //Updating each item inventory in all available warehouses                
                    foreach ($result["data"]["InventoryAllocation"] as $key => $inventoryDetails) 
                    {
                        $requestXml[] =         '<Inventory>';

                        //For debugging purpose, uncomment the if else below will forcing one sku to use wrong warehouse (18593-AA-NA)
                        //if ($pendingSku->marketplace_sku == '18593-AA-BK')
                        //   $requestXml[] =             '<WarehouseLocation>HKG</WarehouseLocation>';
                        //else
                            $requestXml[] =             '<WarehouseLocation>'.$inventoryDetails["WarehouseLocation"].'</WarehouseLocation>';

                        $requestXml[] =             '<AvailableQuantity>'.$pendingSku->inventory.'</AvailableQuantity>';
                        $requestXml[] =         '</Inventory>';
                    }

                    $requestXml[] =     '</InventoryList>';
                    $requestXml[] = '</ItemInventoryInfo>';
                    $requestXml = implode("\n", $requestXml);
                    $requestParams = $this->getRequestParams();
                    $this->neweggCore = new NeweggCore($storeName);
                    $result = $this->neweggCore->query("contentmgmt/item/international/inventory", $this->getResourceMethod(), $requestParams, $requestXml);
                    
                    if($result)
                    {
                        if($result["error"])
                        {
                            $error_string = $result["error"][2];
                            $error_split = explode("\"",$error_string);

                            $failed_sku_list[$pendingSku->marketplace_sku] = $error_split[7];
                            $failed_sku_name[$pendingSku->marketplace_sku] = $pendingSku->name; // SBF#10337 add product name                        
                        }
                        else
                        {
                            $this->updatePendingProductProcessStatusBySku($pendingSku,self::PENDING_INVENTORY);

                            //Set up successful update message per warehouse 
                            foreach ($result["data"]["InventoryList"] as $key => $newInventoryDetails) 
                            {  
                                $successful_sku_list[$pendingSku->marketplace_sku][$newInventoryDetails["WarehouseLocation"]]["ProductName"] = "[".$counterSku."] ".$pendingSku->name; // SBF#10337 add product name
                                $successful_sku_list[$pendingSku->marketplace_sku][$newInventoryDetails["WarehouseLocation"]]["UpdatedInventory"] = $newInventoryDetails["AvailableQuantity"];
                            }
                        }
                    }
                    else
                    {
                        $failed_sku_list[$pendingSku->marketplace_sku] = "Failed to update this sku inventory";
                        $failed_sku_name[$pendingSku->marketplace_sku] = $pendingSku->name;
                    }
                }
            }

            if($failed_sku_list)
            {
                $subject = "[NEWEGG] Inventory update failed!";
                $message = "The following sku has error in updating their inventory to newegg marketplace. \r\n\r\n";
                foreach ($failed_sku_list as $key => $failed_sku_message) 
                {
                    $message.="Marketplace SKU: ".$key."\r\n";
                    $message.="Product Name: ".$failed_sku_name[$key]."\r\n";
                    $message.="Message: ". $failed_sku_message."\r\n\r\n\n";    
                }
                $message .= "\r\nThanks\r\n";
                $this->sendInventoryAlertMailMessage($subject, $message, 0);
            }

            if ($successful_sku_list)
            {
                $subject = "[NEWEGG] Inventory update success!";
                $message = "The following sku have succeeded in updating their inventory to Newegg marketplace. \r\n\r\n";
                foreach ($successful_sku_list as $key => $successful_sku) 
                {
                    foreach ($successful_sku as $warehouse_code => $successfulDetails) 
                    {
                        $message.="Marketplace SKU: ".$key."\r\n";
                        $message.="Product Name: ".$successfulDetails["ProductName"]."\r\n";                        
                        $message.="Warehouse Location: ".$warehouse_code."\r\n";
                        $message.="Updated inventory: ".$successfulDetails["UpdatedInventory"]."\r\n\n";
                    }
                }
                $message .= "\r\nThanks\r\n";                
                $this->sendInventoryAlertMailMessage($subject, $message, 1);
            }
            return true;
        }
        return false;
    }

    public function getProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty())
        {
            foreach ($processStatusProduct as $index => $pendingSku) 
            {
                $requestXml = array();
                $requestXml[] = '<ContentQueryCriteria>';
                $requestXml[] =     '<Type>1</Type>';
                $requestXml[] =     '<Value>'.$pendingSku->marketplace_sku.'</Value>';
                $requestXml[] = '</ContentQueryCriteria>';
                $requestXml = implode("\n", $requestXml);
                $requestParams = $this->getRequestParams();
                $this->neweggCore = new NeweggCore($storeName);
                $result = $this->neweggCore->query("contentmgmt/item/international/inventory", "PUT", $requestParams, $requestXml);
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

    protected function getRequestParams()
    {
        $requestParams = [""=>""];
        return $requestParams;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl($value)
    {
        $this->resourceUrl = $value;
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
}
