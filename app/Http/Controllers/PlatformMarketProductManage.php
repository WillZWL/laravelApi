<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformProductFactoryService;
use App\Services\PlatformMarketSkuMappingService;
use Config;

class  PlatformMarketProductManage extends Controller
{
    public function __construct(ApiPlatformProductFactoryService $apiPlatformProductFactoryService)
    {
        $this->apiPlatformProductFactoryService=$apiPlatformProductFactoryService;
    }

    public function getProductList(Request $request)
    {
        $storeName="BCLAZADAMY";
        //$schedule=$this->apiPlatformProductFactoryService->getStoreSchedule($storeName);
        $productList=$this->apiPlatformProductFactoryService->getProductList($storeName);
    }

    public function submitProductPrice(Request $request)
    {
        $order=$this->apiPlatformProductFactoryService->submitProductPrice();
    }

    public function submitProductInventory(Request $request)
    {
        $order=$this->apiPlatformProductFactoryService->submitProductInventory();
    }

    public function uploadMarketplacdeSkuMapping(Request $request)
    {
        $platform=$request->input("check");
        if($platform!="" && $request->hasFile('sku_file')){
            $file=$request->file('sku_file');
            $extension = $file->getClientOriginalExtension();
            $destinationPath = storage_path()."/marketplace-sku-mapping";
            $fileName=$file->getFilename().'.'.$extension;
            $request->file('sku_file')->move($destinationPath,$fileName);
            $stores=$this->getStores($platform);
            $platformMarketSkuMappingService=new PlatformMarketSkuMappingService($stores);
            $platformMarketSkuMappingService->initMarketplaceSkuMapping($fileName);
            return redirect('platform-market/upload-mapping');
        }
        return response()->view('platform-manager.uplaod-mapping'); 
    }

    public function getMarketplacdeSkuMappingFile($filename)
    {
        $file = \Storage::disk('skuMapping')->get($filename);
        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function getStores($bizType)
    {
        switch ($bizType) {
            case 'amazon':
                $stores = Config::get('amazon-mws.store');
                break;
            
            case 'lazada':
                $stores = Config::get('lazada-mws.store');
                break;
        }
        return $stores;
    }
}
