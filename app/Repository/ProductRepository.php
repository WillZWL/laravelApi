<?php

namespace App\Repository;

use App\Models\Product;

class ProductRepository
{
    public function getProductList($requestData)
    {
        $query = Product::whereIn('product.status', [1, 2]);
        $search = false;
        $marketplaceId = $requestData['marketplace_id'];
        $countryId = $requestData['country_id'];
        if (isset($marketplaceId)
            && $marketplaceId
            && isset($requestData['msku_map'])
            && $requestData['msku_map'] != "P"
        ) {
            if ($requestData['msku_map'] == "C") {
                $mjoin = "join";
            } else if ($requestData['msku_map'] == "N") {
                $mjoin = "leftJoin";
            }
            $query->$mjoin("marketplace_sku_mapping AS msm", "msm.sku", "=", \DB::raw("product.sku AND msm.marketplace_id = '{$marketplaceId}' AND msm.country_id = '{$countryId}'"));
            if ($requestData['msku_map'] == "N") {
                $query->whereNull("msm.id");
            }
            $search = true;
        }
        if (isset($requestData['merchant_id']) && $requestData['merchant_id']) {
            $query->join("merchant_product_mapping AS mpm", "mpm.sku", "=", "product.sku")
                ->where("mpm.merchant_id", $requestData['merchant_id']);
            $search = true;
        }
        if (isset($requestData['skus']) && $requestData['skus']) {
            $bulk_skus = str_replace(["\r\n", "|"], ",", $requestData['skus']);
            $skuArr = explode(",", $bulk_skus);
            if ($skuArr) {
                $query->whereIn('product.sku', $skuArr);
                $search = true;
            }
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
        if (isset($requestData['date_type']) && $requestData['date_type']) {
            if ($requestData['date_type'] == "C") {
                $dateFiled = "product.create_on";
            } else {
                $dateFiled = "product.modify_on";
            }
            if (isset($requestData['start_date']) && $requestData['start_date']) {
                $query->where($dateFiled, ">=", $requestData['start_date']);
            }
            if (isset($requestData['end_date']) && $requestData['end_date']) {
                $query->where($dateFiled, "<=", $requestData['end_date']);
            }
        }
        $query->groupBy('product.sku')->orderBy('product.create_on', "DESC");
        if (! $search) {
            $query->limit(2500);
        }
        return $query->select("product.*")
            ->get();
    }
}




