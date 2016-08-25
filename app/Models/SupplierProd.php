<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierProd extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'supplier_prod';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'prod_sku');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'supplier_id', 'id');
    }
}
