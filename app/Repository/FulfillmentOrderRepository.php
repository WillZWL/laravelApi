<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SellingPlatform;

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
                   ->where('is_test', 0);
        $excludePlatform = $this->getExcludePlatform();
        $excludePlatform = array_flatten($excludePlatform);
        if ($excludePlatform) {
            $query = $query->whereNotIn('platform_id', $excludePlatform);
        }
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
        $filter = $request->get('filter');
        if ($filter) {
            $query = $query->where('so_no', $filter)->orWhere('platform_id', $filter);
        }

        $query = $this->filterOrders($request, $query);

        $query->orderBy('expect_delivery_date', 'asc');
        $query->orderBy('so_no', 'asc');
        $per_page = 10;
        if ($request->get('per_page')) {
            $per_page = $request->get('per_page');
        }
        return $query->paginate($per_page);
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

    public function getExcludePlatform()
    {
        $lists = SellingPlatform::leftJoin('merchant AS m', 'selling_platform.merchant_id', '=', 'm.id')
                                ->leftJoin('merchant_balance AS mb', 'mb.merchant_id', '=', 'm.id')
                                ->where('type', 'DISPATCH')
                                ->where('mb.balance', '<', 0)
                                ->where('m.can_do_prepayment', 1)
                                ->select('selling_platform.id')
                                ->get()
                                ->toArray();
        return $lists;

    }
}
