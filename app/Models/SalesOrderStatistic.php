<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderStatistic extends Model
{
    public $connection = 'mysql_esg';

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'esg_sku', 'sku');
    }
}
