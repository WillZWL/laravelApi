<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\So;

class OrderSettlementService
{

    public function getOrders(Request $request)
    {
        $query = So::leftJoin('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                   ->leftJoin('marketplace AS m', 'sp.marketplace', '=', 'm.id')
                   ->leftJoin('so_payment_status AS sps', 'so.so_no', '=', 'sps.so_no')
                   ->leftJoin('payment_gateway AS pg', 'sps.payment_gateway_id', '=', 'pg.id')
                   ->whereNotNull('settlement_date')->where('settlement_date', '<>', '');
        $query->where('so.status', 6);
        if ($type = $request->get('type')) {
            $query->where('sp.type', '=', $type);
        }
        if ($payment_gateway = $request->get('payment_gateway')) {
            $query->where('sps.payment_gateway_id', $payment_gateway);
        }
        if ($biz_type = $request->get('biz_type')) {
            $query->where('so.biz_type', $biz_type);
        }
        if ($txn_id = $request->get('txn_id')) {
            $query->where('so.txn_id', $txn_id);
        }
        if ($so_no = $request->get('so_no')) {
            $query->where('so.so_no', $so_no);
        }
        $query->select('sps.payment_gateway_id', 'so.biz_type', 'so.txn_id', 'so.so_no', 'so.platform_order_id', 'so.order_create_date', 'so.dispatch_date', 'so.currency_id', 'so.amount', 'so.settlement_date', 'so.create_on', 'm.marketplace_contact_name', 'm.marketplace_contact_phone', 'm.marketplace_email_1', 'm.marketplace_email_2', 'm.marketplace_email_3');
        $per_page = 10;
        if ($request->get('per_page')) {
            $per_page = $request->get('per_page');
        }
        if ($request->get('page')) {
            return $query->paginate($per_page, ['*'], 'page', $request->get('page'));
        }
        return $query->paginate($per_page);
    }
}
