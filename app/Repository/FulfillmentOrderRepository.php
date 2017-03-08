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
            return $query->paginate($per_page, ['*'], 'page', $request->get('page'));
        }
        return $query->paginate($per_page);
    }

    public function filterOrders(Request $request, $query)
    {
        $status = $request->get('status');
        if (in_array($status, [1, 2, 3, 4, 5, 6])) {
            $query->where('so.status', $status);
        } else {
            switch ($status) {
                case 'paied':
                    $query->where('so.status', 3);
                    break;
                case 'allocated':
                    $query->whereIn('so.status', [4, 5]);
                    break;
                case 'dispatch':
                    $query->whereIn('so.status', [4, 5]);
                    break;
                default:
                    $query->where('so.status', 3);
                    break;
            }
        }

        $excludePlatform = $this->getExcludePlatform();
        $excludePlatform = array_flatten($excludePlatform);
        if ($excludePlatform) {
            $query->whereNotIn('platform_id', $excludePlatform);
        }

        if ($request->get('order_no') !== null) {
            $query->where('so_no', $request->get('order_no'));
        }

        if ($request->get('platform_id') !== null) {
            $query->where('platform_id', $request->get('platform_id'));
        }

        if ($request->get('order_create_date') !== null) {
            $query->where('order_create_date', $request->get('order_create_date'));
        }

        if ($request->get('filter') !== null) {
            $filter = $request->get('filter');
            if ($filter) {
                $query->where('so_no', $filter)->orWhere('platform_id', $filter);
            }
        }

        if ($request->get('merchantId') !== null && count($request->get('merchantId')) > 0) {
            $query->leftJoin('selling_platform AS sp', 'sp.id', '=', 'so.platform_id');
            $query->whereIn('sp.merchant_id', $request->get('merchantId'));
        }

        if ($request->get('courierId') !== null && count($request->get('courierId')) > 0) {
            $query->whereIn('esg_quotation_courier_id', $request->get('courierId'));
        }

        if ($request->get('into_iwms_status') !== null) {
            $query->leftJoin('so_extend AS se', 'se.so_no', '=', 'so.so_no');
            $query->where('se.into_iwms_status', $request->get('into_iwms_status'));
        }

        if ($request->get('dnote_invoice_status') !== null) {
            $query->where('dnote_invoice_status', $request->get('dnote_invoice_status'));
        }

        if ($request->get('pick_list_no') !== null) {
            $query->whereNotNull("pick_list_no")->where("pick_list_no", "<>", "");
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
