<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'inventory';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function marketplaceSkuMapping()
    {
        return $this->belongsToMany('App\Models\MarketplaceSkuMapping', 'prod_sku', 'sku');
    }

    public function scopeInventoryQuantities($query, $skus, $warehouseId)
    {
        return $query->whereIn('prod_sku', $skus)
                ->whereWarehouseId($warehouseId)
                ->where('inventory', '>', 0)
                ->get(['prod_sku', 'inventory']);
    }
}
