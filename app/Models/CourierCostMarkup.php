<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierCostMarkup extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_acc_quotation_percent';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
