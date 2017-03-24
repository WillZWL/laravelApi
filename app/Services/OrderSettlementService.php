<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SoSettlement;
use App\Models\PaymentGateway;
use App\Models\SoPaymentStatus;

class OrderSettlementService
{

    use BaseMailService;

    public function getOrders(Request $request)
    {
        $query = So::leftJoin('selling_platform AS sp', 'so.platform_id', '=', 'sp.id')
                   ->leftJoin('marketplace AS m', 'sp.marketplace', '=', 'm.id')
                   ->leftJoin('so_payment_status AS sps', 'so.so_no', '=', 'sps.so_no')
                   ->leftJoin('payment_gateway AS pg', 'sps.payment_gateway_id', '=', 'pg.id')
                   ->leftJoin('so_settlement AS ss', 'so.so_no', '=', 'ss.so_no')
                   ->where('so.platform_group_order', 1)
                   ->whereNull('settlement_date');
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
        $validation_status = $request->get('validation_status');
        if ($validation_status !== '') {
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
        $paymentGatewayId = $request->get('payment_gateway_id');
        $marketplaceId = $request->get('marketplace_id');
        $emails = $request->get('checked_emails');
        if ($paymentGatewayId && $emails) {
            $paymentGateway = PaymentGateway::find($paymentGatewayId);
            $refId = $paymentGateway->ref_id;

            $attachmentFile = $this->generateAttacheFile($paymentGatewayId);

            SoSettlement::leftJoin('so_payment_status AS sps', 'so_settlement.so_no', '=', 'sps.so_no')
                        ->whereIn('sps.payment_gateway_id', $paymentGatewayId)
                        ->where('validation_status', '<', 3)
                        ->increment('validation_status', 1);

            $emailsAddress = implode(',', $emails);
            $subject = 'Settlement Enquiry';
            $message = $this->getEmailContent($marketplaceId);

            $this->setMailTemplate($message);
            $this->sendAttachmentMail($emailsAddress, $subject, $attachmentFile, $refId, 'itsupport-sz@eservicesgroup.com', $refId);
        }

        return $response;
    }

    public function getEmailContent($marketplaceId = '')
    {
        $message = "Hello, \r\n";
        $message .= "Could you please confirm that the attached list of orders has been paid to us and under which payment reference ?\r\n";
        $message .= "Thank you for your assistance.\r\n";
        $message .= "Best regards";
        if (in_array($marketplaceId, ['BCPRICEMINISTER', 'PXPRICEMINISTER', 'VBPRICEMINISTER', 'BCCDISCOUNT', 'PXCDISCOUNT'])) {
            $message = "Bonjour,\r\n";
            $message .= "Pourriez vous nous confirmer que la liste de commande en pièce-jointe nous a été payée et sous quel numéro de paiement ?\r\n";
            $message .= "Merci pour votre assistance.\r\n";
            $message .= "Cordialement\r\n";
        }
        return $message;
    }

    public function generateAttacheFile($paymentGatewayId)
    {
        $orders = So::leftJoin('so_payment_status AS sps', 'so.so_no', '=', 'sps.so_no')
                    ->leftJoin('so_allocate AS sa', 'so.so_no', '=', 'sa.so_no')
                    ->leftJoin('so_shipment AS ss', 'sa.sh_no', '=', 'ss.sh_no')
                    ->leftJoin('so_settlement AS sose', 'sa.so_no', '=', 'sose.so_no')
                    ->leftJoin('courier_info AS ci', 'ss.courier_id', '=', 'ci.courier_id')
                    ->where('so.platform_group_order', 1)
                    ->where('sose.validation_status', '<', 3)
                    ->whereIn('sps.payment_gateway_id', $paymentGatewayId)
                    ->groupBy('sa.so_no')
                    ->select('so.platform_order_id', 'so.so_no', 'so.order_create_date', 'so.dispatch_date', 'ci.courier_name', 'ss.tracking_no')
                    ->get();
        if (!$orders->isEmpty()) {
            $cellData = [];
            $cellData[] = [
                'Platform Order Number',
                'Order Create Date',
                'Dispatch Date',
                'Courier',
                'Tracking Number'
            ];
            foreach ($orders as $order) {
                $cellData[] = [
                    $order->platform_order_id,
                    $order->order_create_date,
                    $order->dispatch_date,
                    $order->courier_name,
                    $order->tracking_no
                ];
            }
            $path = storage_path('/app/');
            $fileName = 'Settlement_Enquiry_Order_List';

            $this->createExcelFile($fileName, $path, $cellData);

            return ['file_name' => $fileName.'.xlsx', 'path' => $path];
        }
    }
}
