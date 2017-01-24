<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product';

    protected $primaryKey = 'sku';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function soItem()
    {
        return $this->hasMany('App\Models\SoItem', 'sku', 'prod_sku');
    }

    public function productComplementaryAcc()
    {
        return $this->hasMany('App\Models\ProductComplementaryAcc', 'sku', 'accessory_sku');
    }

    public function merchantProductMapping()
    {
        return $this->hasOne('App\Models\MerchantProductMapping', 'sku', 'sku');
    }

    public function marketplaceProducts()
    {
        return $this->hasMany('App\Models\MarketplaceProduct', 'esg_sku', 'sku');
    }

    public function marketplaceSkuMapping()
    {
        return $this->hasMany('App\Models\MarketplaceSkuMapping', 'sku', 'sku');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'buyer');
    }

    public function supplierProduct()
    {
        return $this->hasOne('App\Models\SupplierProd', 'prod_sku', 'sku')->where('order_default', '=', 1);
    }

    public function category()
    {
        return $this->hasOne('App\Models\Category', 'id', 'cat_id');
    }

    public function subCategory()
    {
        return $this->hasOne('App\Models\Category', 'id', 'sub_cat_id');
    }

    public function subSubCategory()
    {
        return $this->hasOne('App\Models\Category', 'id', 'sub_sub_cat_id');
    }

    public function hscodeCategory()
    {
        return $this->hasOne('App\Models\HscodeCategory', 'id', 'hscode_cat_id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\Brand');
    }

    public function version()
    {
        return $this->hasOne('App\Models\Version', 'id', 'version_id');
    }

    public function colour()
    {
        return $this->hasOne('App\Models\Colour', 'id', 'colour_id');
    }

    public function productFeatures()
    {
        return $this->hasMany('App\Models\ProductFeatures', 'esg_sku', 'sku');
    }

    public function productImages()
    {
        return $this->hasMany('App\Models\ProductImage', 'sku', 'sku');
    }

    public function productContents()
    {
        return $this->hasMany('App\Models\ProductContent', 'prod_sku', 'sku');
    }

    public function productContentExtends()
    {
        return $this->hasMany('App\Models\ProductContentExtend', 'prod_sku', 'sku');
    }

    public function productCustomClassifications()
    {
        return $this->hasMany('App\Models\ProductCustomClassification', 'sku', 'sku');
    }

}
