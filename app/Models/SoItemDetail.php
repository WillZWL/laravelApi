<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoItemDetail extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_item_detail';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->belongsTo('App\Models\So', 'so_no', 'so_no');
    }
}
