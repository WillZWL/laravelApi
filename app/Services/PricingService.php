<?php

namespace App\Services;

use App\Http\Requests\ProfitEstimateRequest;
use App\Models\CountryState;
use App\Models\CountryTax;
use App\Models\Declaration;
use App\Models\DeclarationException;
use App\Models\ExchangeRate;
use App\Models\HscodeDutyCountry;
use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantProductMapping;
use App\Models\MpFixedFee;
use App\Models\MpListingFee;
use App\Models\PaymentGateway;
use App\Models\ProductComplementaryAcc;
use App\Models\SupplierProd;
use App\Repository\DeliveryQuotationRepository;

class PricingService
{
    private $product;
    private $destination;
    private $exchangeRate;
    private $adjustRate = 0.9725;
    private $deliveryQuotationRepository;


    // TODO
    // 只处理 Pricing 相关的业务逻辑, 通过在 __construct() 中加入 repository 方式拆分逻辑
    public function __construct(DeliveryQuotationRepository $deliveryQuotationRepository)
    {
        $this->deliveryQuotationRepository = $deliveryQuotationRepository;
    }

    public function availableShippingWithProfit(ProfitEstimateRequest $request)
    {
        $marketplaceProduct = MarketplaceSkuMapping::with('product')
                                ->with('mpControl')
                                ->findOrFail($request->get('id'));

        $marketplaceProduct->price = $request->get('selling_price');

        if ($marketplaceProduct->price <= 0) {
            return collect();
        }

        // TODO
        // 将 country_state.is_default_state 移到 mp_control table 中
        // 不用查 country_state table.
        $this->destination = CountryState::firstOrNew([
            'country_id' => $marketplaceProduct->mpControl->country_id,
            'is_default_state' => 1,
        ]);
        if ($this->destination->state_id === null) {
            $this->destination->state_id = '';
        }

        $this->exchangeRate = ExchangeRate::whereFromCurrencyId('HKD')
            ->whereToCurrencyId($marketplaceProduct->mpControl->currency_id)
            ->firstOrFail();

        $declaredValue = $this->getDeclaredValue($marketplaceProduct);
        $tax = $this->getTax($marketplaceProduct, $declaredValue);
        $duty = $this->getDuty($marketplaceProduct, $declaredValue);

        $esgCommission = $this->getEsgCommission($marketplaceProduct);
        $marketplaceCommission = $this->getMarketplaceCommission($marketplaceProduct);
        $marketplaceListingFee = $this->getMarketplaceListingFee($marketplaceProduct);
        $marketplaceFixedFee = $this->getMarketplaceFixedFee($marketplaceProduct);
        $paymentGatewayFee = $this->getPaymentGatewayFee($marketplaceProduct);
        $paymentGatewayAdminFee = $this->getPaymentGatewayAdminFee($marketplaceProduct);

        $availableDeliveryTypeWithCost = $this->deliveryQuotationRepository->getQuotationCost($marketplaceProduct);

        $supplierCost = $this->getSupplierCost($marketplaceProduct);
        $accessoryCost = $this->getAccessoryCost($marketplaceProduct);
        $pricingType = $this->getMerchantType($marketplaceProduct);
        $deliveryCharge = 0;
        $sellingPrice = $marketplaceProduct->price;
        $totalCharged = $marketplaceProduct->price + $deliveryCharge;

        $totalCostExcludeDeliveryCost = array_sum([
            $tax, $duty, $esgCommission, $marketplaceCommission,
            $marketplaceListingFee, $marketplaceFixedFee, $paymentGatewayFee, $paymentGatewayAdminFee,
            $supplierCost, $accessoryCost, $deliveryCharge,
        ]);

        $availableShippingWithProfit = $availableDeliveryTypeWithCost->map(function ($shippingType) use ($sellingPrice, $totalCharged, $pricingType, $esgCommission, $totalCostExcludeDeliveryCost) {
            $shippingType->put('totalCost', $shippingType->get('deliveryCost') + $totalCostExcludeDeliveryCost);
            if ($pricingType == 'revenue') {
                $shippingType->put('profit', $esgCommission);
            } elseif ($pricingType == 'cost') {
                $shippingType->put('profit', $totalCharged - $shippingType->get('totalCost'));
            }
            $shippingType->put('margin', round($shippingType->get('profit') / $sellingPrice * 100, 2));

            return $shippingType;
        });

        return $availableShippingWithProfit;
    }

    public function getMerchantType(MarketplaceSkuMapping $marketplaceProduct)
    {
        // TODO
        // BAD TASTE
        // refactor later.
        $merchantInfo = MerchantProductMapping::join('merchant_client_type', 'merchant_client_type.merchant_id', '=', 'merchant_product_mapping.merchant_id')
            ->select(['revenue_value', 'cost_value'])
            ->where('sku', '=', $marketplaceProduct->sku)
            ->where('merchant_client_type.client_type', '=', 'ACCELERATOR')
            ->firstOrFail();

        if ($merchantInfo->revenue_value !== null) {
            return 'revenue';
        } elseif ($merchantInfo->cost_value !== null) {
            return 'cost';
        } else {
            return false;
        }
    }

    public function getEsgCommission(MarketplaceSkuMapping $marketplaceProduct)
    {
        $esgCommission = 0;
        $merchantInfo = MerchantProductMapping::join('merchant_client_type', 'merchant_client_type.merchant_id', '=', 'merchant_product_mapping.merchant_id')
            ->select(['revenue_value', 'cost_value'])
            ->where('sku', '=', $marketplaceProduct->sku)
            ->where('merchant_client_type.client_type', '=', 'ACCELERATOR')
            ->firstOrFail();

        if ($merchantInfo->revenue_value) {
            $esgCommission = $marketplaceProduct->price * $merchantInfo->revenue_value / 100;
        }

        return round($esgCommission, 2);
    }

    public function getDeclaredValue(MarketplaceSkuMapping $marketplaceProduct)
    {
        $exception = DeclarationException::where('platform_type', '=', 'ACCELERATOR')
            ->select(['absolute_value', 'declared_ratio', 'max_absolute_value'])
            ->where('delivery_country_id', '=', $marketplaceProduct->mpControl->country_id)
            ->where('ref_from_amt', '>=', $marketplaceProduct->price)
            ->where('ref_to_amt_exclusive', '<', $marketplaceProduct->price)
            ->where('status', '=', 1)
            ->first();

        if ($exception) {
            if ($exception->absolute_value > 0) {
                $declaredValue = $exception->absolute_value;
            } else {
                $declaredValue = $exception->declared_ratio * $marketplaceProduct->price / 100;
                $declaredValue = ($exception->max_absolute_value > 0) ? min($declaredValue, $exception->max_absolute_value) : $declaredValue;
            }
        } else {
            $exception = Declaration::where('platform_type', '=', 'ACCELERATOR')
                ->select(['default_declaration_percent'])
                ->firstOrFail();
            $declaredValue = $exception->default_declaration_percent * $marketplaceProduct->price / 100;
        }

        return round($declaredValue, 2);
    }

    public function getTax(MarketplaceSkuMapping $marketplaceProduct, $declaredValue)
    {
        $tax = 0;
        $countryTax = CountryTax::where('country_id', '=', $this->destination->country_id)
            ->select(['tax_percentage', 'critical_point_threshold'])
            ->where('state_id', '=', $this->destination->state_id)
            ->first();

        if ($countryTax) {
            $tax = $countryTax->tax_percentage * $declaredValue / 100;
            if ($marketplaceProduct->price > $countryTax->critical_point_threshold) {
                $tax = $countryTax->absolute_amount + $tax;
            }
        }

        return round($tax, 2);
    }

    public function getDuty(MarketplaceSkuMapping $marketplaceProduct, $declaredValue)
    {
        $dutyInfo = HscodeDutyCountry::join('product', 'product.hscode_cat_id', '=', 'hscode_duty_country.hscode_cat_id')
            ->select(['hscode_duty_country.duty_in_percent'])
            ->where('sku', '=', $marketplaceProduct->sku)
            ->where('hscode_duty_country.country_id', '=', $marketplaceProduct->mpControl->country_id)
            ->firstOrFail();

        return round($declaredValue * $dutyInfo->duty_in_percent / 100, 2);
    }

    public function getMarketplaceCommission(MarketplaceSkuMapping $marketplaceProduct)
    {
        try {
            return round(min($marketplaceProduct->price * $marketplaceProduct->mpCategoryCommission->mp_commission / 100, $marketplaceProduct->mpCategoryCommission->maximum), 2);
        } catch (\Exception $e) {
            // TODO
            // maybe user not set marketplace commission
            // show notice to user.
        }
    }

    public function getMarketplaceListingFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $marketplaceListingFee = 0;
        $mpListingFee = MpListingFee::select('mp_listing_fee')
            ->where('control_id', '=', $marketplaceProduct->mp_control_id)
            ->where('from_price', '<=', $marketplaceProduct->price)
            ->where('to_price', '>', $marketplaceProduct->price)
            ->first();

        if ($mpListingFee) {
            $marketplaceListingFee = $mpListingFee->mp_listing_fee;
        }

        return round($marketplaceListingFee, 2);
    }

    public function getMarketplaceFixedFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $marketplaceFixedFee = 0;
        $mpFixedFee = MpFixedFee::select('mp_fixed_fee')
            ->where('control_id', '=', $marketplaceProduct->mp_control_id)
            ->where('from_price', '<=', $marketplaceProduct->price)
            ->where('to_price', '>', $marketplaceProduct->price)
            ->first();

        if ($mpFixedFee) {
            $marketplaceFixedFee = $mpFixedFee->mp_fixed_fee;
        }

        return round($marketplaceFixedFee, 2);
    }

    public function getPaymentGatewayFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        // TODO
        // 在 payment_gateway table 中添加 mp_control id 进行关联起来
        $account = substr($marketplaceProduct->marketplace_id, 0, 2);
        $marketplaceId = substr($marketplaceProduct->marketplace_id, 2);
        $countryCode = $marketplaceProduct->mpControl->country_id;
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        $paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate;

        return round($marketplaceProduct->price * $paymentGatewayRate / 100, 2);
    }

    public function getPaymentGatewayAdminFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        // TODO
        // 在 payment_gateway table 中添加 mp_control id 进行关联起来
        $account = substr($marketplaceProduct->marketplace_id, 0, 2);
        $marketplaceId = substr($marketplaceProduct->marketplace_id, 2);
        $countryCode = $marketplaceProduct->mpControl->country_id;
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        $paymentGateway = PaymentGateway::findOrFail($paymentGatewayId);
        $paymentGatewayAdminFee = $paymentGateway->admin_fee_abs + $marketplaceProduct->price * $paymentGateway->admin_fee_percent / 100;

        return round($paymentGatewayAdminFee, 2);
    }

    public function getSupplierCost(MarketplaceSkuMapping $marketplaceProduct)
    {
        try {
            return round($marketplaceProduct->supplierProduct->pricehkd * $this->exchangeRate->rate / $this->adjustRate, 2);
        } catch (\Exception $e) {
            // maybe supplier_prod table don't have record.
            // TODO
            // show notice to user.
        }
    }

    public function getAccessoryCost(MarketplaceSkuMapping $marketplaceProduct)
    {
        $accessoryCost = 0;
        $accessoryProduct = ProductComplementaryAcc::whereMainprodSku($marketplaceProduct->product->sku)
            ->whereDestCountryId($marketplaceProduct->mpControl->country_id)
            ->whereStatus(1)
            ->first();

        if ($accessoryProduct) {
            $accessoryProductCost = SupplierProd::where('prod_sku', '=', $accessoryProduct->accessory_sku)
                ->where('order_default', '=', 1)
                ->firstOrFail();

            $accessoryCost = $accessoryProductCost->pricehkd * $this->exchangeRate->rate / $this->adjustRate;
        }

        return round($accessoryCost, 2);
    }
}
