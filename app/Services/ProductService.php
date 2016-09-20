<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductFeatures;
use App\Models\SupplierProd;
use App\Models\Sequence;
use App\Models\MerchantProductMapping;
use Excel;

class ProductService
{
    public function handleUploadFile($fileName = '')
    {
        // echo $fileName;
        if (file_exists($fileName)) {
            $productData = [];
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) {
                $sheetItems = $reader->all();
                $sheetItems = $sheetItems->toArray();
                array_filter($sheetItems);
                foreach ($sheetItems as $item) {
                    $prodGrpCd = $this->generateProdGrpCd();
                    $item['prod_grp_cd'] = $prodGrpCd;
                    $item['sku'] = $item['prod_grp_cd'] .'-'. $item['versionid'] .'-'. $item['colourid'];
                    \DB::beginTransaction();
                    \DB::connection('mysql_esg')->beginTransaction();
                    try {
                        $this->createProduct($item);
                        $this->createProductFeatures($item);
                        $this->createSupplierProd($item);
                        $this->createMerchantProductMapping($item);
                        \DB::connection('mysql_esg')->commit();
                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::connection('mysql_esg')->rollBack();
                        \DB::rollBack();
                        mail('will.zhang@eservicesgroup.com', 'Product Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                    }
                }
            });
        }
    }

    private function createProduct($item = [])
    {
        $object = [];
        $object['sku'] = (string) $item['sku'];
        $object['prod_grp_cd'] = (int) $item['prod_grp_cd'];
        $object['prod_grp_cd_name'] = (string) trim($item['productgrpname']);
        $object['colour_id'] = (string) trim($item['colourid']);
        $object['version_id'] = (string) trim($item['versionid']);
        $object['name'] = (string) trim($item['productname']);
        $object['declared_desc'] = (string) trim($item['declared_desc']);
        $object['hscode_cat_id'] = (int) $item['hscategory'];
        $object['cat_id'] = (int) $item['cat_id'];
        $object['sub_cat_id'] = (int) $item['sub_cat_id'];
        $object['sub_sub_cat_id'] = (int) $item['sub_sub_cat_id'];
        $object['brand_id'] = (int) $item['brand'];
        $object['ean'] = (string) $item['ean'];
        $object['mpn'] = (string) $item['mpn'];
        $object['upc'] = (string) $item['upc'];
        $object['vol_weight'] = (float) $item['volweight'];
        $object['weight'] = (float) $item['weight'];
        $object['length'] = (float) $item['length'];
        $object['width'] = (float) $item['width'];
        $object['height'] = (float) $item['height'];
        $object['fragile'] = (int) $item['fragile'];
        $object['battery'] = (int) $item['battery'];
        $object['sku_type'] = (int) $item['sku_type'];
        $product = Product::firstOrCreate($object);
    }

    private function createProductFeatures($item = [])
    {
        $object = [];
        $object['esg_sku'] = (string) $item['sku'];
        for ($i=1; $i <= 6; $i++) {
            $features_point = 'features_point_'.$i;
            if (trim($item[$features_point]) != '') {
                $object['feature'] = (string) trim($item[$features_point]);
                $productFeatures = ProductFeatures::firstOrCreate($object);
            }
        }
    }

    private function createSupplierProd($item = [])
    {
        $object = [];
        $object['prod_sku'] = (string) $item['sku'];
        $object['supplier_id'] = (int) $item['supplier_id'];
        $object['qty_per_carton'] = (int) $item['qtypercarton'];
        $object['carton_per_pallet'] = (int) $item['cartonperpallet'];
        $object['currency_id'] = (string) $item['currency'];
        $object['cost'] = (float) $item['cost'];
        $object['declared_value'] = (float) $item['declaredvalue'];
        $object['order_default'] = 1;
        $supplierProd = SupplierProd::firstOrCreate($object);
    }

    private function createMerchantProductMapping($item = [])
    {
        $object = [];
        $object['sku'] = (string) $item['sku'];
        $object['merchant_id'] = (string) $item['merchantid'];
        $object['merchant_sku'] = (string) $item['merchantsku'];
        $object['version_id'] = (string) $item['versionid'];
        $object['colour_id'] = (string) $item['colourid'];
        $merchantProductMapping = MerchantProductMapping::firstOrCreate($object);
     }

    /***
     * @return int local prod grp cd.
     */
    private function generateProdGrpCd()
    {
        // TODO: need add transaction.
        $sequence = Sequence::where('seq_name', '=', 'product')->first();
        $sequence->value += 1;
        $sequence->save();
        return $sequence->value;
    }
}