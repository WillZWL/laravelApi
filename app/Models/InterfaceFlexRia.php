<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class InterfaceFlexRia extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'interface_flex_ria';

    public $primaryKey = 'trans_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function getFlexRiaByBatch($batchId)
	{
		$flexRia = self::where("flex_batch_id",$batchId)
					->select('trans_id',  
							  'so_no', 
							  'flex_batch_id', 
							  'gateway_id', 
							  'txn_id', 
							  'txn_time', 
							  'currency_id', 
							  DB::raw('SUM(amount) as amount'), 
							  DB::raw('SUM(net_usd_amt) as net_usd_amt'),
							  'status',
							  'batch_status')
					->groupBy("so_no","status","txn_time")
					->get();
		return $flexRia;
	}
}
