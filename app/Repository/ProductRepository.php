<?php

namespace App\Repository;

use App\Models\Product;

class ProductRepository
{
    public function getProductList($requestData)
    {
        $query = Product::where('status', ">=", 1)
            ->with("marketplaceSkuMapping");
        if (isset($requestData['skus']) && $requestData['skus']) {
            $query->whereIn('sku', $requestData['skus']);
        }
        if (isset($requestData['colour_id']) && $requestData['colour_id']) {
            $query->whereColourId($requestData['colour_id']);
        }
        if (isset($requestData['version_id']) && $requestData['version_id']) {
            $query->whereVersionId($requestData['version_id']);
        }
        if (isset($requestData['brand_id']) && $requestData['brand_id']) {
            $query->whereBrandId($requestData['brand_id']);
        }
        if (isset($requestData['cat_id']) && $requestData['cat_id']) {
            $query->whereCatId($requestData['cat_id']);
        }
        if (isset($requestData['sub_cat_id']) && $requestData['sub_cat_id']) {
            $query->whereSubCatId($requestData['sub_cat_id']);
        }
        if (isset($requestData['sub_sub_cat_id']) && $requestData['sub_sub_cat_id']) {
            $query->whereSubSubCatId($requestData['sub_sub_cat_id']);
        }
        if (isset($requestData['hscode_cat_id']) && $requestData['hscode_cat_id']) {
            $query->whereHscodeCatId($requestData['hscode_cat_id']);
        }

        return $query->groupBy('sku')
            ->limit(5000)
            ->get();
    }
}