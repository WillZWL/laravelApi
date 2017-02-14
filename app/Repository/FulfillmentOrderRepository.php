<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\So;

class FulfillmentOrderRepository
{
    public function getOrders(Request $request)
    {
        $query = So::with('soItem')
                   ->where('refund_status', 0)
                    ->where('hold_status', 0)
                   ->where('prepay_hold_status', 0)
                   ->where('platform_group_order', 1)
                   ->where('merchant_hold_status', 0)
                   ->where('is_test', 0);
        $status = $request->get('status');
        if (is_int($status)) {
            $query = $query->where('status', $status);
        } else {
            switch ($status) {
                case 'paied':
                    $query = $query->where('status', 3);
                    break;
                case 'allocated':
                    $query = $query->whereIn('status', [4, 5]);
                case 'dispatch':
                    $query = $query->where('status', [4, 5]);
                    break;
                default:
                    $query = $query->where('status', 3);
                    break;
            }
        }
        $query->orderBy('expect_delivery_date', 'asc');
        $query->orderBy('so_no', 'asc');
        return $query->paginate(30);
    }
}
