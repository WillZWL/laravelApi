<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\So;

class FulfillmentOrderRepository
{
    public function getOrders(Request $request)
    {
        $query = So::with('soItem')
                   ->with('sellingPlatform')
                   ->where('refund_status', 0)
                   ->where('hold_status', 0)
                   ->where('prepay_hold_status', 0)
                   ->where('platform_group_order', 1)
                   ->where('merchant_hold_status', 0)
                   ->where('platform_id', 'not like', 'EXCV%')
                   ->whereNotIn('platform_id', ['DPMONUS'])
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

        $query = $this->filterOrders($request, $query);

        $query->orderBy('expect_delivery_date', 'asc');
        $query->orderBy('so_no', 'asc');
        return $query->paginate(100);
    }

    public function filterOrders(Request $request, $query)
    {
        if ($request->get('order_no') !== NUll) {
            $query = $query->where('so_no', $request->get('order_no'));
        }

        if ($request->get('platform_id') !== NUll) {
            $query = $query->where('platform_id', $request->get('platform_id'));
        }

        if ($request->get('order_create_date') !== NUll) {
            $query = $query->where('order_create_date', $request->get('order_create_date'));
        }

        return $query;
    }
}
