<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MerchantQuotation extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_quotation';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailableQuotation($query)
    {
        return $query->where('current_used', '=', 1)
            ->where('is_approved', '=', 1)
            ->where('merchant_quotation.status', '=', 1)
            ->where('expire_date', '>=', Carbon::now());
    }
}
