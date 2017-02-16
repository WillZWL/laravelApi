<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAssemblyMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_assembly_mapping';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

    public function merchantProductMapping()
    {
        return $this->hasMany('App\Models\MerchantProductMapping', 'sku', 'sku');
    }

    public function scopeActive($query)
    {
        return $query->where('product_assembly_mapping.status', '=', 1);
    }

    public function scopeInventoryQuantities($query, $skus, $warehouseId)
    {
        return $query->whereIn('prod_sku', $skus)
                ->whereWarehouseId($warehouseId)
                ->where('inventory', '>', 0)
                ->get(['prod_sku', 'inventory']);
    }
}
