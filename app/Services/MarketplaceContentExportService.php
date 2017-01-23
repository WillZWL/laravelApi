<?php

namespace App\Services;

use App\Models\MarketplaceContentField;
use App\Models\MarketplaceContentExport;
use App\Models\ExchangeRate;
use App\Repository\MarketplaceProductRepository;

class MarketplaceContentExportService
{
    private $marketProdRepos = null;

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
        $cellData = $this->getMarketplaceContentData($requestData);
        $fileName = strtoupper($requestData['marketplace'])
            ."_". $requestData['marketplace_id']
            ."_". $requestData['country_id']
            ."_PROUDUCT_FEED". date('_YmdHis_');
        $sheetName = strtoupper($requestData['marketplace'])
            ." - ". $requestData['marketplace_id']
            ." - ". $requestData['country_id'];

        if (! $cellData) {
            $cellData[][] = "No product data for ".$requestData['marketplace_id']." (".$requestData['country_id'].")";
        }
        \Excel::create($fileName, function ($excel) use ($cellData, $sheetName) {
            $excel->sheet($sheetName, function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }

    public function getMarketplaceContentData($requestData) {
        if ($contentExportCollection = $this->getContentExportCollection($requestData['marketplace'])) {
            if ($marketplaceProducts = $this->getMarketplaceProducts($requestData)) {
                $fieldCollection = $contentExportCollection['fields'];
                $fieldNameCollection = $contentExportCollection['fieldNames'];
                $prodCollection[] = $this->arraySort($fieldNameCollection, $fieldCollection);;
                foreach ($marketplaceProducts as $marketplaceProduct) {
                    $prodItem = [];
                    if (in_array('marketplace_sku', $fieldCollection)) {
                        $prodItem['marketplace_sku'] = $marketplaceProduct->marketplace_sku;
                    }
                    if (in_array('mp_category_id', $fieldCollection)) {
                        $prodItem['mp_category_id'] = $marketplaceProduct->mp_category_id;
                    }
                    if (in_array('mp_category_name', $fieldCollection)) {
                        $mpCategory = $marketplaceProduct->mpCategory;
                        $prodItem['mp_category_name'] = $mpCategory ? $mpCategory->name : null;
                    }
                    if (in_array('mp_sub_category_id', $fieldCollection)) {
                        $prodItem['mp_sub_category_id'] = $marketplaceProduct->mp_sub_category_id;
                    }
                    if (in_array('mp_sub_category_name', $fieldCollection)) {
                        $mpSubCategory = $marketplaceProduct->mpSubCategory;
                        $prodItem['mp_sub_category_name'] = $mpSubCategory ? $mpSubCategory->name : null;
                    }
                    if (in_array('inventory', $fieldCollection)) {
                        $prodItem['inventory'] = $marketplaceProduct->inventory;
                    }
                    if (in_array('price', $fieldCollection)) {
                        $prodItem['price'] = $marketplaceProduct->price;
                    }
                    if (in_array('currency_id', $fieldCollection)) {
                        $prodItem['currency_id'] = $marketplaceProduct->currency;
                    }
                    if (in_array('hs_code', $fieldCollection)) {
                        $prodItem['hs_code'] = $this->getCustomClassCode($marketplaceProduct, $requestData);
                    }

                    $prodInfo = $this->prodInfo($marketplaceProduct->product, $fieldCollection);
                    $prodItem = array_merge($prodItem, $prodInfo);

                    $merchantProdMapInfo = $this->getMerchantProdMapInfo($marketplaceProduct, $fieldCollection);
                    $prodItem = array_merge($prodItem, $merchantProdMapInfo);

                    $suppProdInfo = $this->supplierProdInfo($marketplaceProduct, $fieldCollection);
                    $prodItem = array_merge($prodItem, $suppProdInfo);

                    $newProdItem = $this->arraySort($prodItem, $fieldCollection);
                    $prodCollection[] = $newProdItem;
                }
                return $prodCollection;
            }
        }
    }

    public function arraySort($prodItem, $fieldCollection)
    {
        $newProdItem = [];
        if ($fieldCollection) {
            foreach ($fieldCollection as $field) {
                $newProdItem[$field] = $prodItem[$field] ?: null;
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

    public function getMerchantProdMapInfo($marketplaceProduct, $fieldCollection)
    {
        $merchantProdMapInfo = [];
        $merchantProdMap = [
            'merchant_id',
            'merchant_sku',
            'merchant_name',

        ];

        $prodMapContCollection = array_intersect($merchantProdMap, $fieldCollection);
        if ($prodMapContCollection) {
            $merchantProductMapping = $marketplaceProduct->merchantProductMapping;
        }
        if (in_array('merchant_id', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_id'] = $merchantProductMapping ? $merchantProductMapping->merchant_id : null;
        }
        if (in_array('merchant_sku', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_sku'] = $merchantProductMapping ? $merchantProductMapping->merchant_sku : null;
        }
        if (in_array('merchant_name', $prodMapContCollection)) {
            $merchantProdMapInfo['merchant_name'] = $merchantProductMapping ? $merchantProductMapping->merchant->merchant_name : null;
        }
        return $merchantProdMapInfo;
    }

    public function getCustomClassCode($marketplaceProduct, $requestData)
    {
        $customClass = $marketplaceProduct->product->productCustomClassifications()
            ->whereCountryId($requestData['country_id'])
            ->first();
        return $customClass ? $customClass->code : null;
    }

    public function supplierProdInfo($marketplaceProduct, $fieldCollection)
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
        $suppProdCollection = array_intersect($suppProd, $fieldCollection);
        if ($suppProdCollection) {
            $supplierProduct = $marketplaceProduct->supplierProduct;
        }
        if (in_array('supplier_id', $suppProdCollection)) {
            $suppProdInfo['supplier_id'] = $supplierProduct ? $supplierProduct->supplier_id : null;
        }
        if (in_array('supplier_name', $suppProdCollection)) {

            $suppProdInfo['supplier_name'] = $supplierProduct->Supplier ? $supplierProduct->Supplier->name: null;
        }
        // if (in_array('currency_id', $suppProdCollection)) {
        //     $suppProdInfo['currency_id'] = $supplierProduct ? $supplierProduct->currency_id : null;
        // }
        if (in_array('cost', $suppProdCollection)) {
            if ($supplierProduct->currency_id <> $marketplaceProduct->currency) {
                $rate = ExchangeRate::getRate($supplierProduct->currency_id, $marketplaceProduct->currency);
            } else {
                $rate = 1;
            }
            $suppProdInfo['cost'] = $supplierProduct ? $supplierProduct->cost * $rate : null;
        }
        if (in_array('product_cost_hkd', $suppProdCollection)) {
            $suppProdInfo['product_cost_hkd'] = $supplierProduct ? $supplierProduct->pricehkd : null;
        }
        if (in_array('declared_value', $suppProdCollection)) {
            $suppProdInfo['declared_value'] = $supplierProduct ? $supplierProduct->declared_value : null;
        }
        if (in_array('declared_value_currency_id', $suppProdCollection)) {
            $suppProdInfo['declared_value_currency_id'] = $supplierProduct ? $supplierProduct->declared_value_currency_id : null;
        }
        if (in_array('supplier_status', $suppProdCollection)) {
            $suppStatus = [
                'A' => 'Available',
                'C' => 'Stock Constraint',
                'D' => 'Discontinued',
                'L' => 'Last Lot',
                'O' => 'Temp Out of Stock'
            ];
            $suppProdInfo['supplier_status'] = $supplierProduct ? $suppStatus[$supplierProduct->supplier_status] : null;
        }
        return $suppProdInfo;
    }
    public function prodInfo($product, $fieldCollection)
    {
        $prodInfo = [];
        if (in_array('sku', $fieldCollection)) {
            $prodInfo['sku'] = $product->sku;
        }
        if (in_array('name', $fieldCollection)) {
            $prodInfo['name'] = $product->name;
        }
        if (in_array('version_id', $fieldCollection)) {
            $prodInfo['version_id'] = $product->version_id;
        }
        if (in_array('colour_id', $fieldCollection)) {
            $prodInfo['colour_id'] = $product->colour_id;
        }
        if (in_array('brand_id', $fieldCollection)) {
            $prodInfo['brand_id'] = $product->brand_id;
        }
        if (in_array('brand_name', $fieldCollection)) {
            $prodInfo['brand_name'] = $product->brand ? $product->brand->brand_name: null;
        }
        if (in_array('cat_id', $fieldCollection)) {
            $prodInfo['cat_id'] = $product->cat_id;
        }
        if (in_array('sub_cat_id', $fieldCollection)) {
            $prodInfo['sub_cat_id'] = $product->sub_cat_id;
        }
        if (in_array('sub_sub_cat_id', $fieldCollection)) {
            $prodInfo['sub_sub_cat_id'] = $product->sub_sub_cat_id;
        }
        if (in_array('hscode_cat_id', $fieldCollection)) {
            $prodInfo['hscode_cat_id'] = $product->hscode_cat_id;
        }
        if (in_array('declared_desc', $fieldCollection)) {
            $prodInfo['declared_desc'] = $product->declared_desc;
        }
        if (in_array('asin', $fieldCollection)) {
            $prodInfo['asin'] = $product->asin;
        }
        if (in_array('ean', $fieldCollection)) {
            $prodInfo['ean'] = $product->ean;
        }
        if (in_array('upc', $fieldCollection)) {
            $prodInfo['upc'] = $product->upc;
        }
        if (in_array('warranty_in_month', $fieldCollection)) {
            $prodInfo['warranty_in_month'] = $product->warranty_in_month;
        }
        if (in_array('condtions', $fieldCollection)) {
            $prodInfo['condtions'] = $product->condtions;
        }
        if (in_array('fragile', $fieldCollection)) {
            $prodInfo['fragile'] = $product->fragile;
        }
        if (in_array('packed', $fieldCollection)) {
            $prodInfo['packed'] = $product->packed;
        }
        if (in_array('battery', $fieldCollection)) {
            $prodInfo['battery'] = $product->battery;
        }
        if (in_array('vol_weight', $fieldCollection)) {
            $prodInfo['vol_weight'] = $product->vol_weight;
        }
        if (in_array('weight', $fieldCollection)) {
            $prodInfo['weight'] = $product->weight;
        }
        if (in_array('length', $fieldCollection)) {
            $prodInfo['length'] = $product->length;
        }
        if (in_array('width', $fieldCollection)) {
            $prodInfo['width'] = $product->width;
        }
        if (in_array('height', $fieldCollection)) {
            $prodInfo['height'] = $product->height;
        }
        if (in_array('default_ship_to_warehouse', $fieldCollection)) {
            $prodInfo['default_ship_to_warehouse'] = $product->default_ship_to_warehouse;
        }
        if (in_array('website_status', $fieldCollection)) {
            $webState = ['I' => 'In Stock', 'O' => 'Out Stock', 'P' => 'Pre-Order', 'A' => 'Arriving'];
            $prodInfo['website_status'] = $webState[$product->website_status] ?: null;
        }
        if (in_array('status', $fieldCollection)) {
            $status = ['0' => 'Inactive', '1' => 'Created', '2' => 'Listed'];
            $prodInfo['status'] = $status[$product->status] ?: null;
        }

        $prodFeaturInfo = $this->getProductFeatures($product, $fieldCollection);
        $prodInfo = array_merge($prodInfo, $prodFeaturInfo);

        $prodContInfo = $this->prodContInfo($product, $fieldCollection);
        $prodInfo = array_merge($prodInfo, $prodContInfo);

        return $prodInfo;
    }

    public function prodContInfo($product, $fieldCollection, $lang = 'en')
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
            // 'specification',
            // 'requirement',
        ];

        $prodContCollection = array_intersect($prodCont, $fieldCollection);
        if ($prodContCollection) {
            $productContent = $product->productContents()
                ->whereLangId($lang)
                ->first();
        }
        if (in_array('model_1', $prodContCollection)) {
            $prodContInfo['model_1'] = $productContent ? $productContent->model_1 : null;
        }
        if (in_array('model_2', $prodContCollection)) {
            $prodContInfo['model_2'] = $productContent ? $productContent->model_2 : null;
        }
        if (in_array('model_3', $prodContCollection)) {
            $prodContInfo['model_3'] = $productContent ? $productContent->model_3 : null;
        }
        if (in_array('model_4', $prodContCollection)) {
            $prodContInfo['model_4'] = $productContent ? $productContent->model_4 : null;
        }
        if (in_array('model_5', $prodContCollection)) {
            $prodContInfo['model_5'] = $productContent ? $productContent->model_5 : null;
        }
        if (in_array('prod_name', $prodContCollection)) {
            $prodContInfo['prod_name'] = $productContent ? $productContent->prod_name : null;
        }
        if (in_array('keywords', $prodContCollection)) {
            $prodContInfo['keywords'] = $productContent ? $productContent->keywords : null;
        }
        if (in_array('contents', $prodContCollection)) {
            $prodContInfo['contents'] = $productContent ? $productContent->contents : null;
        }
        if (in_array('short_desc', $prodContCollection)) {
            $prodContInfo['short_desc'] = $productContent ? $productContent->short_desc : null;
        }
        if (in_array('prod_desc', $prodContCollection)) {
            $prodContInfo['prod_desc'] = $productContent ? $productContent->detail_desc : null;
        }
        return $prodContInfo;
    }

    public function getProductFeatures($product, $fieldCollection)
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
        $featuresCollection = array_intersect($features, $fieldCollection);
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
        if (in_array('prod_features_point_1', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_1'] = $featureArr[1] ?: null;
        }
        if (in_array('prod_features_point_2', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_2'] = $featureArr[2] ?: null;
        }
        if (in_array('prod_features_point_3', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_3'] = $featureArr[3] ?: null;
        }
        if (in_array('prod_features_point_4', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_4'] = $featureArr[4] ?: null;
        }
        if (in_array('prod_features_point_5', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_5'] = $featureArr[5] ?: null;
        }
        if (in_array('prod_features_point_6', $featuresCollection)) {
            $prodFeaturInfo['prod_features_point_6'] = $featureArr[6] ?: null;
        }
        return $prodFeaturInfo;
    }

    public function getContentExportCollection($marketplace)
    {
        if ($exportFields = $this->getMarketplaceContentExport($marketplace)) {
            $fieldCollection = $fieldNameCollection = [];
            foreach ($exportFields as $exportField) {
                $fieldNameCollection[$exportField->field_value] = $exportField->marketplaceContentField->name;
                $fieldCollection[$exportField->sort] = $exportField->field_value;
            }
            return [
                'fields' => $fieldCollection,
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
