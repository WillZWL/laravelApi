<?php

namespace App\Services;

use App\User;
use App\Models\StoreWarehouse;
use App\Models\MattelSkuMapping;
use Illuminate\Http\Request;
use Excel;

class MattelSkuMappingService
{
    public function getMappings(Request $request)
    {
        \Log::info($request);
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
            $mappingData = [];
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
        $object['warehouse_id'] = $item['warehouse_id'];
        $object['mattel_sku'] = $item['mattel_sku'];
        $object['dc_sku'] = $item['dc_sku'];
        $mattelSkuMapping = MattelSkuMapping::firstOrCreate(
                [
                    'warehouse_id' => $object['warehouse_id'],
                    'mattel_sku' => $object['mattel_sku'],
                    'dc_sku' => $object['dc_sku'],
                ],
                $object
            );
    }
}