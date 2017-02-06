<?php

namespace App\Services;

use App\Models\MarketplaceContentField;
use App\Models\MarketplaceContentExport;
use App\Models\ExchangeRate;
use App\Models\MarketplaceSkuMapping;
use App\Repository\ProductRepository;

class MarketplaceContentExportService
{
    private $prodRepos = null;
    private $suppStatus = ['A' => 'Available', 'C' => 'Stock Constraint', 'D' => 'Discontinued', 'L' => 'Last Lot', 'O' => 'Temp Out of Stock'];
    private $webState = ['I' => 'In Stock', 'O' => 'Out Stock', 'P' => 'Pre-Order', 'A' => 'Arriving'];
    private $status = ['0' => 'Inactive', '1' => 'Created', '2' => 'Listed'];

    public function getMarketplaceContentExport($marketplace)
    {
        return MarketplaceContentExport::whereMarketplace($marketplace)
            ->with('MarketplaceContentField')
            ->whereStatus(1)
            ->orderBy('sort', 'ASC')
            ->get();
    }

    public function setting($data)
    {
        $marketplaceContentExports = MarketplaceContentExport::whereMarketplace($data['marketplace'])->get();
        if ($marketplaceContentExports) {
            foreach ($marketplaceContentExports as $marketplaceContentExport) {
                $marketplaceContentExport->sort = 0;
                $marketplaceContentExport->status = 0;
                $marketplaceContentExport->save();
            }
        }

        if (isset($data['field_value']) && isset($data['marketplace'])) {
            foreach ($data['field_value'] as $sort => $value) {
                \Log::info($sort);
                MarketplaceContentExport::updateOrCreate(['marketplace'=> $data['marketplace'], 'field_value' => $value], ['sort'=>$sort, 'status'=>1]);
            }
        }

        $marketplaceContentExports =  MarketplaceContentExport::whereMarketplace($data['marketplace'])
            ->with('MarketplaceContentField')
            ->whereStatus(1)
            ->orderBy('sort', 'ASC')
            ->get();
        if ($marketplaceContentExports) {
            foreach ($marketplaceContentExports as $marketplaceContentExport) {
                $marketplaceContentExport->field_name = $marketplaceContentExport->marketplaceContentField->name;
            }

            return ['success' => true, 'marketplace_content_export' => $marketplaceContentExports, 'msg' => 'Save marketplace content export field success'];
        } else {
            return ['fialed' => true, 'msg' => 'Currently no marketplace content export field'];
        }
    }

    public function download($requestData)
    {
        $fileName = strtoupper($requestData['marketplace']) ."_". $requestData['marketplace_id'] ."_". $requestData['country_id'] ."_PROUDUCT_FEED_". date('YmdHis');
        $sheetName = strtoupper($requestData['marketplace']) ." - ". $requestData['marketplace_id'] ." - ". $requestData['country_id'];
        $cellData = $this->getMarketplaceContentData($requestData);
        if (! $cellData) {
            $cellData[][] = "No data available for ".$requestData['marketplace_id']." (".$requestData['country_id'].")";
        }
        $extend = $requestData['extend'] ?: 'xlsx';
        \Excel::create($fileName, function ($excel) use ($cellData, $sheetName) {
            $excel->sheet($sheetName, function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export($extend);
    }

    public function getMarketplaceContentData($requestData) {
        if ($contentExportCollection = $this->getContentExportCollection($requestData['marketplace'])) {
            $productList = $this->getProdRepos()->getProductList($requestData);
            if (! $productList->isEmpty()) {
                $fieldSortCollection = $contentExportCollection['fields'];
                $fieldNameCollection = $contentExportCollection['fieldNames'];
                $prodCollection[] = $this->prodItemReAdjustSort($fieldNameCollection, $fieldSortCollection);
                foreach ($productList as $product) {
                    $prodItem = $this->getProdItem($product, $fieldSortCollection, $requestData);
                    $newProdItem = $this->prodItemReAdjustSort($prodItem, $fieldSortCollection);
                    $prodCollection[] = $newProdItem;
                }
                return $prodCollection;
            }
        }
    }

    public function getProdItem($product, $fieldSortCollection, $requestData)
    {
        $prodItem = [];
        $prodInfo = $this->getProdInfo($product, $fieldSortCollection, $requestData);
        $prodItem = array_merge($prodItem, $prodInfo);
        $marketplaceProdInfo = $this->getMarketplaceProduct($product, $fieldSortCollection, $requestData);
        $prodItem = array_merge($prodItem, $marketplaceProdInfo);
        $merchantProdMapInfo = $this->getMerchantProdMapInfo($product, $fieldSortCollection);
        $prodItem = array_merge($prodItem, $merchantProdMapInfo);
        $suppProdInfo = $this->supplierProdInfo($product, $fieldSortCollection);
        $prodItem = array_merge($prodItem, $suppProdInfo);
        return $prodItem;
    }

    public function getMarketplaceProduct($product, $fieldSortCollection, $requestData)
    {
        $marketplaceProdInfo = [];
        $marketplaceSkuMapField = [
            'marketplace_sku',
            'marketplace_id',
            'country_id',
            'mp_category_id',
            'mp_category_name',
            'mp_sub_category_id',
            'mp_sub_category_name',
            'inventory',
            'price',
            'delivery_type',
            'currency',
        ];
        $mSkuProd = null;
        if (array_intersect($marketplaceSkuMapField, array_flip($fieldSortCollection))) {
            $mSkuProd = MarketplaceSkuMapping::whereSku($product->sku)
                ->where("marketplace_id", $requestData['marketplace_id'])
                ->where("country_id", $requestData['country_id'])
                ->whereStatus(1)
                ->first();
        }
        if ($this->inArray('marketplace_sku', $fieldSortCollection)) {
            $marketplaceProdInfo['marketplace_sku'] = $mSkuProd ? $mSkuProd->marketplace_sku : null;
        }
        if ($this->inArray('marketplace_id', $fieldSortCollection)) {
            $marketplaceProdInfo['marketplace_id'] = $mSkuProd ? $mSkuProd->marketplace_id : null;
        }
        if ($this->inArray('country_id', $fieldSortCollection)) {
            $marketplaceProdInfo['country_id'] = $mSkuProd ? $mSkuProd->country_id : null;
        }
        if ($this->inArray('mp_category_id', $fieldSortCollection)) {
            $marketplaceProdInfo['mp_category_id'] = $mSkuProd ? $mSkuProd->mp_category_id : null;
        }
        if ($this->inArray('mp_category_name', $fieldSortCollection)) {
            $mpCategory = $mSkuProd ? $mSkuProd->mpCategory : null;
            $marketplaceProdInfo['mp_category_name'] = $mpCategory ? $mpCategory->name : null;
        }
        if ($this->inArray('mp_sub_category_id', $fieldSortCollection)) {
            $marketplaceProdInfo['mp_sub_category_id'] = $mSkuProd ? $mSkuProd->mp_sub_category_id : null;
        }
        if ($this->inArray('mp_sub_category_name', $fieldSortCollection)) {
            $mpSubCategory = $mSkuProd ? $mSkuProd->mpSubCategory : null;
            $marketplaceProdInfo['mp_sub_category_name'] = $mpSubCategory ? $mpSubCategory->name : null;
        }
        if ($this->inArray('inventory', $fieldSortCollection)) {
            $marketplaceProdInfo['inventory'] = $mSkuProd ? $mSkuProd->inventory : null;
        }
        if ($this->inArray('price', $fieldSortCollection)) {
            $marketplaceProdInfo['price'] = $mSkuProd ? $mSkuProd->price : null;
        }
        if ($this->inArray('delivery_type', $fieldSortCollection)) {
            $marketplaceProdInfo['delivery_type'] = $mSkuProd ? $mSkuProd->delivery_type : null;
        }
        if ($this->inArray('currency', $fieldSortCollection)) {
            $marketplaceProdInfo['currency'] = $mSkuProd ? $mSkuProd->currency : null;
        }
        return $marketplaceProdInfo;
    }

    public function getProdInfo($product, $fieldSortCollection = [], $requestData = [])
    {
        $prodInfo = [];
        if ($this->inArray('sku', $fieldSortCollection)) {
            $prodInfo['sku'] = $product->sku;
        }
        if ($this->inArray('name', $fieldSortCollection)) {
            $prodInfo['name'] = $product->name;
        }
        if ($this->inArray('version_id', $fieldSortCollection)) {
            $prodInfo['version_id'] = $product->version_id;
        }
        if ($this->inArray('version_name', $fieldSortCollection)) {
            $prodInfo['version_name'] = $product->version ? $product->version->desc : null;
        }
        if ($this->inArray('colour_id', $fieldSortCollection)) {
            $prodInfo['colour_id'] = $product->colour_id;
        }
        if ($this->inArray('colour_name', $fieldSortCollection)) {
            $prodInfo['colour_name'] = $product->colour ? $product->colour->name : null;
        }
        if ($this->inArray('brand_id', $fieldSortCollection)) {
            $prodInfo['brand_id'] = $product->brand_id;
        }
        if ($this->inArray('brand_name', $fieldSortCollection)) {
            $prodInfo['brand_name'] = $product->brand ? $product->brand->brand_name: null;
        }
        if ($this->inArray('cat_id', $fieldSortCollection)) {
            $prodInfo['cat_id'] = $product->cat_id;
        }
        if ($this->inArray('cat_name', $fieldSortCollection)) {
            $prodInfo['cat_name'] = $product->category ? $product->category->name : null;
        }
        if ($this->inArray('sub_cat_id', $fieldSortCollection)) {
            $prodInfo['sub_cat_id'] = $product->sub_cat_id;
        }
        if ($this->inArray('sub_cat_name', $fieldSortCollection)) {
            $prodInfo['sub_cat_name'] = $product->subCategory ? $product->subCategory->name : null;
        }
        if ($this->inArray('sub_sub_cat_id', $fieldSortCollection)) {
            $prodInfo['sub_sub_cat_id'] = $product->sub_sub_cat_id;
        }
        if ($this->inArray('sub_sub_cat_name', $fieldSortCollection)) {
            $prodInfo['sub_sub_cat_name'] = $product->subSubCategory ? $product->subSubCategory->name : null;
        }
        if ($this->inArray('hscode_cat_id', $fieldSortCollection)) {
            $prodInfo['hscode_cat_id'] = $product->hscode_cat_id;
        }
        if ($this->inArray('hscode_cat_name', $fieldSortCollection)) {
            $prodInfo['hscode_cat_name'] = $product->hscodeCategory ? $product->hscodeCategory->name : null;
        }
        if ($this->inArray('hs_code', $fieldSortCollection)) {
            $prodInfo['hs_code'] = $this->getCustomClassCode($product, $requestData);
        }
        if ($this->inArray('declared_desc', $fieldSortCollection)) {
            $prodInfo['declared_desc'] = $product->declared_desc;
        }
        if ($this->inArray('ean', $fieldSortCollection)) {
            $prodInfo['ean'] = $product->ean;
        }
        if ($this->inArray('asin', $fieldSortCollection)) {
            $prodInfo['asin'] = $product->asin;
        }
        if ($this->inArray('upc', $fieldSortCollection)) {
            $prodInfo['upc'] = $product->upc;
        }
        if ($this->inArray('mpn', $fieldSortCollection)) {
            $prodInfo['mpn'] = $product->mpn;
        }
        if ($this->inArray('isbn', $fieldSortCollection)) {
            $prodInfo['isbn'] = $product->isbn;
        }
        if ($this->inArray('harmonized_code', $fieldSortCollection)) {
            $prodInfo['harmonized_code'] = $product->harmonized_code;
        }
        if ($this->inArray('warranty_in_month', $fieldSortCollection)) {
            $prodInfo['warranty_in_month'] = $product->warranty_in_month;
        }
        if ($this->inArray('condtions', $fieldSortCollection)) {
            $prodInfo['condtions'] = $product->condtions;
        }
        if ($this->inArray('fragile', $fieldSortCollection)) {
            $prodInfo['fragile'] = $product->fragile;
        }
        if ($this->inArray('packed', $fieldSortCollection)) {
            $prodInfo['packed'] = $product->packed;
        }
        if ($this->inArray('battery', $fieldSortCollection)) {
            $prodInfo['battery'] = $product->battery;
        }
        if ($this->inArray('vol_weight', $fieldSortCollection)) {
            $prodInfo['vol_weight'] = $product->vol_weight;
        }
        if ($this->inArray('weight', $fieldSortCollection)) {
            $prodInfo['weight'] = $product->weight;
        }
        if ($this->inArray('prod_length', $fieldSortCollection)) {
            $prodInfo['prod_length'] = $product->length;
        }
        if ($this->inArray('prod_width', $fieldSortCollection)) {
            $prodInfo['prod_width'] = $product->width;
        }
        if ($this->inArray('prod_height', $fieldSortCollection)) {
            $prodInfo['prod_height'] = $product->height;
        }
        if ($this->inArray('default_ship_to_warehouse', $fieldSortCollection)) {
            $prodInfo['default_ship_to_warehouse'] = $product->default_ship_to_warehouse;
        }
        if ($this->inArray('website_status', $fieldSortCollection)) {
            $prodInfo['website_status'] = $this->webState[$product->website_status] ?: null;
        }
        if ($this->inArray('status', $fieldSortCollection)) {
            $prodInfo['status'] = $this->status[$product->status] ?: null;
        }

        $prodFeaturInfo = $this->getProductFeatures($product, $fieldSortCollection);
        $prodInfo = array_merge($prodInfo, $prodFeaturInfo);
        $prodContInfo = $this->getProdContInfo($product, $fieldSortCollection);
        $prodInfo = array_merge($prodInfo, $prodContInfo);

        return $prodInfo;
    }

    public function getMerchantProdMapInfo($product, $fieldSortCollection)
    {
        $merchantProdMapInfo = [];
        $merchantProdMap = [
            'merchant_id',
            'merchant_sku',
            'merchant_name',

        ];
        if (array_intersect($merchantProdMap, array_flip($fieldSortCollection))) {
            $merchantProductMapping = $product->merchantProductMapping;
        }
        if ($this->inArray('merchant_id', $fieldSortCollection)) {
            $merchantProdMapInfo['merchant_id'] = $merchantProductMapping ? $merchantProductMapping->merchant_id : null;
        }
        if ($this->inArray('merchant_sku', $fieldSortCollection)) {
            $merchantProdMapInfo['merchant_sku'] = $merchantProductMapping ? $merchantProductMapping->merchant_sku : null;
        }
        if ($this->inArray('merchant_name', $fieldSortCollection)) {
            $merchantProdMapInfo['merchant_name'] = $merchantProductMapping ? $merchantProductMapping->merchant->merchant_name : null;
        }
        return $merchantProdMapInfo;
    }

    public function supplierProdInfo($product, $fieldSortCollection)
    {
        $suppProdInfo = [];
        $suppProd = [
            'supplier_id',
            'supplier_name',
            'currency_id',
            'cost',
            'product_cost_hkd',
            'declared_desc',
            'declared_value',
            'declared_value_currency_id',
            'supplier_status',
        ];
        if (array_intersect($suppProd, array_flip($fieldSortCollection))) {
            $supplierProduct = $product->supplierProduct;
        }
        if ($this->inArray('supplier_id', $fieldSortCollection)) {
            $suppProdInfo['supplier_id'] = $supplierProduct ? $supplierProduct->supplier_id : null;
        }
        if ($this->inArray('supplier_name', $fieldSortCollection)) {
            $supplier = $supplierProduct ? $supplierProduct->supplier : null;
            $suppProdInfo['supplier_name'] = $supplier ? $supplier->name: null;
        }
        if ($this->inArray('cost', $fieldSortCollection)) {
            $suppProdInfo['cost'] = $supplierProduct ? ($supplierProduct->cost . "(". $supplierProduct->currency_id .")") : null;
        }
        if ($this->inArray('product_cost_hkd', $fieldSortCollection)) {
            $suppProdInfo['product_cost_hkd'] = $supplierProduct ? $supplierProduct->pricehkd : null;
        }
        if ($this->inArray('declared_value', $fieldSortCollection)) {
            $suppProdInfo['declared_value'] = $supplierProduct ? $supplierProduct->declared_value : null;
        }
        if ($this->inArray('declared_value_currency', $fieldSortCollection)) {
            $suppProdInfo['declared_value_currency'] = $supplierProduct ? $supplierProduct->declared_value_currency_id : null;
        }
        if ($this->inArray('supplier_status', $fieldSortCollection)) {
            $supplierStatus = null;
            if ($supplierProduct) {
                if ($supplierProduct->supplier_status)
                    $supplierStatus = $this->suppStatus[$supplierProduct->supplier_status];
            }
            $suppProdInfo['supplier_status'] = $supplierStatus ?: null;
        }
        return $suppProdInfo;
    }

    public function getProdContInfo($product, $fieldSortCollection, $lang = 'en')
    {
        $prodContInfo = [];
        $prodCont = [
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
        if (array_intersect($prodCont, array_flip($fieldSortCollection))) {
            $prodContent = $product->productContents()
                ->whereLangId($lang)
                ->first();
        }
        if ($this->inArray('model_1', $fieldSortCollection)) {
            $prodContInfo['model_1'] = $prodContent ? $prodContent->model_1 : null;
        }
        if ($this->inArray('model_2', $fieldSortCollection)) {
            $prodContInfo['model_2'] = $prodContent ? $prodContent->model_2 : null;
        }
        if ($this->inArray('model_3', $fieldSortCollection)) {
            $prodContInfo['model_3'] = $prodContent ? $prodContent->model_3 : null;
        }
        if ($this->inArray('model_4', $fieldSortCollection)) {
            $prodContInfo['model_4'] = $prodContent ? $prodContent->model_4 : null;
        }
        if ($this->inArray('model_5', $fieldSortCollection)) {
            $prodContInfo['model_5'] = $prodContent ? $prodContent->model_5 : null;
        }
        if ($this->inArray('prod_name', $fieldSortCollection)) {
            $prodContInfo['prod_name'] = $prodContent ? $prodContent->prod_name : null;
        }
        if ($this->inArray('keywords', $fieldSortCollection)) {
            $prodContInfo['keywords'] = $prodContent ? $prodContent->keywords : null;
        }
        if ($this->inArray('contents', $fieldSortCollection)) {
            $prodContInfo['contents'] = $prodContent ? $prodContent->contents : null;
        }
        if ($this->inArray('short_desc', $fieldSortCollection)) {
            $prodContInfo['short_desc'] = $prodContent ? $prodContent->short_desc : null;
        }
        if ($this->inArray('detail_desc', $fieldSortCollection)) {
            $prodContInfo['detail_desc'] = $prodContent ? $prodContent->detail_desc : null;
        }
        return $prodContInfo;
    }

    public function getProductFeatures($product, $fieldSortCollection)
    {
        $prodFeaturInfo = [];
        $features = [
            'prod_features_point_1',
            'prod_features_point_2',
            'prod_features_point_3',
            'prod_features_point_4',
            'prod_features_point_5',
            'prod_features_point_6'
        ];
        $featureArr = [];
        if (array_intersect($features, array_flip($fieldSortCollection))) {
            $prodFeatures = $product->productFeatures()
                ->whereEsgSku($product->sku)
                ->whereStatus(1)
                ->get();
            $i = 1;
            foreach ($prodFeatures as $prodFeature) {
                $featureArr[$i] = $prodFeature->feature;
                $i++;
            }
        }
        if ($this->inArray('prod_features_point_1', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_1'] = isset($featureArr[1]) ? $featureArr[1] : null;
        }
        if ($this->inArray('prod_features_point_2', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_2'] = isset($featureArr[2]) ? $featureArr[2] : null;
        }
        if ($this->inArray('prod_features_point_3', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_3'] = isset($featureArr[3]) ? $featureArr[3] : null;
        }
        if ($this->inArray('prod_features_point_4', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_4'] = isset($featureArr[4]) ? $featureArr[4] : null;
        }
        if ($this->inArray('prod_features_point_5', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_5'] = isset($featureArr[5]) ? $featureArr[5] : null;
        }
        if ($this->inArray('prod_features_point_6', $fieldSortCollection)) {
            $prodFeaturInfo['prod_features_point_6'] = isset($featureArr[6]) ? $featureArr[6] : null;
        }
        return $prodFeaturInfo;
    }

    public static function inArray($key, $array) {
        return isset($array[$key]);
    }

    public function getCustomClassCode($product, $requestData)
    {
        $customClass = $product->productCustomClassifications()
            ->whereCountryId($requestData['country_id'])
            ->first();
        return $customClass ? $customClass->code : null;
    }

    public function prodItemReAdjustSort($prodItem, $fieldSortCollection)
    {
        $newProdItem = [];
        $newFieldCollection = array_flip($fieldSortCollection);
        if ($newFieldCollection) {
            foreach ($newFieldCollection as $field) {
                $newProdItem[$field] = isset($prodItem[$field]) ? $prodItem[$field] : null;
            }
        }
        return $newProdItem;
    }

    public function getContentExportCollection($marketplace)
    {
        if ($exportFields = $this->getMarketplaceContentExport($marketplace)) {
            $fieldSortCollection = $fieldNameCollection = [];
            foreach ($exportFields as $exportField) {
                $fieldNameCollection[$exportField->field_value] = $exportField->marketplaceContentField ? $exportField->marketplaceContentField->name : $exportField->field_value;
                $fieldSortCollection[$exportField->field_value] = $exportField->sort;
            }
            return [
                'fields' => $fieldSortCollection,
                'fieldNames' => $fieldNameCollection
            ];
        }
    }

    public function getProdRepos()
    {
        if ($this->prodRepos === null) {
            $this->prodRepos = new ProductRepository();
        }
        return $this->prodRepos;
    }
}
