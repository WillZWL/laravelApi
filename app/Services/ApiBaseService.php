<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
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

    public function getPlatformSkuOrderedList($platformOrderGroups)
    {
        $platformSkuOrderedList = array();
        foreach($platformOrderGroups as $index =>  $platformOrderGroup){
            $platformPrefix = strtoupper(substr($index, 3, 2));
            $platformAcronym = strtoupper(substr($index, 5, 2));
            $countryCode = strtoupper(substr($index, -2));
            $marketplaceId = $platformPrefix.$this->platformAcronym[$platformAcronym];
            $skuOrderedList = $this->getSkuOrderedListByGroupOrder($platformOrderGroup,$marketplaceId,$countryCode);
            $platformSkuOrderedList[$marketplaceId.$countryCode] = $skuOrderedList;
        };
        return $platformSkuOrderedList; 
    }

    private function getSkuOrderedListByGroupOrder($platformOrderGroup,$marketplaceId,$countryCode)
    {
        $skuOrderedList = null;$newSkuOrderedList = null;
        $marketplaceSkuList = MarketplaceSkuMapping::where("marketplace_id","=",$marketplaceId)
                    ->where("country_id","=",$countryCode)
                    ->get()
                    ->pluck('marketplace_sku', 'sku')
                    ->toArray();
        foreach($platformOrderGroup as $platformOrder){
            if(isset($marketplaceSkuList[$platformOrder->prod_sku])){
                $skuOrderedList[$platformOrder->prod_sku]["marketplace_sku"]=$marketplaceSkuList[$platformOrder->prod_sku];
            }
            $skuOrderedList[$platformOrder->prod_sku]["sku"]=$platformOrder->prod_sku;
            if(isset($skuOrderedList[$platformOrder->prod_sku]["qty"])){
                $skuOrderedList[$platformOrder->prod_sku]["qty"] +=$platformOrder->qty;
            }else{
                $skuOrderedList[$platformOrder->prod_sku]["qty"] =$platformOrder->qty;
            }
            $skuOrderedList[$platformOrder->prod_sku]["product_name"]=$platformOrder->product_name;
            $skuOrderedList[$platformOrder->prod_sku]["brand_name"]=$platformOrder->brand_name;
            $skuOrderedList[$platformOrder->prod_sku]["master_sku"]=$platformOrder->master_sku;
        }
        foreach($skuOrderedList as $skuOrdered){
            if(isset($skuOrdered["marketplace_sku"]))
            $newSkuOrderedList[$skuOrdered["marketplace_sku"]]= $skuOrdered;
        }
        return $newSkuOrderedList;
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
