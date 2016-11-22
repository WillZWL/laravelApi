<?php

namespace App\Services;

use App\Models\CountryState;
use App\Models\CountryTax;
use App\Models\Declaration;
use App\Models\DeclarationException;
use App\Models\ExchangeRate;
use App\Models\HscodeDutyCountry;
use App\Models\Marketplace;
use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantProductMapping;
use App\Models\MpCategoryCommission;
use App\Models\MpControl;
use App\Models\MpFixedFee;
use App\Models\MpListingFee;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductComplementaryAcc;
use App\Models\Quotation;
use App\Models\SupplierProd;
use App\Models\Warehouse;
use App\Models\WeightCourier;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;
use Illuminate\Http\Request;

class PricingToolService
{
    private $product;
    private $destination;
    private $exchangeRate;
    private $marketplaceControl;
    private $adjustRate = 0.9725;

    public function getPricingInfo(Request $request)
    {
        $this->product = Product::findOrFail($request->input('sku'));
        $this->destination = CountryState::firstOrNew(['country_id' => $request->input('country'), 'is_default_state' => 1]);
        if ($this->destination->state_id === null) {
            $this->destination->state_id = '';
        }

        $this->marketplaceControl = MpControl::whereMarketplaceId($marketplaceId = $request->input('marketplace'))
            ->whereCountryId($this->destination->country_id)
            ->firstOrFail();

        $this->exchangeRate = ExchangeRate::whereFromCurrencyId('HKD')
            ->whereToCurrencyId($this->marketplaceControl->currency_id)
            ->firstOrFail();

        $declaredValue = $this->getDeclaredValue($request);
        $tax = $this->getTax($request, $declaredValue);
        $duty = $this->getDuty($request, $declaredValue);

        $targetMargin = $this->getTargetMargin($request);
        $marketplaceCommission = $this->getMarketplaceCommission($request);
        $marketplaceListingFee = $this->getMarketplaceListingFee($request);
        $marketplaceFixedFee = $this->getMarketplaceFixedFee($request);
        $paymentGatewayFee = $this->getPaymentGatewayFee($request);
        $paymentGatewayAdminFee = $this->getPaymentGatewayAdminFee($request);
        $shippingOptions = $this->getShippingOptions($request);
        $warehouseCostDetails = $this->getWarehouseCost($request);
        $supplierCost = $this->getSupplierCost($request);
        $accessoryCost = $this->getAccessoryCost($request);
        $tuvFee = $this->getTuvFee($request);
        $deliveryCharge = 0;

        $totalCharged = $request->input('price') + $deliveryCharge;

        $priceInfo = [];

        foreach ($shippingOptions as $shippingOption) {
            $bookInCost = $warehouseCostDetails['book_in_cost'];
            $pnpCost = $warehouseCostDetails['pnp_cost'];

            if ($shippingOption['deliveryType'] === 'FBA' || $shippingOption['deliveryType'] === 'SBN') {
                $pnpCost = 0;
            }

            $totalFbaFee = 0;
            if ($shippingOption['deliveryType'] === 'FBA' && substr($this->marketplaceControl->marketplace_id, 2) === 'AMAZON') {

                $fbafees = $this->getFbaFees($request);
                $totalFbaFee = $fbafees->storage_fee + $fbafees->order_handing_fee
                    + $fbafees->pick_and_pack_fee + $fbafees->weight_handing_fee;

                if ($request->input('country') == 'GB' && $request->input('price') >= 300) {
                    $totalFbaFee = $totalFbaFee - $fbafees->weight_handing_fee;
                }
            }

            $deliveryType = $shippingOption['deliveryType'];

            $priceInfo[$deliveryType] = [];
            $priceInfo[$deliveryType]['tax'] = $tax;
            $priceInfo[$deliveryType]['duty'] = $duty;
            $priceInfo[$deliveryType]['marketplaceCommission'] = $marketplaceCommission;
            $priceInfo[$deliveryType]['marketplaceListingFee'] = $marketplaceListingFee;
            $priceInfo[$deliveryType]['marketplaceFixedFee'] = $marketplaceFixedFee;
            $priceInfo[$deliveryType]['paymentGatewayFee'] = $paymentGatewayFee;
            $priceInfo[$deliveryType]['paymentGatewayAdminFee'] = $paymentGatewayAdminFee;
            $priceInfo[$deliveryType]['freightCost'] = $shippingOption['cost'];
            $priceInfo[$deliveryType]['warehouseCost'] = $bookInCost + $pnpCost;
            $priceInfo[$deliveryType]['supplierCost'] = $supplierCost;
            $priceInfo[$deliveryType]['accessoryCost'] = $accessoryCost;
            $priceInfo[$deliveryType]['deliveryCharge'] = $deliveryCharge;
            $priceInfo[$deliveryType]['totalFbaFee'] = $totalFbaFee;
            $priceInfo[$deliveryType]['tuvFee'] = $tuvFee;
            $priceInfo[$deliveryType]['totalCost'] = array_sum($priceInfo[$deliveryType]);
            $priceInfo[$deliveryType]['targetMargin'] = $targetMargin;

            $priceInfo[$deliveryType]['price'] = $request->input('price');
            $priceInfo[$deliveryType]['declaredValue'] = $declaredValue;
            $priceInfo[$deliveryType]['totalCharged'] = $totalCharged;
            $priceInfo[$deliveryType]['marketplaceSku'] = $request->input('marketplaceSku');

            if ($request->input('selectedDeliveryType') == $deliveryType) {
                $priceInfo[$deliveryType]['checked'] = 'checked';
            } else {
                $priceInfo[$deliveryType]['checked'] = '';
            }

            if ($request->input('price') > 0) {
                $priceInfo[$deliveryType]['profit'] = round($priceInfo[$deliveryType]['totalCharged'] - $priceInfo[$deliveryType]['totalCost'], 2);
                $priceInfo[$deliveryType]['margin'] = round($priceInfo[$deliveryType]['profit'] / $request->input('price') * 100, 2);
            } else {
                $priceInfo[$deliveryType]['profit'] = 'N/A';
                $priceInfo[$deliveryType]['margin'] = 'N/A';
            }
        }

        return $priceInfo;
    }

    public function getMerchantType(Request $request)
    {
        $merchantInfo = MerchantProductMapping::join('merchant_client_type', 'merchant_client_type.merchant_id', '=', 'merchant_product_mapping.merchant_id')
            ->select(['revenue_value', 'cost_value'])
            ->where('sku', '=', $request->sku)
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

    public function getTargetMargin(Request $request)
    {
        $targetMargin = 0;
        $merchantInfo = MerchantProductMapping::join('merchant_client_type', 'merchant_client_type.merchant_id', '=', 'merchant_product_mapping.merchant_id')
            ->select(['revenue_value', 'cost_value'])
            ->where('sku', '=', $request->sku)
            ->where('merchant_client_type.client_type', '=', 'ACCELERATOR')
            ->firstOrFail();

        if ($merchantInfo->revenue_value) {
            $targetMargin = $merchantInfo->revenue_value;
        }

        return round($targetMargin, 2);
    }

    public function getDeclaredValue(Request $request)
    {
        $exception = DeclarationException::where('platform_type', '=', 'ACCELERATOR')
            ->select(['absolute_value', 'declared_ratio', 'max_absolute_value'])
            ->where('delivery_country_id', '=', $this->destination->country_id)
            ->where('ref_from_amt', '>=', $request->input('price'))
            ->where('ref_to_amt_exclusive', '<', $request->input('price'))
            ->where('status', '=', 1)
            ->first();

        if ($exception) {
            if ($exception->absolute_value > 0) {
                $declaredValue = $exception->absolute_value;
            } else {
                $declaredValue = $exception->declared_ratio * $request->input('price') / 100;
                $declaredValue = ($exception->max_absolute_value > 0) ? min($declaredValue, $exception->max_absolute_value) : $declaredValue;
            }
        } else {
            $exception = Declaration::where('platform_type', '=', 'ACCELERATOR')
                ->select(['default_declaration_percent'])
                ->firstOrFail();
            $declaredValue = $exception->default_declaration_percent * $request->input('price') / 100;
        }

        return round($declaredValue, 2);
    }

    public function getTax(Request $request, $declaredValue)
    {
        $tax = 0;
        $countryTax = CountryTax::where('country_id', '=', $this->destination->country_id)
            ->select(['tax_percentage', 'critical_point_threshold'])
            ->where('state_id', '=', $this->destination->state_id)
            ->first();

        if ($countryTax) {
            $tax = $countryTax->tax_percentage * $declaredValue / 100;
            if ($request->input('price') > $countryTax->critical_point_threshold) {
                $tax = $countryTax->absolute_amount + $tax;
            }
        }

        return round($tax, 2);
    }

    public function getDuty(Request $request, $declaredValue)
    {
        $dutyInfo = HscodeDutyCountry::join('product', 'product.hscode_cat_id', '=', 'hscode_duty_country.hscode_cat_id')
            ->select(['hscode_duty_country.duty_in_percent'])
            ->where('sku', '=', $request->sku)
            ->where('hscode_duty_country.country_id', '=', $this->destination->country_id)
            ->firstOrFail();

        return round($declaredValue * $dutyInfo->duty_in_percent / 100, 2);
    }

    public function getMarketplaceCommission(Request $request)
    {
        $marketplaceCommission = 0;

        $categoryCommission = MpCategoryCommission::join('marketplace_sku_mapping', 'mp_id', '=', 'mp_sub_category_id')
            ->where('marketplace_sku', '=', $request->input('marketplaceSku'))
            ->where('marketplace_id', '=', $request->input('marketplace'))
            ->where('country_id', '=', $request->input('country'))
            ->select(['mp_commission', 'maximum'])
            ->first();
        if ($categoryCommission) {
            $marketplaceCommission = min($request->input('price') * $categoryCommission->mp_commission / 100, $categoryCommission->maximum);
        }

        return round($marketplaceCommission, 2);
    }

    public function getMarketplaceListingFee(Request $request)
    {
        $marketplaceListingFee = 0;
        $controlId = MpControl::select(['control_id'])
            ->where('marketplace_id', '=', $request->input('marketplace'))
            ->where('country_id', '=', $this->destination->country_id)
            ->firstOrFail()
            ->control_id;

        $mpListingFee = MpListingFee::select('mp_listing_fee')
            ->where('control_id', '=', $controlId)
            ->where('from_price', '<', $request->input('price'))
            ->where('to_price', '>=', $request->input('price'))
            ->first();

        if ($mpListingFee) {
            $marketplaceListingFee = $mpListingFee->mp_listing_fee;
        }

        return round($marketplaceListingFee, 2);
    }

    public function getMarketplaceFixedFee(Request $request)
    {
        $marketplaceFixedFee = 0;
        $controlId = MpControl::select(['control_id'])
            ->where('marketplace_id', '=', $request->input('marketplace'))
            ->where('country_id', '=', $this->destination->country_id)
            ->firstOrFail()
            ->control_id;

        $mpFixedFee = MpFixedFee::select('mp_fixed_fee')
            ->where('control_id', '=', $controlId)
            ->where('from_price', '<', $request->input('price'))
            ->where('to_price', '>=', $request->input('price'))
            ->first();

        if ($mpFixedFee) {
            $marketplaceFixedFee = $mpFixedFee->mp_fixed_fee;
        }

        return round($marketplaceFixedFee, 2);
    }

    public function getPaymentGatewayFee(Request $request)
    {
        $account = substr($request->input('marketplace'), 0, 2);
        $marketplaceId = substr($request->input('marketplace'), 2);
        $countryCode = $request->input('country');
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        $paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate;

        return round($request->input('price') * $paymentGatewayRate / 100, 2);
    }

    public function getPaymentGatewayAdminFee(Request $request)
    {
        $account = substr($request->input('marketplace'), 0, 2);
        $marketplaceId = substr($request->input('marketplace'), 2);
        $countryCode = $request->input('country');
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_', [$account, $marketplaceId, $countryCode]));
        $paymentGateway = PaymentGateway::findOrFail($paymentGatewayId);
        $paymentGatewayAdminFee = $paymentGateway->admin_fee_abs + $request->input('price') * $paymentGateway->admin_fee_percent / 100;

        return round($paymentGatewayAdminFee, 2);
    }

    public function getShippingOptions(Request $request)
    {
        $this->shippingService = new ShippingService(new AcceleratorShippingRepository, new MarketplaceProductRepository);
        $shippingOptions = $this->shippingService->shippingOptions($request->input('id'));

        // convert HKD to target currency.
        $currencyRate = $this->exchangeRate->rate;
        $adjustRate = $this->adjustRate;
        $shippingOptions->transform(function ($option) use ($currencyRate, $adjustRate) {
            $option['cost'] = round($option['cost'] * $currencyRate / $adjustRate, 2);
            return $option;
        });

        return $shippingOptions;
    }

    public function getSupplierCost(Request $request)
    {
        $supplierProd = SupplierProd::where('prod_sku', '=', $this->product->sku)
            ->where('order_default', '=', 1)
            ->firstOrFail();

        return round($supplierProd->pricehkd * $this->exchangeRate->rate / $this->adjustRate, 2);
    }

    public function getWarehouseCost(Request $request)
    {
        $warehouseCost = ['book_in_cost' => 0, 'pnp_cost' => 0];

        $warehouseId = $this->product->default_ship_to_warehouse;
        if (!$warehouseId) {
            $warehouseId = $this->product->merchantProductMapping->merchant->default_ship_to_warehouse;
        }

        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
            $currencyRate = ExchangeRate::whereFromCurrencyId($warehouse->currency_id)
                ->whereToCurrencyId($this->marketplaceControl->currency_id)
                ->first()->rate;

            $weight = ($this->product->weight > $this->product->vol_weight)
                ? $this->product->weight : $this->product->vol_weight;

            // above 1kg, need to calculate addition weight.
            $additionWeight = ceil($weight - 1);

            $warehouseCost['book_in_cost'] = round(( $warehouse->warehouseCost->book_in_fixed
                    + $warehouse->warehouseCost->additional_book_in_per_kg * $additionWeight)
                * $currencyRate / $this->adjustRate, 2);

            $warehouseCost['pnp_cost'] = round(($warehouse->warehouseCost->pnp_fixed
                    + $warehouse->warehouseCost->additional_pnp_per_kg * $additionWeight )
                * $currencyRate / $this->adjustRate, 2);
        }

        return $warehouseCost;
    }

    public function getAccessoryCost(Request $request)
    {
        $accessoryCost = 0;
        $accessoryProduct = ProductComplementaryAcc::whereMainprodSku($this->product->sku)
            ->whereDestCountryId($this->destination->country_id)
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

    public function getFbaFees(Request $request)
    {
        $marketplaceProduct = MarketplaceSkuMapping::find($request->input('id'));

        return $marketplaceProduct->amazonFbaFee;
    }

    public function getTuvFee(Request $request)
    {
        $tuvFee = 0;
        $acceleratorMerchant = $this->product->merchantProductMapping->merchant->merchantClientType()->where('client_type', 'ACCELERATOR')->first();
        if ($acceleratorMerchant && ($acceleratorMerchant->q_rated == 1)) {
            $tuvFee = round(0.02 * $request->input('price'), 2);
        }

        return $tuvFee;
    }
}
