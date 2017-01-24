<?php

namespace App\Services;

use App\Models\MarketplaceContentField;
use App\Models\MarketplaceContentExport;
use App\Models\ExchangeRate;
use App\Repository\MarketplaceProductRepository;

class MarketplaceContentExportService
{
    private $marketProdRepos = null;
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
        \Excel::create($fileName, function ($excel) use ($cellData, $sheetName) {
            $excel->sheet($sheetName, function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }

    public function getMarketplaceContentData($requestData) {
        if ($contentExportCollection = $this->getContentExportCollection($requestData['marketplace'])) {
            $marketplaceProducts = $this->getMarketplaceProducts($requestData);
            if ($marketplaceProducts->count()) {
                $fieldSortCollection = $contentExportCollection['fields'];
                $fieldNameCollection = $contentExportCollection['fieldNames'];
                $prodCollection[] = $this->prodItemReAdjustSort($fieldNameCollection, $fieldSortCollection);
                foreach ($marketplaceProducts as $marketplaceProduct) {
                    $prodItem = $this->getProdItem($marketplaceProduct, $fieldSortCollection, $requestData);
                    $newProdItem = $this->prodItemReAdjustSort($prodItem, $fieldSortCollection);
                    $prodCollection[] = $newProdItem;
                }
                return $prodCollection;
            }
        }
    }

    public function getProdItem($marketplaceProduct, $fieldSortCollection, $requestData)
    {
        $prodItem = [];
        if ($this->inArray('marketplace_sku', $fieldSortCollection)) {
            $prodItem['marketplace_sku'] = $marketplaceProduct->marketplace_sku;
        }
        if ($this->inArray('marketplace_id', $fieldSortCollection)) {
            $prodItem['marketplace_id'] = $marketplaceProduct->marketplace_id;
        }
        if ($this->inArray('country_id', $fieldSortCollection)) {
            $prodItem['country_id'] = $marketplaceProduct->country_id;
        }
        if ($this->inArray('mp_category_id', $fieldSortCollection)) {
            $prodItem['mp_category_id'] = $marketplaceProduct->mp_category_id;
        }
        if ($this->inArray('mp_category_name', $fieldSortCollection)) {
            $mpCategory = $marketplaceProduct->mpCategory;
            $prodItem['mp_category_name'] = $mpCategory ? $mpCategory->name : null;
        }
        if ($this->inArray('mp_sub_category_id', $fieldSortCollection)) {
            $prodItem['mp_sub_category_id'] = $marketplaceProduct->mp_sub_category_id;
        }
        if ($this->inArray('mp_sub_category_name', $fieldSortCollection)) {
            $mpSubCategory = $marketplaceProduct->mpSubCategory;
            $prodItem['mp_sub_category_name'] = $mpSubCategory ? $mpSubCategory->name : null;
        }
        if ($this->inArray('inventory', $fieldSortCollection)) {
            $prodItem['inventory'] = $marketplaceProduct->inventory;
        }
        if ($this->inArray('price', $fieldSortCollection)) {
            $prodItem['price'] = $marketplaceProduct->price;
        }
        if ($this->inArray('delivery_type_id', $fieldSortCollection)) {
            $prodItem['delivery_type_id'] = $marketplaceProduct->delivery_type;
        }
        if ($this->inArray('currency_id', $fieldSortCollection)) {
            $prodItem['currency_id'] = $marketplaceProduct->currency;
        }
        if ($this->inArray('hs_code', $fieldSortCollection)) {
            $prodItem['hs_code'] = $this->getCustomClassCode($marketplaceProduct, $requestData);
        }

        $prodInfo = $this->prodInfo($marketplaceProduct->product, $fieldSortCollection);
        $prodItem = array_merge($prodItem, $prodInfo);

        $merchantProdMapInfo = $this->getMerchantProdMapInfo($marketplaceProduct, $fieldSortCollection);
        $prodItem = array_merge($prodItem, $merchantProdMapInfo);

        $suppProdInfo = $this->supplierProdInfo($marketplaceProduct, $fieldSortCollection);
        $prodItem = array_merge($prodItem, $suppProdInfo);

        return $prodItem;
    }

    public function prodInfo($product, $fieldSortCollection)
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
        if ($this->inArray('colour_id', $fieldSortCollection)) {
            $prodInfo['colour_id'] = $product->colour_id;
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
            $prodInfo['cat_name'] = $product->category->name;
        }
        if ($this->inArray('sub_cat_id', $fieldSortCollection)) {
            $prodInfo['sub_cat_id'] = $product->sub_cat_id;
        }
        if ($this->inArray('sub_cat_name', $fieldSortCollection)) {
            $prodInfo['sub_cat_name'] = $product->subCategory->name;
        }
        if ($this->inArray('sub_sub_cat_id', $fieldSortCollection)) {
            $prodInfo['sub_sub_cat_id'] = $product->sub_sub_cat_id;
        }
        if ($this->inArray('sub_sub_cat_name', $fieldSortCollection)) {
            $prodInfo['sub_sub_cat_name'] = $product->subSubCategory->name;
        }
        if ($this->inArray('hscode_cat_id', $fieldSortCollection)) {
            $prodInfo['hscode_cat_id'] = $product->hscode_cat_id;
        }
        if ($this->inArray('hscode_cat_name', $fieldSortCollection)) {
            $prodInfo['hscode_cat_name'] = $product->hscodeCategory->name;
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
        if ($this->inArray('length', $fieldSortCollection)) {
            $prodInfo['length'] = $product->length;
        }
        if ($this->inArray('width', $fieldSortCollection)) {
            $prodInfo['width'] = $product->width;
        }
        if ($this->inArray('height', $fieldSortCollection)) {
            $prodInfo['height'] = $product->height;
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

        $prodContInfo = $this->prodContInfo($product, $fieldSortCollection);
        $prodInfo = array_merge($prodInfo, $prodContInfo);

        return $prodInfo;
    }

    public function getMerchantProdMapInfo($marketplaceProduct, $fieldSortCollection)
    {
        $merchantProdMapInfo = [];
        $merchantProdMap = [
            'merchant_id',
            'merchant_sku',
            'merchant_name',

        ];
        $prodMapContCollection = array_intersect($merchantProdMap, array_flip($fieldSortCollection));
        if ($prodMapContCollection) {
            $merchantProductMapping = $marketplaceProduct->merchantProductMapping;
        }
        if ($this->inArray('merchant_id', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_id'] = $merchantProductMapping ? $merchantProductMapping->merchant_id : null;
        }
        if ($this->inArray('merchant_sku', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_sku'] = $merchantProductMapping ? $merchantProductMapping->merchant_sku : null;
        }
        if ($this->inArray('merchant_name', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_name'] = $merchantProductMapping ? $merchantProductMapping->merchant->merchant_name : null;
        }
        return $merchantProdMapInfo;
    }

    public function supplierProdInfo($marketplaceProduct, $fieldSortCollection)
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
        $suppProdCollection = array_intersect($suppProd, array_flip($fieldSortCollection));
        if ($suppProdCollection) {
            $supplierProduct = $marketplaceProduct->supplierProduct;
        }
        if ($this->inArray('supplier_id', $suppProdCollection)) {
            $suppProdInfo['supplier_id'] = $supplierProduct ? $supplierProduct->supplier_id : null;
        }
        if ($this->inArray('supplier_name', $suppProdCollection)) {

            $suppProdInfo['supplier_name'] = $supplierProduct->Supplier ? $supplierProduct->Supplier->name: null;
        }
        if ($this->inArray('cost', $suppProdCollection)) {
            if ($supplierProduct->currency_id <> $marketplaceProduct->currency) {
                $rate = ExchangeRate::getRate($supplierProduct->currency_id, $marketplaceProduct->currency);
            } else {
                $rate = 1;
            }
            $suppProdInfo['cost'] = $supplierProduct ? $supplierProduct->cost * $rate : null;
        }
        if ($this->inArray('product_cost_hkd', $suppProdCollection)) {
            $suppProdInfo['product_cost_hkd'] = $supplierProduct ? $supplierProduct->pricehkd : null;
        }
        if ($this->inArray('declared_value', $suppProdCollection)) {
            $suppProdInfo['declared_value'] = $supplierProduct ? $supplierProduct->declared_value : null;
        }
        if ($this->inArray('declared_value_currency_id', $suppProdCollection)) {
            $suppProdInfo['declared_value_currency_id'] = $supplierProduct ? $supplierProduct->declared_value_currency_id : null;
        }
        if ($this->inArray('supplier_status', $suppProdCollection)) {
            if ($supplierProduct && isset($this->suppStatus[$supplierProduct->supplier_status])) {
                $supplierStatus = $this->suppStatus[$supplierProduct->supplier_status];
            }
            $suppProdInfo['supplier_status'] = $supplierStatus ?: null;
        }
        return $suppProdInfo;
    }

    public function prodContInfo($product, $fieldSortCollection, $lang = 'en')
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
            'prod_desc',
        ];

        $prodContCollection = array_intersect($prodCont, array_flip($fieldSortCollection));
        if ($prodContCollection) {
            $productContent = $product->productContents()
                ->whereLangId($lang)
                ->first();
        }
        if ($this->inArray('model_1', $prodContCollection)) {
            $prodContInfo['model_1'] = $productContent ? $productContent->model_1 : null;
        }
        if ($this->inArray('model_2', $prodContCollection)) {
            $prodContInfo['model_2'] = $productContent ? $productContent->model_2 : null;
        }
        if ($this->inArray('model_3', $prodContCollection)) {
            $prodContInfo['model_3'] = $productContent ? $productContent->model_3 : null;
        }
        if ($this->inArray('model_4', $prodContCollection)) {
            $prodContInfo['model_4'] = $productContent ? $productContent->model_4 : null;
        }
        if ($this->inArray('model_5', $prodContCollection)) {
            $prodContInfo['model_5'] = $productContent ? $productContent->model_5 : null;
        }
        if ($this->inArray('prod_name', $prodContCollection)) {
            $prodContInfo['prod_name'] = $productContent ? $productContent->prod_name : null;
        }
        if ($this->inArray('keywords', $prodContCollection)) {
            $prodContInfo['keywords'] = $productContent ? $productContent->keywords : null;
        }
        if ($this->inArray('contents', $prodContCollection)) {
            $prodContInfo['contents'] = $productContent ? $productContent->contents : null;
        }
        if ($this->inArray('short_desc', $prodContCollection)) {
            $prodContInfo['short_desc'] = $productContent ? $productContent->short_desc : null;
        }
        if ($this->inArray('prod_desc', $prodContCollection)) {
            $prodContInfo['prod_desc'] = $productContent ? $productContent->detail_desc : null;
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
        $featuresCollection = array_intersect($features, array_flip($fieldSortCollection));
        $featureArr = [];
        if ($featuresCollection) {
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
        if ($this->inArray('prod_features_point_1', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_1'] = $featureArr[1] ?: null;
        }
        if ($this->inArray('prod_features_point_2', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_2'] = $featureArr[2] ?: null;
        }
        if ($this->inArray('prod_features_point_3', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_3'] = $featureArr[3] ?: null;
        }
        if ($this->inArray('prod_features_point_4', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_4'] = $featureArr[4] ?: null;
        }
        if ($this->inArray('prod_features_point_5', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_5'] = $featureArr[5] ?: null;
        }
        if ($this->inArray('prod_features_point_6', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_6'] = $featureArr[6] ?: null;
        }
        return $prodFeaturInfo;
    }

    public static function inArray($key, $array) {
        return isset($array[$key]);
    }

    public function getCustomClassCode($marketplaceProduct, $requestData)
    {
        $customClass = $marketplaceProduct->product->productCustomClassifications()
            ->whereCountryId($requestData['country_id'])
            ->first();
        return $customClass ? $customClass->code : null;
    }

    public function prodItemReAdjustSort($prodItem, $fieldSortCollection)
    {
        $newFieldCollection = array_flip($fieldSortCollection);
        if ($newFieldCollection) {
            foreach ($newFieldCollection as $field) {
                $newProdItem[$field] = isset($prodItem[$field]) ? $prodItem[$field] : null;
            }
        }
        return $newProdItem;
    }

    public function getMarketplaceProducts($requestData)
    {
        return $this->getMarketProdRepos()->getMarketplaceProducts(
            $requestData['marketplace_id'],
            $requestData['country_id']
        );
    }

    public function getContentExportCollection($marketplace)
    {
        if ($exportFields = $this->getMarketplaceContentExport($marketplace)) {
            $fieldSortCollection = $fieldNameCollection = [];
            foreach ($exportFields as $exportField) {
                $fieldNameCollection[$exportField->field_value] = $exportField->marketplaceContentField->name;
                $fieldSortCollection[$exportField->field_value] = $exportField->sort;
            }
            return [
                'fields' => $fieldSortCollection,
                'fieldNames' => $fieldNameCollection
            ];
        }
    }

    public function getMarketProdRepos()
    {
        if ($this->marketProdRepos === null) {
            $this->marketProdRepos = new MarketplaceProductRepository();
        }
        return $this->marketProdRepos;
    }
}
