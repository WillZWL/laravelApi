<?php 

namespace App\Services;

use App\Models\Store;
/**
* 
*/
trait ApiPlatformTraitService
{
    public function getPlatformStore($platform)
    {
        $storeCode = strtoupper(substr($platform, 0,2));
        $marketplace = strtoupper(substr($platform, 2, -2));
        $country = strtoupper(substr($platform, -2));
        return Store::where('store_code', $storeCode)
                ->where('marketplace', $marketplace)
                ->where('country', $country)
                ->first();
    }

    public function saveDataToFile($data, $fileName, $ext = 'txt')
    {
        $filePath = storage_path().'/app/ApiPlatform/'.$this->getPlatformId().'/'.$fileName.'/'.date('Y');
        if (!file_exists($filePath)) {
            mkdir($filePath, 0775, true);
        }
        $file = $filePath .'/'. date('Y-m-d-H-i') .'.'. $ext;
        //write json data into data.json file
        if (file_put_contents($file, $data)) {
            //echo 'Data successfully saved';
            return $data;
        }
        return false;
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
        $excelFile = \Excel::create($fileName, function($excel) use ($cellDataArr) {
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
            $attachment = array("path" => $path,"file_name"=>$fileName.".xlsx");
            return $attachment;
        }
    }

    private function getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList)
    {
        $skuOrderedQtyList = null;$platformSkuOrderedList = null;
        foreach($warehouseOrderGroup as $warehouseOrder){
            if(isset($skuOrderedQtyList[$warehouseOrder["prod_sku"]])){
                $skuOrderedQtyList[$warehouseOrder["prod_sku"]] +=$warehouseOrder["qty"];
            }else{
                $skuOrderedQtyList[$warehouseOrder["prod_sku"]] =$warehouseOrder["qty"];
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

}