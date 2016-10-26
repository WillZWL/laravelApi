<?php

namespace App\Services;

use App\Models\FlexSoFee;
use App\Models\PaymentGateway;

class CommissionChargeService
{
    public function commissionChargeData($postData)
    {
        $data = [];
        $header[] = [
                'so_no' => "So no",
                'gateway_id' => 'Gateway ID',
                'currency' => 'Currency ID',
                'amazon_commission' => 'MarketPlace Commission Charge',
                'psp_fee' => 'PSP Fee',
                'diff' => 'Diff Fee'
        ];

        $feeList = FlexSoFee::AmazonCommission($postData);
        $current_so_no = '';
        foreach ($feeList as $item) {
            if ($current_so_no != $item->so_no) {
                $data[$item->so_no] = [];
                $pspFee = 0;
            }

            $row = [];
            $row['prod_sku'] = $item->prod_sku;
            $row['marketplace'] = $item->marketplace;
            $row['country'] = $item->delivery_country_id;
            $row['soi_amount'] = $item->soi_amount;
            $row['rate'] = $item->rate;

            $pspFee += $this->getPaymentFee($row);

            $data[$item->so_no] = [
                'so_no' => $item->so_no,
                'gateway_id' => $item->gateway_id,
                'currency' => $item->currency_id,
                'amazon_commission' => $item->commission,
                'psp_fee' => $pspFee,
                'diff' => $item->commission + $pspFee
            ];

            $current_so_no = $item->so_no;
        }

        return array_merge($header, $data);
    }

    public function getPaymentFee($data)
    {
        $account = substr($data['marketplace'], 0, 2);
        $marketplaceId = substr($data['marketplace'], 2);
        $countryCode = $data['country'];
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;
        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));

        if ($paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate) {
            return round(($data['soi_amount'] * $paymentGatewayRate / 100) *  $data['rate'], 2);
        }

        return 0;
    }

}

