<?php

namespace App\Services;

use App\Models\FlexSoFee;
use App\Models\PaymentGateway;
use App\Models\So;
use App\Models\MarketplaceSkuMapping;
use Excel;

class CommissionChargeService
{
    public function amazonCommissionChargeExport($flexBatchId)
    {
        $header[] = [
            'Order ID',
            'Transaction ID',
            'Currency ID',
            'Order Amt',
            'Gateway Report PSP Fee',
            'Admin PSP Fee',
            'Gateway Report PSP %',
            'Admin PSP Fee %'
        ];

        $orderList = FlexSoFee::AmazonCommission($flexBatchId);
        $pspFeeData = $this->calculatePspFee($orderList);

        $newData = array_merge($header, $pspFeeData);

        $excel = Excel::create('product_inventory_feed', function ($excel) use ($newData) {
            $excel->sheet('first', function ($sheet) use ($newData) {
                $sheet->fromArray($newData);
            });
        });

        return $excel;
    }

    public function calculatePspFee($orderList)
    {
        $pspFeeData = [];
        foreach ($orderList as $order) {
            $rate = $order->rate;
            $so = So::whereSoNo($order->so_no)->first();
            if ($so) {
                $adminPspFee = 0;

                $marketplaceId = $so->sellingPlatform->marketplace;
                $countryId = $so->delivery_country_id;

                $paymentGatewayRate = $adminFeePercent = $adminFeeAbs = 0;
                if ($paymentGateway = $this->getPaymentGateway($marketplaceId, $countryId)) {
                    $paymentGatewayRate = $paymentGateway->payment_gateway_rate ? $paymentGateway->payment_gateway_rate : 0;
                    $adminFeePercent = $paymentGateway->admin_fee_percent ? $paymentGateway->admin_fee_percent : 0;
                    $adminFeeAbs = $paymentGateway->admin_fee_abs ? $paymentGateway->admin_fee_abs : 0;
                }

                if ($soItems = $so->soItem()->whereSoNo($so->so_no)->whereHiddenToClient(0)->get()) {

                    foreach ($soItems as $soItem) {
                        if (!$soItem->ext_seller_sku) continue;
                        $MarketplaceSkuMapping = MarketplaceSkuMapping::whereMarketplaceSku($soItem->ext_seller_sku)->whereMarketplaceId($marketplaceId)->whereCountryId($countryId)->first();
                        if ($MarketplaceSkuMapping) {
                            $qty = $soItem->qty;
                            $unit_price = $soItem->unit_price;
                            $prod_sku = $soItem->prod_sku;

                            $paymentGatewayFee = ($unit_price * $paymentGatewayRate / 100) * $rate;
                            $paymentGatewayAdminFee = ($adminFeeAbs + $unit_price * $adminFeePercent / 100) * $rate;

                            $marketplaceCommission = 0;
                            $mpCatCommission = $MarketplaceSkuMapping->mpCategoryCommission;
                            if ($mpCatCommission) {
                                $mpCommission = $mpCatCommission->mp_commission;
                                $maximum = $mpCatCommission->maximum;
                                $marketplaceCommission = (min($unit_price * $mpCommission / 100, $maximum)) * $rate;
                            }

                            $adminPspFee += round(($paymentGatewayFee + $paymentGatewayAdminFee + $marketplaceCommission) * $qty, 2);
                        }
                    }
                }
                $adminPspFee = $adminPspFee != 0 ? $adminPspFee : "0";

                $commission_amt_percent = round(($order->commission / $order->amount) * 100, 2);
                $commission_amt_percent = $commission_amt_percent != 0 ? $commission_amt_percent : "0";

                $psp_amt_percent = round(($adminPspFee / $order->amount) * 100, 2);
                $psp_amt_percent = $psp_amt_percent != 0 ? $psp_amt_percent : "0";

                $pspFeeData[] = [
                    $order->so_no,
                    $order->txn_id,
                    $order->currency_id,
                    $order->amount,
                    $order->commission,
                    $adminPspFee,
                    $commission_amt_percent,
                    $psp_amt_percent
                ];

            }
        }

        return $pspFeeData;
    }

    public function getPaymentGateway($marketplace, $country_code)
    {
        $account = substr($marketplace, 0, 2);
        $marketplaceId = substr($marketplace, 2);
        $countryCode = $country_code;
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;
        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));

        return PaymentGateway::find($paymentGatewayId);
    }

}

