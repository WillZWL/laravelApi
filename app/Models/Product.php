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

    public function supplierProduct()
    {
        return $this->hasOne('App\Models\SupplierProd', 'prod_sku', 'sku')->where('order_default', '=', 1);
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\Brand');
    }
}
