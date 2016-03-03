<?php

namespace App\Http\Controllers;

use App\Models\AmazonOrder;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;

class ListOrders extends Controller
{
    public function index(Request $request)
    {
        $orders = DB::select('
                select * from amazon_orders as ao inner join amazon_order_items as aoi
                on ao.amazon_order_id = aoi.amazon_order_id
                inner join amazon_shipping_addresses asa
                on ao.amazon_order_id = asa.amazon_order_id
                where ao.platform = :platform and ao.updated_at > :timestamp',
                ['platform' => $request->store, 'timestamp' => $request->timestamp]
        );

        return json_encode($orders);
    }
}
