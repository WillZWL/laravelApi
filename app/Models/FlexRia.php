<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexRia extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'flex_ria';

    public $primaryKey = '';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->belongsTo('App\Models\So', 'so_no', 'so_no');
    }

    public function getFeedbackReport($batchIdlist)
    {
        return Self::join("so","so.so_no","=","flex_ria.so_no")
        ->join('selling_platform AS sp', 'sp.id', '=', 'so.platform_id')
        ->whereIn("flex_batch_id",$batchIdlist)
        ->select('flex_ria.so_no',
                 'flex_ria.txn_id', 
                 'flex_ria.flex_batch_id', 
                 'flex_ria.gateway_id', 
                 'flex_ria.txn_time', 
                 'flex_ria.currency_id', 
                 'flex_ria.amount', 
                 'flex_ria.status', 
                 'sp.merchant_id')
        ->orderBy("sp.merchant_id","asc")
        ->orderBy("so.so_no","asc")
        ->get();

    }

    public function soFee($batchIdlist)
    {
        return Self::join("so","so.so_no","=","flex_ria.so_no")
        ->join('selling_platform AS sp', 'sp.id', '=', 'so.platform_id')
        ->leftJoin('flex_so_fee',function($join){
            $join->on('flex_so_fee.flex_batch_id', '=', 'flex_ria.flex_batch_id')
                 ->on('flex_so_fee.so_no', '=', 'flex_ria.so_no')
                 ->on('flex_so_fee.txn_time', '=', 'flex_ria.txn_time');
        })
        ->whereIn("flex_ria.flex_batch_id",$batchIdlist)
        ->select('flex_ria.so_no',
                 'flex_ria.txn_id', 
                 'flex_ria.flex_batch_id', 
                 'flex_ria.gateway_id', 
                 'flex_ria.txn_time', 
                 'flex_ria.currency_id', 
                 'flex_ria.amount', 
                 'flex_ria.status', 
                 'sp.merchant_id',
                 'flex_so_fee.status AS fee_status',
                 'flex_so_fee.amount AS fee_amount')
        ->orderBy("sp.merchant_id","asc")
        ->orderBy("so.so_no","asc")
        ->get();

    }
}
