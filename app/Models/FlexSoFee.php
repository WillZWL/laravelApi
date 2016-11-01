<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexSoFee extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'flex_so_fee';

    // public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];


    public function so()
    {
        return $this->belongsTo('App\Models\So', 'so_no', 'so_no');
    }

    /**
     * Get not Commission Charge.
     *
     * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAmazonCommission($query, $flexBatchId)
    {
        if ($flexBatchId) {
            return $query->join('flex_batch', 'flex_batch.id', '=', 'flex_so_fee.flex_batch_id')
                ->join('so', 'so.so_no', '=', 'flex_so_fee.so_no')
                ->join('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                ->join('exchange_rate AS er', function ($er) {
                    $er->on('er.from_currency_id', '=', 'so.currency_id')
                        ->on('er.to_currency_id', '=', 'flex_so_fee.currency_id');
                })

                ->where('flex_batch.id', '=', $flexBatchId)
                ->where('flex_so_fee.status', '=', 'A_COMM')
                ->where('so.platform_split_order', '=', '1')

                ->select(
                    'flex_batch.id AS flex_batch_id',
                    'flex_batch.gateway_id',
                    'flex_so_fee.currency_id',
                    'flex_so_fee.amount AS commission',
                    'flex_so_fee.status',
                    'sp.marketplace',
                    'so.so_no',
                    'so.txn_id',
                    'flex_so_fee.currency_id',
                    'so.amount',
                    'so.delivery_country_id',
                    'er.rate'
                )

                ->orderBy('so.so_no')

                ->groupBy('so.so_no')

                ->get();
        }
    }
}
