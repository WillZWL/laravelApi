<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Models\WmsWarehouseMapping;
use Excel;

class ApiBaseService extends PlatformMarketConstService
{
    private $request;
    private $schedule;

    public function __construct(\Request $request)
    {
        $this->request = $request;
    }

    public function saveDataToFile($data, $fileName, $ext = 'txt')
    {
        $filePath = storage_path().'/app/ApiPlatform/'.$this->getPlatformId().'/'.$fileName.'/'.date('Y');
        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true);
        }
        $file = $filePath .'/'. date('Y-m-d-H-i') .'.'. $ext;
        //write json data into data.json file
        if (file_put_contents($file, $data)) {
            //echo 'Data successfully saved';
            return $data;
        }
        return false;
    }

    public function updatePendingProductProcessStatus($processStatusProduct,$processStatus)
    {
        if ($processStatus == self::PENDING_PRICE) {
            $processStatusProduct->transform(function ($pendingSku) {
                $pendingSku->process_status ^= self::PENDING_PRICE;
                $pendingSku->process_status |= self::COMPLETE_PRICE;
                $pendingSku->save();
            });
        }
        if ($processStatus == self::PENDING_INVENTORY) {
            $processStatusProduct->transform(function ($pendingSku) {
                $pendingSku->process_status ^= self::PENDING_INVENTORY;
                $pendingSku->process_status |= self::COMPLETE_INVENTORY;
                $pendingSku->save();
            });
        }
        $pendingPriceAndInventory = self::PENDING_PRICE | self::PENDING_INVENTORY;
        if ($processStatus == $pendingPriceAndInventory) {
            $processStatusProduct->transform(function ($pendingSku) {
                $pendingSku->process_status ^= self::PENDING_PRICE ^ self::PENDING_INVENTORY;
                $pendingSku->process_status |= self::COMPLETE_PRICE | self::COMPLETE_INVENTORY;
                $pendingSku->save();
            });
        }
    }

    public function removeApiFileSystem($expireDate)
    {
        $directoryList = array(
             "api_platform" => storage_path().'/app/ApiPlatform/',
             "xml_directory" => \Storage::disk('xml')->getDriver()->getAdapter()->getPathPrefix()
        );
        return $this->removeFileSystem($directoryList,$expireDate);
    }

    public function removeFileSystem($directoryList,$expireDate)
    {
        $expireDateString = strtotime($expireDate);
        foreach($directoryList as $directory){
            $allFiles = $this->findAllFiles($directory);
            if($allFiles){
                foreach($allFiles as $file){
                    if(filemtime($file) < $expireDateString){
                        $deleteFiles[]= $file;
                    }
                }
            }
            if(isset($deleteFiles)){
                \File::delete($deleteFiles);
            }
        }
    }

    public function findAllFiles($dir)
    {
        if(file_exists($dir)){
            $result = "";
            $root = scandir($dir);
            foreach($root as $value)
            {
                if($value === '.' || $value === '..') {continue;}
                if(is_file("$dir/$value")) {
                    $result[]="$dir/$value";continue;
                }
                if($subFile = $this->findAllFiles("$dir/$value")){
                    foreach($subFile as $value)
                    {
                        $result[]=$value;
                    }
                }
            }
            return $result;
        }
    }

    public function sendMailMessage($alertEmail, $subject, $message)
    {
        mail("{$alertEmail}, jimmy.gao@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
    }

    public function generateMultipleSheetsExcel($fileName,$cellDataArr,$path)
    {
        $excelFile = Excel::create($fileName, function($excel) use ($cellDataArr) {
            // Our first sheet
            foreach($cellDataArr as $key => $cellData){
                if($cellData){
                    $excel->sheet($key, function($sheet) use ($cellData) {
                        $sheet->rows($cellData);
                    });
                }
            }
        })->store("xlsx",$path);
        if($excelFile){
            $attachment = array("path"=>$this->getDateReportPath(),"file_name"=>$fileName.".xlsx");
            return $attachment;
        }
    }

    public function getWmsWarehouseSkuOrderedList($warehouseOrderGroups)
    {
        $warehouseSkuOrderedList = array();
        foreach($warehouseOrderGroups as $warehouseId => $warehouseOrderGroup){
            $warehouseMapping = WmsWarehouseMapping::where("warehouse_id","=",$warehouseId)->first();
            $platformPrefix = strtoupper(substr($warehouseMapping->platform_id, 3, 2));
            $platformAcronym = strtoupper(substr($warehouseMapping->platform_id, 5, 2));
            $countryCode = strtoupper(substr($warehouseMapping->platform_id, -2));
            $marketplaceId = $platformPrefix.$this->platformAcronym[$platformAcronym];
            $marketplaceSkuList = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
                        ->join('brand', 'brand.id', '=', 'product.brand_id')
                        ->join('sku_mapping', 'sku_mapping.sku', '=', 'product.sku')
                        ->where("marketplace_id","=",$marketplaceId)
                        ->where("country_id","=",$countryCode)
                        ->select('marketplace_sku_mapping.sku','marketplace_sku_mapping.marketplace_sku','product.name as product_name','brand.brand_name','sku_mapping.ext_sku as master_sku')
                        ->get()
                        ->toArray();
            $platformSkuOrderedList = $this->getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList);
            $warehouseSkuOrderedList[$warehouseId] = $platformSkuOrderedList;
        }
        return $warehouseSkuOrderedList;
    }

    private function getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList)
    {
        $skuOrderedQtyList = null;$platformSkuOrderedList = null;
        foreach($warehouseOrderGroup as $warehouseOrder){
            if(isset($skuOrderedQtyList[$warehouseOrder->prod_sku])){
                $skuOrderedQtyList[$warehouseOrder->prod_sku] +=$warehouseOrder->qty;
            }else{
                $skuOrderedQtyList[$warehouseOrder->prod_sku] =$warehouseOrder->qty;
            }
        }
        foreach($marketplaceSkuList as $marketplaceSku){
            if(isset($skuOrderedQtyList[$marketplaceSku["sku"]])){
                $marketplaceSku["qty"] = $skuOrderedQtyList[$marketplaceSku["sku"]];
            }
            //can set only order show the product mapping info
            $platformSkuOrderedList[$marketplaceSku["marketplace_sku"]] = $marketplaceSku;
        }
        return $platformSkuOrderedList;
    }

    public function checkWarehouseInventory($platformMarketOrder,$orginWarehouse)
    {
        $updateAction = true; $warehouseInventory = null; $updateObject = null;
        $newWarehouse = $orginWarehouse;
        foreach($platformMarketOrder->platformMarketOrderItem as $orderItem){
            $remainInventroy = $newWarehouse[$orderItem->sell_sku]["inventory"] - $orderItem->quantity_ordered;
            if($remainInventroy >= 0){
                $newWarehouse[$orderItem->sell_sku]["inventory"] = $remainInventroy;
                if(isset($updateObject[$orderItem->sell_sku])){
                    $updateObject[$orderItem->sell_sku]["qty"] += $orderItem->qty;
                }else{
                    $updateObject[$orderItem->sell_sku]["qty"] = $orderItem->qty;
                    $updateObject[$orderItem->sell_sku]["sku"] = $newWarehouse[$orderItem->sell_sku]["sku"];
                    $updateObject[$orderItem->sell_sku]["warehouse_id"] = $newWarehouse[$orderItem->sell_sku]["warehouse_id"];
                }
            }else{
                $updateAction = false;
            }
        }
        $warehouseInventory["warehouse"] = $updateAction ? $newWarehouse : $orginWarehouse;
        $warehouseInventory["updateObject"] = $updateObject;
        return $warehouseInventory;
    }

    public function updateWarehouseInventory($soNo,$updateObject)
    {
        foreach($updateObjects as $updateObject){
            $object = array(
                "ship_ref" => $soNo."-01",
                "sku" => $updateObject["sku"],
                "qty" => $updateObject["qty"],
                "type" => "C",
                "from_location" => $updateObject["warehouse_id"],
                "reason" => "LAZADA READY TO SHIP",
                "status" => "OT"
            );
            $invMovement = InvMovement::updateOrCreate(
                [
                    'ship_ref' => $soNo."-01",
                    'sku' => $updateObject["sku"],
                ],
                $object
            );
        }
    }

    public function deleteWarehouseInventory($soNo,$updateObject)
    {
        foreach($updateObjects as $updateObject){
            $object = array(
                "ship_ref" => $soNo."-01",
                "sku" => $updateObject["sku"]
            );
            $invMovement = InvMovement::delete($object);
        }
    }

    public function getSchedule()
    {
        return $this->schedule;
    }

    public function setSchedule($value)
    {
        $this->schedule = $value;
    }
}
