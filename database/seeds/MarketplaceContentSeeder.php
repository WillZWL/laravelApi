<?php

use Illuminate\Database\Seeder;

class MarketplaceContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('marketplace_content_export')->truncate();
        DB::table('marketplace_content_field')->truncate();

        DB::table("marketplace_content_field")->insert([
            'value'=>'marketplace_id',
            'name'=>'Marketplace ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'country_id',
            'name'=>'Country ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'marketplace_sku',
            'name'=>'Marketplace SKU',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'sku',
            'name'=>'ESG SKU',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'name',
            'name'=>'Product Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'version_id',
            'name'=>'Version ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'version_name',
            'name'=>'Version Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'colour_id',
            'name'=>'Colour ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'colour_name',
            'name'=>'Colour Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'brand_id',
            'name'=>'Brand ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'brand_name',
            'name'=>'Brand Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'cat_id',
            'name'=>'Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'cat_name',
            'name'=>'Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'sub_cat_id',
            'name'=>'Sub Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'sub_cat_name',
            'name'=>'Sub Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'sub_sub_cat_id',
            'name'=>'Sub Sub Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'sub_sub_cat_name',
            'name'=>'Sub Sub Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'hscode_cat_id',
            'name'=>'HS Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'hscode_cat_name',
            'name'=>'HS Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'hs_code',
            'name'=>'HS Code',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'currency',
            'name'=>'Currency',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'price',
            'name'=>'Price',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'inventory',
            'name'=>'Inventory',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'mp_category_id',
            'name'=>'Marketplace Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'mp_category_name',
            'name'=>'Marketplace Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'mp_sub_category_id',
            'name'=>'Marketplace Sub Category ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'mp_sub_category_name',
            'name'=>'Marketplace Sub Category',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'delivery_type',
            'name'=>'Delivery Type',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'warranty_in_month',
            'name'=>'Warranty In Month',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'condtions',
            'name'=>'Condtions',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'fragile',
            'name'=>'Fragile',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'packed',
            'name'=>'Packed',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'battery',
            'name'=>'Battery',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'vol_weight',
            'name'=>'Volumetric Weight (Kg)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'weight',
            'name'=>'Weight (Kg)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'length',
            'name'=>'Length (cm)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'width',
            'name'=>'Width (cm)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'height',
            'name'=>'Height (cm)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'ean',
            'name'=>'EAN',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'asin',
            'name'=>'ASIN',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'upc',
            'name'=>'UPC',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'mpn',
            'name'=>'MPN',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'isbn',
            'name'=>'ISBN',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'harmonized_code',
            'name'=>'Harmonized Code',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'default_ship_to_warehouse',
            'name'=>'DEFAULT Ship to Warehouse',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'model_1',
            'name'=>'Model 1',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'model_2',
            'name'=>'Model 2',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'model_3',
            'name'=>'Model 3',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'model_4',
            'name'=>'Model 4',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'model_5',
            'name'=>'Model 5',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_name',
            'name'=>'Website Prod Display Name ',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'keywords',
            'name'=>'Related Keywords',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'contents',
            'name'=>'Contents',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'short_desc',
            'name'=>'Short Description',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'detail_desc',
            'name'=>'Description',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'website_status',
            'name'=>'Website Status',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'status',
            'name'=>'Status',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'merchant_id',
            'name'=>'Merchant ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'merchant_name',
            'name'=>'Merchant Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'merchant_sku',
            'name'=>'Merchant SKU',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'supplier_id',
            'name'=>'Supplier ID',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'supplier_name',
            'name'=>'Supplier Name',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'cost',
            'name'=>'Product Cost',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'product_cost_hkd',
            'name'=>'Product Cost (HKD)',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'declared_desc',
            'name'=>'Declared Description',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'declared_value',
            'name'=>'Declared Value',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'declared_value_currency',
            'name'=>'Declared Value Currency',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'supplier_status',
            'name'=>'Supplier Status',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_1',
            'name'=>'Product Features Point 1',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_2',
            'name'=>'Product Features Point 2',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_3',
            'name'=>'Product Features Point 3',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_4',
            'name'=>'Product Features Point 4',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_5',
            'name'=>'Product Features Point 5',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        DB::table("marketplace_content_field")->insert([
            'value'=>'prod_features_point_6',
            'name'=>'Product Features Point 6',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
    }
}
