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
        if ($type = $request->get('type')) {
            $query->where('sp.type', '=', $type);
        }
        // $query->select('sps.payment_gateway_id, so.biz_type AS biz_type, so.txn_id AS txn_id, so.so_no, so.platform_id, so.order_create_date, so.dispatch_date, so.currency_id, so.amount, so.settlement_date, m.marketplace_contact_name, m.marketplace_email_1, m.marketplace_email_2, m.marketplace_email_3, m.marketplace_contact_phone');
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
