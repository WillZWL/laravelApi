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
use App\Models\Warehouse;
use App\Repository\DeliveryQuotationRepository;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;

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

        $costDetails = [];

        $declaredValue = $this->getDeclaredValue($marketplaceProduct);

        $costDetails['tax'] = $this->getTax($marketplaceProduct, $declaredValue);
        $costDetails['duty'] = $this->getDuty($marketplaceProduct, $declaredValue);
        $costDetails['marketplaceCommission'] =  $this->getMarketplaceCommission($marketplaceProduct);
        $costDetails['marketplaceListingFee'] =  $this->getMarketplaceListingFee($marketplaceProduct);
        $costDetails['marketplaceFixedFee'] =  $this->getMarketplaceFixedFee($marketplaceProduct);
        $costDetails['paymentGatewayFee'] =  $this->getPaymentGatewayFee($marketplaceProduct);
        $costDetails['paymentGatewayAdminFee'] =  $this->getPaymentGatewayAdminFee($marketplaceProduct);
        $costDetails['supplierCost'] =  $this->getSupplierCost($marketplaceProduct);
        $costDetails['accessoryCost'] =  $this->getAccessoryCost($marketplaceProduct);
        $costDetails['tuvFee'] =  $this->getTuvFee($marketplaceProduct);
        $costDetails['deliveryCharge'] = 0;

        $availableDeliveryTypeWithCost =  $this->getShippingOptions($marketplaceProduct);
        $warehouseCostDetails =  $this->getWarehouseCost($marketplaceProduct);

        $pricingType = $this->getMerchantType($marketplaceProduct);
        $sellingPrice = $marketplaceProduct->price;
        $totalCharged = $marketplaceProduct->price + $costDetails['deliveryCharge'];

        $availableDeliveryTypeWithCost = $availableDeliveryTypeWithCost->keyBy('deliveryType');
        // SBF 10879
        if (substr($marketplaceProduct->marketplace_id, 2) === 'AMAZON') {
            $availableDeliveryTypeWithCost = $availableDeliveryTypeWithCost->forget('STD');
        }

        $availableShippingWithProfit = $availableDeliveryTypeWithCost->map(function ($shippingOption) use (
            $sellingPrice,
            $totalCharged,
            $pricingType,
            $warehouseCostDetails,
            $costDetails,
            $marketplaceProduct
        ) {
            $bookInCost = $warehouseCostDetails['book_in_cost'];
            $pnpCost = $warehouseCostDetails['pnp_cost'];
            $bookOutCost = $warehouseCostDetails['book_out_cost'];
            $labelingCost = $warehouseCostDetails['labeling_cost'];

            if ($shippingOption['deliveryType'] === 'FBA' || $shippingOption['deliveryType'] === 'SBN') {
                $pnpCost = 0;
            } else {
                $labelingCost = 0;
            }

            $costDetails['warehouseCost'] = $bookInCost + $bookOutCost + $pnpCost + $labelingCost;

            $costDetails['fulfilmentByMarketplaceFee'] = 0;
            if (($shippingOption['deliveryType'] === 'FBA'
                    && substr($marketplaceProduct->mpControl->marketplace_id, 2) === 'AMAZON')
                || ($shippingOption['deliveryType'] === 'SBN'
                    && substr($marketplaceProduct->mpControl->marketplace_id, 2) === 'NEWEGG')
            ) {
                $fbaFees = $marketplaceProduct->amazonFbaFee;
                $costDetails['fulfilmentByMarketplaceFee'] = $fbaFees->storage_fee + $fbaFees->order_handing_fee
                    + $fbaFees->pick_and_pack_fee + $fbaFees->weight_handing_fee;

                if (substr($marketplaceProduct->mpControl->marketplace_id, 2) === 'AMAZON'
                    && $marketplaceProduct->mpControl->country_id === 'GB' && $sellingPrice >= 300
                ) {
                    $costDetails['fulfilmentByMarketplaceFee'] = 0;
                }

                if (substr($marketplaceProduct->mpControl->marketplace_id, 2) === 'NEWEGG'
                    && $sellingPrice >= 300
                    && in_array($marketplaceProduct->amazonProductSizeTier->product_size, [14, 15])
                ) {
                    $costDetails['fulfilmentByMarketplaceFee'] = 0;
                }
            }

            $costDetails['deliveryCost'] = $shippingOption['cost'];

            $option = [];
            $option['deliveryCost'] = $shippingOption['cost'];
            $option['totalCost'] = array_sum($costDetails);
            $option['profit'] = round($totalCharged - $option['totalCost'], 2);
            $option['margin'] = round($option['profit'] / $sellingPrice * 100, 2);
            $option = array_merge($option, $costDetails);

            return $option;
        });

        return $availableShippingWithProfit;
    }

    public function getShippingOptions(MarketplaceSkuMapping $marketplaceProduct)
    {
        $this->shippingService = new ShippingService(
            new AcceleratorShippingRepository,
            new MarketplaceProductRepository
        );
        $shippingOptions = $this->shippingService->shippingOptions($marketplaceProduct->id);

        // convert HKD to target currency.
        $currencyRate = $this->exchangeRate->rate;
        $adjustRate = $this->adjustRate;
        $shippingOptions->transform(function ($option) use ($currencyRate, $adjustRate) {
            $option['cost'] = round($option['cost'] * $currencyRate / $adjustRate, 2);
            return $option;
        });

        return $shippingOptions;
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

    public function getTargetMargin(MarketplaceSkuMapping $marketplaceProduct)
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
            ->where('to_price', '>=', $marketplaceProduct->price)
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
            ->where('to_price', '>=', $marketplaceProduct->price)
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
        if ($marketplaceProduct->marketplace_id == 'ETALIBABA') {
            $paymentGatewayId = 'et_alipay_alibaba';
        } else {
            $account = substr($marketplaceProduct->marketplace_id, 0, 2);
            $marketplaceId = substr($marketplaceProduct->marketplace_id, 2);
            $countryCode = $marketplaceProduct->mpControl->country_id;
            $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;
            $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        }

        $paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate;

        return round($marketplaceProduct->price * $paymentGatewayRate / 100, 2);
    }

    public function getPaymentGatewayAdminFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        // TODO
        // 在 payment_gateway table 中添加 mp_control id 进行关联起来
        if ($markplaceProduct->marketplace_id == 'ETALIBABA') {
            $paymentGatewayId = 'et_alipay_alibaba';
        } else {
            $account = substr($marketplaceProduct->marketplace_id, 0, 2);
            $marketplaceId = substr($marketplaceProduct->marketplace_id, 2);
            $countryCode = $marketplaceProduct->mpControl->country_id;
            $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;
            $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        }

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

    public function getWarehouseCost($marketplaceProduct)
    {
        $warehouseCost = ['book_in_cost' => 0, 'pnp_cost' => 0, 'book_out_cost' => 0, 'labeling_cost' => 0];

        $warehouseId = $marketplaceProduct->product->default_ship_to_warehouse;
        if (!$warehouseId) {
            $warehouseId = $marketplaceProduct->product->merchantProductMapping->merchant->default_ship_to_warehouse;
        }

        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
            $currencyRate = ExchangeRate::whereFromCurrencyId($warehouse->currency_id)
                ->whereToCurrencyId($marketplaceProduct->mpControl->currency_id)
                ->first()->rate;

            $weight = ($marketplaceProduct->product->weight > $marketplaceProduct->product->vol_weight)
                        ? $marketplaceProduct->product->weight : $marketplaceProduct->product->vol_weight;

            // above 1kg, need to calculate addition weight.
            $additionWeight = ceil($weight - 1);

            $warehouseCost['book_in_cost'] = round(( $warehouse->warehouseCost->book_in_fixed
                    + $warehouse->warehouseCost->additional_book_in_per_kg * $additionWeight)
                * $currencyRate / $this->adjustRate, 2);

            $warehouseCost['pnp_cost'] = round(($warehouse->warehouseCost->pnp_fixed
                    + $warehouse->warehouseCost->additional_pnp_per_kg * $additionWeight )
                * $currencyRate / $this->adjustRate, 2);

            $warehouseCost['book_out_cost'] = round(($warehouse->warehouseCost->book_out_fixed
                    + $warehouse->warehouseCost->additional_book_out_per_kg * $additionWeight)
                * $currencyRate / $this->adjustRate, 2);

            $warehouseCost['labeling_cost'] = round($warehouse->warehouseCost->labeling_cost_per_item
                * $currencyRate / $this->adjustRate, 2);
        }

        return $warehouseCost;
    }

    public function getTuvFee($marketplaceProduct)
    {
        $tuvFee = 0;
        $acceleratorMerchant = $marketplaceProduct->product->merchantProductMapping->merchant->merchantClientType()->where('client_type', 'ACCELERATOR')->first();
        if ($acceleratorMerchant && ($acceleratorMerchant->q_rated == 1)) {
            $tuvFee = round(0.02 * $marketplaceProduct->price, 2);
        }

        return $tuvFee;
    }
}
