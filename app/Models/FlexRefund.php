<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexRefund extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'flex_refund';

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
        return Self::join("so","so.so_no","=","flex_refund.so_no")
        ->join('selling_platform AS sp', 'sp.id', '=', 'so.platform_id')
        ->whereIn("flex_batch_id",$batchIdlist)
        ->select('flex_refund.so_no',
                 'flex_refund.txn_id', 
                 'flex_refund.flex_batch_id', 
                 'flex_refund.gateway_id', 
                 'flex_refund.txn_time', 
                 'flex_refund.currency_id', 
                 'flex_refund.amount', 
                 'flex_refund.status', 
                 'sp.merchant_id')
        ->orderBy("sp.merchant_id","asc")
        ->orderBy("so.so_no","asc")
        ->get();
    }

    public function soFee($batchIdlist)
    {
        return Self::join("so","so.so_no","=","flex_refund.so_no")
        ->join('selling_platform AS sp', 'sp.id', '=', 'so.platform_id')
        ->leftJoin('flex_so_fee',function($join){
            $join->on('flex_so_fee.flex_batch_id', '=', 'flex_refund.flex_batch_id')->on('flex_so_fee.so_no', '=', 'flex_refund.so_no');
        })
        ->whereIn("flex_refund.flex_batch_id",$batchIdlist)
        ->select('flex_refund.so_no',
                 'flex_refund.txn_id', 
                 'flex_refund.flex_batch_id', 
                 'flex_refund.gateway_id', 
                 'flex_refund.txn_time', 
                 'flex_refund.currency_id', 
                 'flex_refund.amount', 
                 'flex_refund.status', 
                 'sp.merchant_id',
                 'flex_so_fee.status AS fee_status',
                 'flex_so_fee.amount AS fee_amount')
        ->orderBy("sp.merchant_id","asc")
        ->orderBy("so.so_no","asc")
        ->get();

    }
}
