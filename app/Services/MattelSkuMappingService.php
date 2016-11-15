<?php

namespace App\Services;

use App\User;
use App\Models\StoreWarehouse;
use App\Models\MattelSkuMapping;
use Illuminate\Http\Request;
use Excel;
use App\Repository\PlatformMarketOrderRepository;

class MattelSkuMappingService
{
    use ApiPlatformTraitService;

    public function getMappings(Request $request)
    {
        $stores = User::find(\Authorizer::getResourceOwnerId())->stores()->pluck('store_id')->all();
        $warehouses = StoreWarehouse::whereIn('store_id', $stores)->pluck('warehouse_id')->all();
        $query = MattelSkuMapping::whereIn('warehouse_id', $warehouses);
        if ($request->get('mattel_sku')) {
            $query = $query->where('mattel_sku', '=', $request->get('mattel_sku'));
        }
        if ($request->get('dc_sku')) {
            $query = $query->where('dc_sku', '=', $request->get('dc_sku'));
        }
        return $query->paginate(30);
    }

    public function handleUploadFile($fileName = '')
    {
        if (file_exists($fileName)) {
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) {
                $sheetItems = $reader->all();
                $sheetItems = $sheetItems->toArray();
                array_filter($sheetItems);
                foreach ($sheetItems as $item) {
                    \DB::beginTransaction();
                    try {
                        $this->createMattelSkuMapping($item);
                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::rollBack();
                        mail('will.zhang@eservicesgroup.com', 'Mattel SKu Mapping Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                    }
                }
            });
        }
    }

    public function createMattelSkuMapping($item = [])
    {
        $object = [];
        $object['warehouse_id'] = trim($item['warehouse_id']);
        $object['mattel_sku'] = trim($item['mattel_sku']);
        $object['dc_sku'] = trim($item['dc_sku']);
        $mattelSkuMapping = MattelSkuMapping::updateOrCreate(
                [
                    'warehouse_id' => $object['warehouse_id'],
                    'mattel_sku' => $object['mattel_sku'],
                ],
                $object
            );
    }

    public function exportOrdersToExcel()
    {
        $stores = User::find(\Authorizer::getResourceOwnerId())->stores()->pluck('store_id')->all();
        $warehouses = StoreWarehouse::whereIn('store_id', $stores)->pluck('warehouse_id')->all();
        $lists = MattelSkuMapping::whereIn('warehouse_id', $warehouses)->get();
        $path = \Storage::disk('mattelSkuMappingUpload')->getDriver()->getAdapter()->getPathPrefix()."excel/";

        $cellData[] = [
            "WAREHOUSE ID",
            "Mattel SKU",
            "DC SKU"
        ];
        foreach ($lists as $sku) {
            $cellData[] = [
                "WAREHOUSE ID" => $sku->warehouse_id,
                "Mattel SKU" => $sku->mattel_sku,
                "DC SKU" => $sku->dc_sku
            ];
        };
        $cellDataArr['mapping'] = $cellData;
        $excelFileName = "Mattel_SKU_Mapping_Report";
        $excelFile = $this->generateMultipleSheetsExcel($excelFileName,$cellDataArr,$path);
        return $excelFile["path"].$excelFile["file_name"];
    }
}