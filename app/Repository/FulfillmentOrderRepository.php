<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SellingPlatform;

class FulfillmentOrderRepository
{
    public function getOrders(Request $request)
    {
        $query = So::with('soItemDetail')
                   ->with('sellingPlatform')
                   ->where('refund_status', 0)
                   ->where('hold_status', 0)
                   ->where('prepay_hold_status', 0)
                   ->where('platform_group_order', 1)
                   ->where('merchant_hold_status', 0)
                   ->where('platform_id', 'not like', 'EXCV%')
                   ->where('is_test', 0);

        $query = $this->filterOrders($request, $query);

        $query->orderBy('expect_delivery_date', 'asc');
        $query->orderBy('so.so_no', 'asc');
        $per_page = 10;
        if ($request->get('per_page')) {
            $per_page = $request->get('per_page');
        }
        if ($request->get('page')) {
            return $query->paginate($per_page,  ['*'], 'page', $request->get('page'));
        }
        return $query->paginate($per_page);
    }

    public function filterOrders(Request $request, $query)
    {
        $status = $request->get('status');
        if (in_array($status, [1, 2, 3, 4, 5, 6])) {
            $query->where('status', $status);
        } else {
            switch ($status) {
                case 'paied':
                    $query->where('status', 3);
                    break;
                case 'allocated':
                    $query->whereIn('status', [4, 5]);
                case 'dispatch':
                    $query->where('status', [4, 5]);
                    break;
                default:
                    $query->where('status', 3);
                    break;
            }
        }

        $excludePlatform = $this->getExcludePlatform();
        $excludePlatform = array_flatten($excludePlatform);
        if ($excludePlatform) {
            $query->whereNotIn('platform_id', $excludePlatform);
        }

        if ($request->get('order_no') !== NUll) {
            $query->where('so_no', $request->get('order_no'));
        }

        if ($request->get('platform_id') !== NUll) {
            $query->where('platform_id', $request->get('platform_id'));
        }

        if ($request->get('order_create_date') !== NUll) {
            $query->where('order_create_date', $request->get('order_create_date'));
        }

        if ($request->get('filter') !== NULL) {
            $filter = $request->get('filter');
            if ($filter) {
                $query->where('so_no', $filter)->orWhere('platform_id', $filter);
            }
        }

        if ($request->get('merchantId') !== '') {
            $query->where('platform_id', 'like', '%'.$request->get('merchantId').'%');
        }

        if ($request->get('courierId') !== '') {
            $query->where('esg_quotation_courier_id', $request->get('courierId'));
        }

        if ($request->get('into_iwms_status') !== NULL) {
            $query->leftJoin('so_extend AS se', 'se.so_no', '=', 'so.so_no');
            $query->where('se.into_iwms_status', $request->get('into_iwms_status'));
        }

        if ($request->get('dnote_invoice_status') !== NULL) {
            $query->where('dnote_invoice_status', $request->get('dnote_invoice_status'));
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
                                ->orWhere('selling_platform.need_fulfillment', 0)
                                ->select('selling_platform.id')
                                ->get()
                                ->toArray();
        return $lists;
    }
}
