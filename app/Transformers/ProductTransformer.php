<?php

namespace App\Transformers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductCustomClassification;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    private $lang = 'en';

    public function __construct($lang)
    {
        if (isset($lang)) {
            $this->lang = $lang;
        }
    }

    public function transform(Product $product)
    {
        $productImages = ProductImage::prodImages($product->sku);

        $supProd = $product->supplierProduct()->first();

        $merchProdMap = $product->merchantProductMapping;

        $productContent = $product->productContents()->whereLangId($this->lang)->first();

        $productContentExtend = $product->productContentExtends()->whereLangId($this->lang)->first();

        $productFeatures = $product->productFeatures;

        $prodCustomClass = ProductCustomClassification::whereSku($product->sku)->first();

        return [
            'product_info' => [
                'sku' => $product->sku,
                'prod_grp_cd' => $product->prod_grp_cd,
                'prod_grp_cd_name' => $product->prod_grp_cd_name,
                'colour_id' => $product->colour_id,
                'version_id' => $product->version_id,
                'name' => $product->name,
                'declared_desc' => $product->declared_desc,
                'hscode_cat_id' => $product->hscode_cat_id,
                'cat_id' => $product->cat_id,
                'sub_cat_id' => $product->sub_cat_id,
                'sub_sub_cat_id' => $product->sub_sub_cat_id,
                'brand_id' => $product->brand_id,
                'clearance' => $product->clearance,
                'ean' => $product->ean,
                'asin' => $product->asin,
                'isbn' => $product->isbn,
                'harmonized_code' => $product->harmonized_code,
                'mpn' => $product->mpn,
                'upc' => $product->upc,
                'discount' => $product->discount,
                'proc_status' => $product->proc_status,
                'website_status' => $product->website_status,
                'sourcing_status' => $product->sourcing_status,
                'expected_delivery_date' => $product->expected_delivery_date,
                'warranty_in_month' => $product->warranty_in_month,
                'vol_weight' => $product->vol_weight,
                'weight' => $product->weight,
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
                'fragile' => $product->fragile,
                'packed' => $product->packed,
                'battery' => $product->battery,
                'sku_type' => $product->sku_type,
                'status' => $product->status,
                'condtions' => $product->condtions,
                'default_ship_to_warehouse' => $product->default_ship_to_warehouse,
            ],
            'hs_code' => $prodCustomClass ? $prodCustomClass->code : '',
            'merchant_sku_mapping' => [
                'merchant_id' => $merchProdMap ? $merchProdMap->merchant_id : '',
                'merchant_sku' => $merchProdMap ? $merchProdMap->merchant_sku : '',
            ],
            'images' => $productImages,
            'supplier_product' => [
                'prod_sku' => $supProd ? $supProd['prod_sku'] : '',
                'supplier_id' => $supProd ? $supProd['supplier_id'] : '',
                'currency_id' => $supProd ? $supProd['currency_id'] : '',
                'cost' => $supProd ? $supProd['cost'] : '',
                'pricehkd' => $supProd ? $supProd['pricehkd'] : '',
                'declared_value' => $supProd ? $supProd['declared_value'] : '',
                'declared_value_currency_id' => $supProd ? $supProd['declared_value_currency_id'] : '',
                'supplier_status' => $supProd ? $supProd['supplier_status'] : ''
            ],
            'product_content' => [
                'model_1' => $productContent ? $productContent->model_1 : '',
                'model_2' => $productContent ? $productContent->model_2 : '',
                'model_3' => $productContent ? $productContent->model_3 : '',
                'model_4' => $productContent ? $productContent->model_4 : '',
                'model_5' => $productContent ? $productContent->model_5 : '',
                'prod_name' => $productContent ? $productContent->prod_name : '',
                'keywords' => $productContent ? $productContent->keywords : '',
                'keywords_original' => $productContent ? $productContent->keywords_original : '',
                'lang_id' => $productContent ? $productContent->lang_id : $this->lang,
                'contents' => $productContent ? $productContent->contents : '',
                'contents_original' => $productContent ? $productContent->contents_original : '',
                'short_desc' => $productContent ? $productContent->short_desc : '',
                'detail_desc' => $productContent ? $productContent->detail_desc : '',
                'detail_desc_original' => $productContent ? $productContent->detail_desc_original : '',
                'specification' => $productContent ? $productContent->specification : '',
                'spec_original' => $productContent ? $productContent->spec_original : '',
                'requirement' => $productContent ? $productContent->requirement : '',
            ],
            'product_content_extend' => [
                'feature' => $productContentExtend ? $productContentExtend->feature : '',
                'feature_original' => $productContentExtend ? $productContentExtend->feature_original : '',
                'specification' => $productContentExtend ? $productContentExtend->specification : '',
                'spec_original' => $productContentExtend ? $productContentExtend->spec_original : '',
                'requirement' => $productContentExtend ? $productContentExtend->requirement : '',
            ],
            'product_features' => $productFeatures,
        ];
    }
}
