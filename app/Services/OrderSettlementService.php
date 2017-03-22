<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SoSettlement;

class OrderSettlementService
{

    public function getOrders(Request $request)
    {
        $query = So::leftJoin('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                   ->leftJoin('marketplace AS m', 'sp.marketplace', '=', 'm.id')
                   ->leftJoin('so_payment_status AS sps', 'so.so_no', '=', 'sps.so_no')
                   ->leftJoin('payment_gateway AS pg', 'sps.payment_gateway_id', '=', 'pg.id')
                   ->leftJoin('so_settlement AS ss', 'so.so_no', '=', 'ss.so_no')
                   ->whereNotNull('settlement_date')
                   ->where('settlement_date', '<>', '')
                   ->where('settlement_date', '<>', '0000-00-00');
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
        if ($platform_order_id = $request->get('platform_order_id')) {
            $query->where('so.platform_order_id', $platform_order_id);
        }
        if ($validation_status = $request->get('validation_status')) {
            $query->where('ss.validation_status', $validation_status);
        }
        $query->select('sps.payment_gateway_id', 'so.biz_type', 'so.txn_id', 'so.so_no', 'so.platform_order_id', 'so.order_create_date', 'so.dispatch_date', 'so.currency_id', 'so.amount', 'so.settlement_date', 'so.create_on', 'pg.settlement_date_type', 'pg.settlement_date_day', 'm.marketplace_contact_name', 'm.marketplace_contact_phone', 'm.marketplace_email_1', 'm.marketplace_email_2', 'm.marketplace_email_3', 'ss.validation_status');

        $per_page = 10;
        if ($request->get('per_page')) {
            $per_page = $request->get('per_page');
        }
        if ($request->get('page')) {
            return $query->paginate($per_page, ['*'], 'page', $request->get('page'));
        }
        return $query->paginate($per_page);
    }

    public function bulkUpdate(Request $request)
    {
        $response['status'] = 1;
        if (($soNoList = $request->get('orders')) && is_array($request->get('orders'))) {
            $validation_status = $request->get('validation_status');
            if (!empty($soNoList)) {
                try {
                    SoSettlement::whereIn('so_no', $soNoList)->update(['validation_status' => $validation_status]);
                } catch (\Exception $e) {
                    $response['status'] = 0;
                    $response['message'] = $e->getMessage();
                }
            }
        }
        return $response;
    }

    //TODO
    public function sendEmail(Request $request)
    {
        $response['status'] = 1;
        $soNo = $request->get('so_no');
        $emails = $request->get('checked_emails');
        if ($soNo && $emails) {
            $soSettlement = SoSettlement::where('so_no', $soNo)->first();
            if (($soSettlement->validation_status < 3)) {
                $soSettlement->validation_status = $soSettlement->validation_status + 1;
            }
            $soSettlement->save();
            $paymentGateway = So::where('so_no', $soNo)->first()->soPaymentStatus()->first()->paymentGateway()->first();
            $refId = $paymentGateway->ref_id;

            $headers = "";
            $emailsAddress = implode(',', $emails);
            $subject = '';
            $message = '';
            // mail($emailsAddress, $subject, $message, $headers = 'From:'.$refId);
        }

        return $response;
    }
}
