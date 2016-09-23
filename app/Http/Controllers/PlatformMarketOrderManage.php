<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;
use App\Models\Marketplace;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use Config;

class PlatformMarketOrderManage extends Controller
{
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        $this->apiPlatformFactoryService = $apiPlatformFactoryService;
    }

    public function index(Request $request)
    {   
        return response()->view('platform-manager.index', $data);
    }

    public function uploadMarketplacdeSkuMapping(Request $request)
    {
        $platform = $request->input('check');
        if ($platform == 'lazada' && $request->hasFile('sku_file')) {
            $file = $request->file('sku_file');
            $extension = $file->getClientOriginalExtension();
            $destinationPath = storage_path().'/marketplace-sku-mapping';
            $fileName = $file->getFilename().'.'.$extension;
            $request->file('sku_file')->move($destinationPath, $fileName);
            $stores = Config::get('lazada-mws.store');
            $this->apiPlatformFactoryService->initMarketplaceSkuMapping($stores, $fileName);

            return redirect('platform-market/upload-mapping');
        }

        return response()->view('platform-manager.uplaod-mapping');
    }

    public function getMarketplacdeSkuMappingFile($filename)
    {
        $file = \Storage::disk('skuMapping')->get($filename);

        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
