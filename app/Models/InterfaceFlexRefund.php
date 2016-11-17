<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class InterfaceFlexRefund extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'interface_flex_refund';

    public $primaryKey = 'trans_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function getFlexRefundByBatch($batchId)
	{
		$flexRefund = self::where("flex_batch_id",$batchId)
					->select('trans_id',  
							  'so_no', 
							  'flex_batch_id', 
							  'gateway_id', 
							  'txn_id', 
							  'internal_txn_id',
							  'txn_time', 
							  'currency_id', 
							  DB::raw('SUM(amount) as amount'),
							  'status',
							  'batch_status')
					->groupBy("so_no","status","txn_time")
					->get();
		return $flexRefund;
	}
}
