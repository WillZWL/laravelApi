<?php

namespace App\Services;

use App\Models\FlexSoFee;
use App\Models\PaymentGateway;
use App\Models\So;
use App\Models\MarketplaceSkuMapping;

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

        $orderList = FlexSoFee::AmazonCommission($postData);

        $pspFee = $this->calculatePspFee($orderList);

        foreach ($orderList as $order) {
            $data[] = [
                'so_no' => $order->so_no,
                'gateway_id' => $order->gateway_id,
                'currency' => $order->currency_id,
                'amazon_commission' => $order->commission,
                'psp_fee' => $pspFee[$order->so_no],
                'diff' => $order->commission + $pspFee[$order->so_no],
            ];
        }

        return array_merge($header, $data);
    }

    public function calculatePspFee($orderList)
    {
        $pspFee = [];
        foreach ($orderList as $order) {
            $rate = $order->rate;
            $so = So::whereSoNo($order->so_no)->first();
            if ($so) {
                $pspFee[$so->so_no] = 0;

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

                            $pspFee[$so->so_no] += round(($paymentGatewayFee + $paymentGatewayAdminFee + $marketplaceCommission) * $qty, 2);
                        }
                    }
                }
            }
        }

        return $pspFee;
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

