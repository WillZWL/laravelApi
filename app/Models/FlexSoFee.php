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
    public function scopeAmazonCommission($query, $data)
    {
        if ($data['marketplace'] || $data['payment_gateway']) {

            if (empty($data['payment_gateway'])) {
                $onRel = 'like';
                $onVal = $data['marketplace'] .'_%';
            } else {
                $onRel = '=';
                $onVal = $data['payment_gateway'];
            }

            switch ($data['marketplace']) {
                case 'amazon':
                    $fsfStatus = 'A_COMM';
                    break;

                default:
                    $fsfStatus = '';
                    break;
            }

            return $query->join('flex_batch', 'flex_batch.id', '=', 'flex_so_fee.flex_batch_id')
                ->join('so', 'so.so_no', '=', 'flex_so_fee.so_no')
                ->join('so_item', 'so.so_no', '=', 'so_item.so_no')
                ->join('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                ->join('exchange_rate AS er', function ($er) {
                    $er->on('er.from_currency_id', '=', 'so.currency_id')
                        ->on('er.to_currency_id', '=', 'flex_so_fee.currency_id');
                })

                ->where('flex_batch.status', '=', 'C')
                ->where("flex_batch.gateway_id", $onRel, $onVal)
                ->where('flex_so_fee.status', '=', $fsfStatus)
                ->where('so.platform_split_order', '=', '1')
                ->where('so_item.hidden_to_client', '=', '0')

                ->select(
                    'flex_batch.gateway_id',
                    'flex_so_fee.currency_id',
                    'flex_so_fee.amount AS commission',
                    'sp.marketplace',
                    'so.so_no',
                    'so.amount',
                    'so.delivery_country_id',
                    'so_item.prod_sku',
                    'so_item.qty',
                    'so_item.amount AS soi_amount',
                    'er.rate'
                )

                ->orderBy('so.so_no')

                ->get();
        }
    }
}
