<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Product;
use App\Models\ProductFeatures;
use App\Models\Supplier;
use App\Models\SupplierProd;
use App\Models\Sequence;
use App\Models\MerchantProductMapping;
use App\Models\ProductContent;
use App\Models\ProductContentExtend;
use App\Models\ProductImage;
use App\Models\ProductCustomClassification;
use App\Models\ExchangeRate;
use Excel;

class ProductService
{

    public function getProduct($sku)
    {
        return Product::whereSku($sku)->first();
    }

    public function store($data)
    {
        $prod_grp_cd = $this->generateProdGrpCd();
        $data['prod_grp_cd'] = $prod_grp_cd;

        $sku = '';
        if ( $prod_grp_cd && isset($data['version_id']) && isset($data['colour_id']) ) {
            $sku = $prod_grp_cd .'-'. $data['version_id'] .'-'. $data['colour_id'];
            $data['sku'] = $sku;
        } else {
            return ['fialed' => true, 'msg' => 'Create failed, Cannot generate new SKU'];
        }

        if (! isset($data['status'])) {
            $data['status'] = 1;
        }

        $data['sku_type'] = 1;

        return $this->updateOrCreateProduct($data);
    }

    public function update($data, $sku)
    {
        if ($data['sku'] != $sku ) {
            return ['fialed' => true, 'msg' => 'Error, Submit an illegal product SKU'];
        }

        return $this->updateOrCreateProduct($data);
    }

    public function updateOrCreateProduct($data)
    {
        if (! isset($data['sku'])) {
            return ['fialed' => true, 'msg' => 'Failed, Cannot take product sku'];
        }

        $productInfo = [
            'sku',
            'prod_grp_cd',
            'prod_grp_cd_name',
            'colour_id',
            'version_id',
            'name',
            'declared_desc',
            'hscode_cat_id',
            'cat_id',
            'sub_cat_id',
            'sub_sub_cat_id',
            'brand_id',
            'clearance',
            'ean',
            'asin',
            'isbn',
            'harmonized_code',
            'mpn',
            'upc',
            'discount',
            'proc_status',
            'website_status',
            'sourcing_status',
            'expected_delivery_date',
            'warranty_in_month',
            'vol_weight',
            'weight',
            'length',
            'width',
            'height',
            'fragile',
            'packed',
            'battery',
            'sku_type',
            'status',
            'condtions',
            'default_ship_to_warehouse',
        ];

        $object = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $productInfo)) {
                $object[$key] = $value;
            }
        }

        $product = Product::updateOrCreate(['sku' => $data['sku']], $object);
        if (! $product) {
            return ['fialed' => true, 'msg' => 'Failed, Cannot take product info'];
        } else {
            if (isset($data['hs_code'])) {
                $countrys = Country::whereStatus(1)->get();
                if ($countrys) {
                    foreach ($countrys as $country) {
                        ProductCustomClassification::updateOrCreate(['sku' => $product->sku,'country_id' => $country->id], ['code' => $data['hs_code']]);
                    }
                }

            }

            return ['success' => true, 'product_info' => $product, 'msg' => 'Basic product info save success'];
        }
    }

    public function productMapping($data)
    {
        $product = Product::whereSku($data['sku'])->first();

        if (! $product) {
            return ['fialed' => true, 'msg' => 'Failed, By sku check product info fail'];
        }

        $object = [
            'merchant_id' => $data['merchant_id'],
            'merchant_sku' => $data['merchant_sku'],
            'colour_id' => $product->colour_id,
            'version_id' => $product->version_id,
        ];

        $result = MerchantProductMapping::updateOrCreate(['sku' => $data['sku']], $object);
        if ($result) {
            return ['success' => true, 'prod_map_info' => $result, 'msg' => 'Save merchant product mapping info success'];
        }
    }

    public function supplierProduct($data) {
        $supplier = Supplier::whereId($data['supplier_id'])->first();
        $hkdRate = ExchangeRate::getRate($supplier->currency_id, 'HKD');
        $object = [
            'currency_id' => $supplier->currency_id,
            'cost' => $data['cost'],
            'pricehkd' => round($data['cost'] * $hkdRate, 2),
            'declared_value' => $data['declared_value'],
            'declared_value_currency_id' => $supplier->currency_id,
            'order_default' => 1,
            'lead_day' => 0,
            'moq' => 1,
            'qty_per_carton' => 9999,
            'carton_per_pallet' => 9999,
        ];

        if (isset($data['supplier_status'])) {
            $object['supplier_status'] = $data['supplier_status'];
        } else {
            $object['supplier_status'] = 'A';
        }

        $result = SupplierProd::updateOrCreate(['prod_sku' => $data['sku'], 'supplier_id'=>$data['supplier_id']], $object);

        if ($result) {
            $this->updateOrCreateProduct($data);

            return ['success' => true, 'supplier_product' => $result, 'msg' => 'Save supplier product info success'];
        }
    }

    public function weightDimension($data) {
        return $this->updateOrCreateProduct($data);
    }

    public function productCode($data) {
        return $this->updateOrCreateProduct($data);
    }

    public function productContent($data) {
        $prodContent = [
            'model_1',
            'model_2',
            'model_3',
            'model_4',
            'model_5',
            'prod_name',
            'keywords',
            'contents',
            'short_desc',
            'detail_desc',
        ];

        $object = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $prodContent)) {
                $object[$key] = $value;
            }
        }

        if (isset($data['keywords'])) {
            $object['keywords_original'] = isset($data['keywords_original']) ? 1 : 0;
        }

        if (isset($data['contents'])) {
            $object['contents_original'] = isset($data['contents_original']) ? 1 : 0;
        }

        if (isset($data['detail_desc'])) {
            $object['detail_desc_original'] = isset($data['detail_desc_original']) ? 1 : 0;
        }

        $result = ProductContent::updateOrCreate(['prod_sku' => $data['sku'], 'lang_id' => $data['lang_id']], $object);

        if ($result) {
            return ['success' => true, 'product_content' => $result, 'msg' => 'Save product content info success'];
        }
    }

    public function productContentExtend($data) {
        $prodContent = [
            'requirement',
            'specification',
            'feature',
        ];

        $object = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $prodContent)) {
                $object[$key] = $value;
            }
        }

        if (isset($data['feature'])) {
            $object['feature_original'] = isset($data['feature_original']) ? 1 : 0;
        }

        if (isset($data['specification'])) {
            $object['spec_original'] = isset($data['spec_original']) ? 1 : 0;
        }

        $result = ProductContentExtend::updateOrCreate(['prod_sku' => $data['sku'], 'lang_id' => $data['lang_id']], $object);

        if ($result) {
            return ['success' => true, 'product_content_extend' => $result, 'msg' => 'Save product content extend info success'];
        }
    }



    public function productFeatures($data)
    {
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $key => $id) {
                $feature = $data['feature_'. $id];

                if (empty($feature)) {
                    ProductFeatures::whereId($id)->delete();
                } else {
                    $object = [
                        'esg_sku' => $data['sku'],
                        'feature' => $feature,
                    ];

                    ProductFeatures::updateOrCreate(['id' => $id], $object);

                }
            }
        }

        if (isset($data['add_feature'])) {
            foreach ($data['add_feature'] as $key => $feature) {
                if (empty($feature)) continue;

                $object = [
                    'esg_sku' => $data['sku'],
                    'feature' => $feature,
                ];

                ProductFeatures::firstOrCreate($object);
            }
        }

        $prodFeatures =  ProductFeatures::whereEsgSku($data['sku'])->get();
        if ($prodFeatures) {
            return ['success' => true, 'product_features' => $prodFeatures, 'msg' => 'Save product features success'];
        } else {
            return ['fialed' => true, 'msg' => 'Currently no input product features'];
        }
    }

    public function deleteImage($data)
    {
        $prodImg = ProductImage::whereId($data['id'])->whereSku($data['sku'])->first();

        if ($prodImg) {
            $img = $prodImg->sku ."_". $prodImg->id .".". $prodImg->image;
            if ($data['id'] == $prodImg->id) {
                if ($prodImg->delete()) {
                    @unlink(public_path() . "/product-images/". $img);
                    @unlink(public_path() . "/product-images/thumbnail/". $img);

                    return ['success' => true, 'msg' => 'Delete image is success'];
                } else {
                    return ['fialed' => true, 'msg' => 'Delete image is failed'];
                }
            }
        }
    }

    public function saveProductImage($data)
    {
        $sku = $data['prod_sku'];

        $num = ProductImage::whereSku($sku)->whereStatus(1)->select('sku', 'id', 'image', 'priority')->count();
        if ($num == 60) {
            return $response['files'][0]->error = "The maximum number of allow uploads has been exceeded";
        }

        $imgID = $this->generateImageID();

        try {
            $options = $this->allowOptions($imgID, $sku);

            $uploadResponse = new FileUploadService($options);
        } catch (\Exception $e) {
            mail('brave.liu@eservicesgroup.com', 'Product Upload Images Failed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
        }

        $response = $uploadResponse->response;
        if ($response && !isset($response['files'][0]->error)) {
            $extension = $response['files'][0]->extension;
            $priority = ProductImage::whereSku($sku)->max('priority');

            $object = [
                'id' => $imgID,
                'sku' => $sku,
                'image' => $extension,
                'priority' => $priority + 1,
                'alt_text' => $response['files'][0]->name,
                'status' => 1,
            ];

            ProductImage::firstOrCreate($object);

            $productImages = ProductImage::prodImages($sku);

            $response['product_images'] = $productImages;
        }

        return $response;
    }

    private function allowOptions($imgID, $sku)
    {
        $imagePath = '/product-images/';

        $uploadDir = public_path() . $imagePath;
        if (! file_exists($uploadDir))
            mkdir($uploadDir, 0755, true);

        $options = [];
        $options['inline_file_types'] = '/\.(gif|jpe?g|png)$/i';
        $options['accept_file_types'] = '/\.(gif|jpe?g|png)$/i';
        $options['image_file_types'] = '/\.(gif|jpe?g|png)$/i';
        $options['correct_image_extensions'] = true;
        $options['print_response'] = false;
        $options['upload_dir'] = $uploadDir .'/';
        $options['upload_url'] = url($imagePath). "/";
        $options['file_rename'] = $sku ."_". $imgID;

        return $options;
    }

    public function generateImageID()
    {
        // TODO: need add transaction.
        $sequence = Sequence::where('seq_name', '=', 'product_image')->first();
        $sequence->value += 1;
        $sequence->save();

        return $sequence->value;
    }

    public function handleUploadFile($fileName = '')
    {
        if (file_exists($fileName)) {
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) {
                $sheetItems = $reader->all();
                $sheetItems = $sheetItems->toArray();
                array_filter($sheetItems);
                foreach ($sheetItems as $item) {
                    if (trim($item['esg_sku'])) {
                        $item['sku'] = $item['esg_sku'];
                        $product = Product::whereSku($item['sku'])->first();
                    } else {
                        $prodGrpCd = $this->generateProdGrpCd();
                        $item['prod_grp_cd'] = $prodGrpCd;
                        $item['sku'] = $item['prod_grp_cd'] .'-'. $item['versionid'] .'-'. $item['colourid'];
                    }
                    if ($product or (empty(trim($item['esg_sku'])) && $item['sku'])) {
                        \DB::beginTransaction();
                        \DB::connection('mysql_esg')->beginTransaction();
                        try {
                            $this->createOrUpdateProduct($item);
                            $this->createOrUpdateProductFeatures($item);
                            $this->createOrUpdateSupplierProd($item);
                            $this->createOrUpdateMerchantProductMapping($item);
                            $this->createOrUpdateProductContent($item);
                            \DB::connection('mysql_esg')->commit();
                            \DB::commit();
                        } catch (\Exception $e) {
                            \DB::connection('mysql_esg')->rollBack();
                            \DB::rollBack();
                            mail('will.zhang@eservicesgroup.com', 'Product Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                        }
                    }

                }
            });
        }
    }

    private function createOrUpdateProduct($item = [])
    {
        $object = [];
        $object['sku'] = (string) $item['sku'];
        if (isset($item['prod_grp_cd'])) {
            $object['prod_grp_cd'] = (int) $item['prod_grp_cd'];
        }
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
        $product = Product::updateOrCreate([ 'sku' => $object['sku'] ], $object);
    }

    private function createOrUpdateProductFeatures($item = [])
    {
        $object = [];
        $object['esg_sku'] = (string) $item['sku'];
        ProductFeatures::where('esg_sku', '=', $object['esg_sku'])->delete();
        for ($i=1; $i <= 6; $i++) {
            $features_point = 'features_point_'.$i;
            if (trim($item[$features_point]) != '') {
                $object['feature'] = (string) trim($item[$features_point]);
                $productFeatures = ProductFeatures::firstOrCreate($object);
            }
        }
    }

    private function createOrUpdateSupplierProd($item = [])
    {
        $object = [];
        $object['prod_sku'] = (string) $item['sku'];
        $object['supplier_id'] = (int) $item['supplier_id'];
        SupplierProd::where('prod_sku', '=', $object['prod_sku'])->where('supplier_id', '=', $object['supplier_id'])->delete();
        $object['qty_per_carton'] = (int) $item['qtypercarton'];
        $object['carton_per_pallet'] = (int) $item['cartonperpallet'];
        $object['currency_id'] = (string) $item['currency'];
        $object['cost'] = (float) $item['cost'];
        $object['declared_value'] = (float) $item['declaredvalue'];
        $object['order_default'] = 1;
        $supplierProd = SupplierProd::firstOrCreate($object);
    }

    private function createOrUpdateMerchantProductMapping($item = [])
    {
        $object = [];
        $object['sku'] = (string) $item['sku'];
        $object['merchant_id'] = strtoupper($item['merchantid']);
        $object['merchant_sku'] = (string) $item['merchantsku'];
        $object['version_id'] = (string) $item['versionid'];
        $object['colour_id'] = (string) $item['colourid'];
        return $merchantProductMapping = MerchantProductMapping::firstOrCreate($object);
     }

     private function createOrUpdateProductContent($item = [])
     {
        $object = [];
        $object['prod_sku'] = (string) $item['sku'];
        $object['lang_id'] = 'en';
        $object['prod_name'] = (string) trim($item['productname']);
        $object['short_desc'] = (string) trim($item['detail_description']);
        $object['contents'] = (string) trim($item['detail_description']);
        $object['model_1'] = (string) trim($item['model_1']);
        $object['detail_desc'] = (string) trim($item['detail_description']);
        \Log::info($object);
        $productContent = ProductContent::updateOrCreate(['prod_sku' => $item['sku'], 'lang_id' => 'en'], $object);
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