<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformProductFactoryService;
use App\Services\PlatformMarketSkuMappingService;
use App\Models\MpControl;
use Config;

class PlatformMarketProductManage extends Controller
{
    public function __construct(ApiPlatformProductFactoryService $apiPlatformProductFactoryService)
    {
        $this->apiPlatformProductFactoryService = $apiPlatformProductFactoryService;
    }

    public function getProductList(Request $request)
    {
        $storeName = 'BCLAZADAMY';
        //$schedule=$this->apiPlatformProductFactoryService->getStoreSchedule($storeName);
        $productList = $this->apiPlatformProductFactoryService->getProductList($storeName);
    }

    public function submitProductPrice(Request $request)
    {
        $order = $this->apiPlatformProductFactoryService->submitProductPrice();
    }

    public function submitProductInventory(Request $request)
    {
        $order = $this->apiPlatformProductFactoryService->submitProductInventory();
    }

    public function uploadMarketplacdeSkuMapping(Request $request)
    {
        $platform = $request->input('check');$message = "";
        if ($platform != '' && $request->hasFile('sku_file')) {
            $file = $request->file('sku_file');
            $extension = $file->getClientOriginalExtension();
            $destinationPath = storage_path().'/marketplace-sku-mapping';
            $fileName = $file->getFilename().'.'.$extension;
            $request->file('sku_file')->move($destinationPath, $fileName);

            $platformMarketSkuMappingService = new PlatformMarketSkuMappingService();

            $result = $platformMarketSkuMappingService->initMarketplaceSkuMapping($platform,$fileName);
            if(isset($result["error_sku"])){
                foreach($result["error_sku"] as $errorSku){
                    $message .= "SKU:" .$errorSku." Upload Error,Please Check Your File/r/n";
                }
            }else{
                $message = "Upload Marketplace SKU Mapping Success!";
            }
        }
        return \Redirect::back()->with('message',$message);
    }

    public function getMarketplacdeSkuMappingFile($filename)
    {
        $file = \Storage::disk('skuMapping')->get($filename);

        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function getStores($bizType)
    {
        return Config::get($bizType.'-mws.store');
    }

    public function exportLazadaPricingCsv(Request $request)
    {
        /* $data=array();
        $marketplaceArr=MpControl::where("marketplace_id","like","%LAZADA")->get();
        foreach($marketplaceArr as $marketplace){
            $data[$marketplace->marketplace_id][] = $marketplace->country_id;
        }*/
        $allMarketplace = $request->input('all_marketplace');
        $marketplace_id = $request->input('marketplace_id');
        if ($allMarketplace != '' || $marketplace_id != '') {
            $platformMarketSkuMappingService = new PlatformMarketSkuMappingService();
            $platformMarketSkuMappingService->exportLazadaPricingCsv($request);
            //return redirect('platform-market/upload-mapping');
        }

        return response()->view('platform-manager.export-mapping');
    }
}
