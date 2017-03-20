<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SellingPlatform;
use DB;

class FulfillmentOrderRepository
{
    public function getOrders(Request $request)
    {
        $query = So::with('soItemDetail')
                   ->with('sellingPlatform')
                   ->where('platform_group_order', 1);

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

    public function getMerchantOrdersCount($request)
    {
        $query = So::leftJoin('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                   ->where('platform_group_order', 1);
        $query = $this->filterOrders($request, $query);
        $query->groupBy('sp.merchant_id');
        $query->select('sp.merchant_id', DB::raw('count(*) as count'));
        $data = $query->get();
        $sorted = $data->sortBy('count');
        return $sorted->values()->all();
    }

    public function getPickListCount($request)
    {
        $query = So::where('platform_group_order', 1);
        $query = $this->filterOrders($request, $query);
        if ($request->get('pick_list_no')) {
            $query->select('pick_list_no', DB::raw("count(*) as count, GROUP_CONCAT(so_no) as so_no_list"));
        } else {
            $query->select('pick_list_no', DB::raw("count(*) as count"));
        }
        $query->groupBy('pick_list_no');
        $data = $query->get();
        $sorted = $data->sortBy('count');
        return $sorted->values()->all();
    }

    public function filterOrders(Request $request, $query)
    {
        $this->filterOrderStatus($request, $query);

        $this->filterOrderPlatform($request, $query);

        if ($request->get('refund_status') !== null) {
            $query->where('refund_status', $request->get('refund_status'));
        }

        if ($request->get('hold_status') !== null) {
            $query->where('hold_status', $request->get('hold_status'));
        }

        if ($request->get('prepay_hold_status') !== null) {
            $query->where('prepay_hold_status', $request->get('prepay_hold_status'));
        }

        if ($request->get('merchant_hold_status') !== null) {
            $query->where('merchant_hold_status', $request->get('merchant_hold_status'));
        }

        if ($request->get('merchant_id') !== null && count($request->get('merchant_id')) > 0) {
            $query->leftJoin('selling_platform AS sp', 'sp.id', '=', 'so.platform_id');
            $query->whereIn('sp.merchant_id', $request->get('merchant_id'));
        }

        if ($request->get('filter') !== null) {
            $filter = $request->get('filter');
            if ($filter) {
                $query->where('so_no', $filter)->orWhere('platform_id', $filter);
            }
        }

        if ($request->get('pick_list_no') != null) {
            $pick_list_no = $request->get('pick_list_no');
            $query->where('pick_list_no', trim($pick_list_no));
        }

        if ($request->get('courier_id') !== null && count($request->get('courier_id')) > 0) {
            $query->whereIn('esg_quotation_courier_id', $request->get('courier_id'));
        }

        if ($request->get('into_iwms_status') !== null) {
            $query->leftJoin('so_extend AS se', 'se.so_no', '=', 'so.so_no');
            $query->where('se.into_iwms_status', $request->get('into_iwms_status'));
        }

        if ($request->get('dnote_invoice_status') !== null) {
            $query->where('dnote_invoice_status', $request->get('dnote_invoice_status'));
        }

        if ($request->get('exist_pick_list_no') !== null) {
            $query->whereNotNull("pick_list_no")->where("pick_list_no", "<>", "");
        }
        return $query;
    }

    public function filterOrderStatus(Request $request, $query)
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
        return $query;
    }

    public function filterOrderPlatform(Request $request, $query)
    {
        $excludeLackBalance = $request->get('exclude_lack_balance');
        $excludePlatformList = $this->getExcludePlatformList($excludeLackBalance);
        $excludePlatform = array_flatten($excludePlatformList);
        if ($excludePlatform) {
            $query->whereNotIn('platform_id', $excludePlatform);
        }
        return $query;
    }

    public function getExcludePlatformList($excludeLackBalance = 1)
    {
        $query = SellingPlatform::where('selling_platform.status', 1);
        if ($excludeLackBalance) {
            $query->leftJoin('merchant AS m', 'selling_platform.merchant_id', '=', 'm.id')
                  ->leftJoin('merchant_balance AS mb', 'mb.merchant_id', '=', 'm.id')
                  ->where('type', 'DISPATCH')
                  ->where('mb.balance', '<', 0)
                  ->where('m.can_do_prepayment', 1)
                  ->orWhere('need_fulfillment', 0);
        } else {
            $query->where('need_fulfillment', 0);
        }
        $lists = $query->select('selling_platform.id')->get()->toArray();
        return $lists;
    }
}
