<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CountryState;
use App\Models\CountryTax;
use App\Models\Declaration;
use App\Models\DeclarationException;
use App\Models\ExchangeRate;
use App\Models\HscodeDutyCountry;
use App\Models\Marketplace;
use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantClientType;
use App\Models\MerchantProductMapping;
use App\Models\MerchantQuotation;
use App\Models\MpCategoryCommission;
use App\Models\MpControl;
use App\Models\MpFixedFee;
use App\Models\MpListingFee;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductComplementaryAcc;
use App\Models\Quotation;
use App\Models\SkuMapping;
use App\Models\SupplierProd;
use App\Models\WeightCourier;
use Illuminate\Http\Request;

use App\Http\Requests;

class PricingController extends Controller
{
    private $product;
    private $destination;
    private $exchangeRate;
    private $marketplaceControl;

    private $adjustRate = 0.9725;

    public function index()
    {
        $data = [];
        $data['brands'] = Brand::whereStatus(1)->get(['id', 'brand_name']);
        $data['marketplaces'] = Marketplace::whereStatus(1)->get(['id']);
        return response()->view('pricing.pricing-index', $data);
    }

    public function getPriceInfo(Request $request)
    {
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereMarketplaceSku($request->input('marketplaceSku'))
            ->whereMarketplaceId($request->input('marketplace'))
            ->get();

        $result = [];
        foreach ($marketplaceSkuMapping as $mappingItem) {
            $request->merge([
                'country' => $mappingItem->country_id,
                'price' => $mappingItem->price,
                'sku' => $mappingItem->product->sku,
                'selectedDeliveryType' => $mappingItem->delivery_type,
            ]);

            $result[$request->input('marketplace').$request->input('country')]['deliveryOptions'] = $this->getPricingInfo($request);
            $result[$request->input('marketplace').$request->input('country')]['listingStatus'] = $mappingItem->listing_status;
        }
        return response()->view('pricing.pricing-table', ['data' => $result]);
        //return response()->json($result);
    }

    public function simulate(Request $request)
    {
        $countryCode = substr($request->input('sellingPlatform'), -2);
        $marketplaceId = substr($request->input('sellingPlatform'), 0, -2);
        $marketplaceMapping = MarketplaceSkuMapping::whereMarketplaceSku($request->input('marketplaceSku'))
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryCode)
            ->firstOrFail();

        $request->merge([
            'marketplace' => $marketplaceMapping->marketplace_id,
            'country' => $marketplaceMapping->country_id,
            'sku' => $marketplaceMapping->product->sku,
            'selectedDeliveryType' => $marketplaceMapping->delivery_type,
        ]);
        $result[$request->input('sellingPlatform')]['deliveryOptions'] = $this->getPricingInfo($request);
        $result[$request->input('sellingPlatform')]['listingStatus'] = $marketplaceMapping->listing_status;

        return response()->view('pricing.platform-pricing-info', ['data' => $result]);
    }

    public function getSkuList(Request $request)
    {
        $sql = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku');

        if ($request->input('master_sku')) {
            $sql = $sql->join('sku_mapping', 'sku_mapping.sku', '=', 'product.sku')
                        ->where('sku_mapping.ext_sku', '=', $request->input('master_sku'));
        }

        if ($request->input('esg_sku')) {
            $sql = $sql->where('product.sku', '=', $request->input('esg_sku'));
        }

        if ($request->input('product_name')) {
            $sql = $sql->where('product.name', 'like', '%'.$request->input('product_name').'%');
        }

        if ($request->input('brand_id')) {
            $sql = $sql->where('product.brand_id', '=', $request->input('brand_id'));
        }

        if ($request->input('marketplace')) {
            $sql = $sql->whereMarketplaceId($request->input('marketplace'));
        }

        $marketplaceSkuMapping = $sql->groupBy('marketplace_sku')
            ->get(['marketplace_sku', 'marketplace_id', 'product.sku', 'name', 'price']);

        return response()->view('pricing.listing-sku', ['data' => $marketplaceSkuMapping]);
    }

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

        $esgCommission = $this->getEsgCommission($request);
        $marketplaceCommission = $this->getMarketplaceCommission($request);
        $marketplaceListingFee = $this->getMarketplaceListingFee($request);
        $marketplaceFixedFee = $this->getMarketplaceFixedFee($request);
        $paymentGatewayFee = $this->getPaymentGatewayFee($request);
        $paymentGatewayAdminFee = $this->getPaymentGatewayAdminFee($request);
        $freightCost = $this->getQuotationCost($request);
        $supplierCost = $this->getSupplierCost($request);
        $accessoryCost = $this->getAccessoryCost($request);
        $deliveryCharge = 0;

        $pricingType = $this->getMerchantType($request);

        $totalCharged = $request->input('price') + $deliveryCharge;

        $priceInfo = [];
        $deliveryOptions = ['STD', 'EXPED', 'EXP', 'FBA', 'MCF'];
        foreach ($deliveryOptions as $deliveryType) {
            if (in_array($deliveryType, array_keys($freightCost))) {
                $priceInfo[$deliveryType] = [];
                $priceInfo[$deliveryType]['tax'] = $tax;
                $priceInfo[$deliveryType]['duty'] = $duty;
                $priceInfo[$deliveryType]['esgCommission'] = $esgCommission;
                $priceInfo[$deliveryType]['marketplaceCommission'] = $marketplaceCommission;
                $priceInfo[$deliveryType]['marketplaceListingFee'] = $marketplaceListingFee;
                $priceInfo[$deliveryType]['marketplaceFixedFee'] = $marketplaceFixedFee;
                $priceInfo[$deliveryType]['paymentGatewayFee'] = $paymentGatewayFee;
                $priceInfo[$deliveryType]['paymentGatewayAdminFee'] = $paymentGatewayAdminFee;
                $priceInfo[$deliveryType]['freightCost'] = $freightCost[$deliveryType];
                $priceInfo[$deliveryType]['supplierCost'] = $supplierCost;
                $priceInfo[$deliveryType]['accessoryCost'] = $accessoryCost;
                $priceInfo[$deliveryType]['deliveryCharge'] = $deliveryCharge;
                $priceInfo[$deliveryType]['totalCost'] = array_sum($priceInfo[$deliveryType]);

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
                    if ($pricingType == 'revenue') {
                        $priceInfo[$deliveryType]['profit'] = $esgCommission;
                    } elseif ($pricingType == 'cost') {
                        $priceInfo[$deliveryType]['profit'] = $priceInfo[$deliveryType]['totalCharged'] - $priceInfo[$deliveryType]['totalCost'];
                    }
                    $priceInfo[$deliveryType]['margin'] = round($priceInfo[$deliveryType]['profit'] / $request->input('price') * 100, 2);
                } else {
                    $priceInfo[$deliveryType]['profit'] = 'N/A';
                    $priceInfo[$deliveryType]['margin'] = 'N/A';
                }
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
        if ($merchantInfo->revenue_value) {
            return 'revenue';
        } elseif ($merchantInfo->cost_value) {
            return 'cost';
        } else {
            return false;
        }
    }

    public function getEsgCommission(Request $request)
    {
        $esgCommission = 0;
        $merchantInfo = MerchantProductMapping::join('merchant_client_type', 'merchant_client_type.merchant_id', '=', 'merchant_product_mapping.merchant_id')
            ->select(['revenue_value', 'cost_value'])
            ->where('sku', '=', $request->sku)
            ->where('merchant_client_type.client_type', '=', 'ACCELERATOR')
            ->firstOrFail();

        if ($merchantInfo->revenue_value) {
            $esgCommission = $request->price * $merchantInfo->revenue_value;
        }

        return round($esgCommission, 2);
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
        $categoryCommission = MpCategoryCommission::join('mp_category', 'mp_category.id', '=', 'mp_category_commission.mp_id')
            ->select(['mp_commission', 'maximum'])
            ->where('mp_category.control_id', '=', $this->marketplaceControl->control_id)
            ->where('mp_category.esg_cat_id', '=', $this->product->cat_id)
            ->where('mp_category.esg_sub_cat_id', '=', $this->product->sub_cat_id)
            ->where('mp_category.esg_sub_sub_cat_id', '=', $this->product->sub_sub_cat_id)
            //->where('from_price', '<=', $request->price)
            //->where('to_price', '>', $request->price)
            ->firstOrFail();


        $marketplaceCommission = min($request->input('price') * $categoryCommission->mp_commission / 100, $categoryCommission->maximum);

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
            ->where('from_price', '<=', $request->input('price'))
            ->where('to_price', '>', $request->input('price'))
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

        $mpFixedFee =  MpFixedFee::select('mp_fixed_fee')
            ->where('control_id', '=', $controlId)
            ->where('from_price', '<=', $request->input('price'))
            ->where('to_price', '>', $request->input('price'))
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

        $paymentGatewayId = strtolower(implode('_',[$account, $marketplaceId, $countryCode]));
        $paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate;

        return round($request->input('price') * $paymentGatewayRate / 100, 2);
    }

    public function getPaymentGatewayAdminFee(Request $request)
    {
        $account = substr($request->input('marketplace'), 0, 2);
        $marketplaceId = substr($request->input('marketplace'), 2);
        $countryCode = $request->input('country');
        $countryCode = ($countryCode == 'GB') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_',[$account, $marketplaceId, $countryCode]));
        $paymentGateway = PaymentGateway::findOrFail($paymentGatewayId);
        $paymentGatewayAdminFee = $paymentGateway->admin_fee_abs + $request->input('price') * $paymentGateway->admin_fee_percent / 100;

        return round($paymentGatewayAdminFee, 2);
    }

    public function getQuotationCost(Request $request)
    {
        $freightCost = [];
        $quotation = new Quotation();
        $quotationVersion = $quotation->getAcceleratorQuotationByProduct($this->product);

        $actualWeight = WeightCourier::getWeightId($this->product->weight);
        $volumeWeight = WeightCourier::getWeightId($this->product->vol_weight);
        $battery = $this->product->battery;

        if ($battery == 1) {
            $quotationVersion->forget('acc_external_postage');
        }

        $quotation = collect();
        foreach ($quotationVersion as $quotationType => $quotationVersionId) {
            if (($quotationType == 'acc_builtin_postage') || ($quotationType == 'acc_external_postage') ) {
                $weight = $actualWeight;
            } else {
                $weight = max($actualWeight, $volumeWeight);
            }

            $quotationItem = Quotation::getQuotation($this->destination, $weight, $quotationVersionId);
            if ($quotationItem) {
                $quotation->push($quotationItem);
            }
        }

        $availableQuotation = $quotation->filter(function ($quotationItem) use ($battery) {
                switch ($battery) {
                    case '1':
                        if ($quotationItem->courierInfo->allow_builtin_battery) {
                            return true;
                        }
                        break;

                    case '2':
                        if ($quotationItem->courierInfo->allow_external_battery) {
                            return true;
                        }
                        break;

                    default:
                        return true;
                        break;
                }
            });

        // TODO: if $availableQuotation contains both built-in and external quotation, should choose the cheapest quotation.

        // convert HKD to target currency.
        $currencyRate = $this->exchangeRate->rate;
        $adjustRate = $this->adjustRate;
        $quotationCost = $availableQuotation->map(function ($item) use ($currencyRate, $adjustRate) {
            $item->cost = round($item->cost * $currencyRate / $adjustRate, 2);
            return $item;
        })->pluck('cost', 'quotation_type')->toArray();

        foreach ($quotationCost as $quotationType => $cost) {
            switch ($quotationType) {
                case 'acc_builtin_postage':
                case 'acc_external_postage':
                    $freightCost['STD'] = $cost;
                    break;

                case 'acc_courier':
                    $freightCost['EXPED'] = $cost;
                    break;

                case 'acc_courier_exp':
                    $freightCost['EXP'] = $cost;
                    break;

                case 'acc_fba':
                    $freightCost['FBA'] = $cost;
                    break;

                case 'acc_mcf':
                    $freightCost['MCF'] = $cost;
                    break;
            }
        }

        return $freightCost;
    }

    public function getSupplierCost(Request $request)
    {
        $supplierProd = SupplierProd::where('prod_sku', '=', $this->product->sku)
            ->where('order_default', '=', 1)
            ->firstOrFail();

        return round($supplierProd->pricehkd * $this->exchangeRate->rate / $this->adjustRate, 2);
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
}
