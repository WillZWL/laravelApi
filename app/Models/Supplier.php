<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'supplier';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function supplierProduct()
    {
        return $this->hasMany('App\Models\SupplierProd', 'supplier_id', 'id');
    }
}
