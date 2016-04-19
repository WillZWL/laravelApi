<?php

namespace App\Http\Controllers;

use App\Models\CountryState;
use App\Models\CountryTax;
use App\Models\Declaration;
use App\Models\DeclarationException;
use App\Models\ExchangeRate;
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

    public function index(Request $request)
    {
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereMarketplaceSku($request->marketplace_sku)
            ->where('marketplace_id', '=', 'BCAMAZON')
            ->get();

        $result = [$request->marketplace_sku => []];
        foreach ($marketplaceSkuMapping as $mapping_item) {
            $innerRequest = new Request();
            $innerRequest->sellingPlatform = $mapping_item->marketplace_id.$mapping_item->country_id;
            $innerRequest->marketplaceSku = $request->marketplace_sku;
            $innerRequest->price = $mapping_item->price;
            $this->product = $mapping_item->product;
            $innerRequest->sku = $this->product->sku;
            $result[$request->marketplace_sku][$innerRequest->sellingPlatform] = $this->getPricingInfo($innerRequest);
        }

        echo json_encode($result);
        die();
    }

    public function preCalculateProfit(Request $request)
    {
        $countryCode = substr($request->sellingPlatform, -2);
        $marketplaceId = substr($request->sellingPlatform, 0, -2);
        $marketplaceMapping = MarketplaceSkuMapping::whereMarketplaceSku($request->marketplaceSku)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryCode)
            ->firstOrFail();
        $request->sku = $marketplaceMapping->product->sku;
        $result = [$request->marketplaceSku => []];
        $result[$request->marketplaceSku][$request->sellingPlatform] = $this->getPricingInfo($request);

        echo json_encode($result);
        die();
    }

    public function save(Request $request)
    {
        $countryCode = substr($request->sellingPlatform, -2);
        $marketplaceId = substr($request->sellingPlatform, 0, -2);

        $mapping = MarketplaceSkuMapping::whereMarketplaceSku($request->marketplace_sku)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryCode)
            ->firstOrFail();

        $mapping->delivery_type = $request->delivery_type;
        $mapping->price = $request->price;
        $mapping->profit = $request->profit;
        $mapping->margin = $request->margin;
        if ($mapping->save()) {
            echo json_encode(['success'=>$request->price]);
        } else {
            echo json_encode(['save failure']);
        }

    }

    public function getListSku(Request $request)
    {
        $marketplace = $request->marketplace;
        $masterSku = $request->master_sku;
        $esgSku = $request->sku;
        $productName = $request->product_name;

        $marketplaceSkuMapping = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
            ->select(['marketplace_sku', 'product.sku', 'name', 'price'])
            ->where('product.sku', '=', $esgSku)
            ->whereMarketplaceId($marketplace)
            ->groupBy('marketplace_sku')
            ->get();

        echo json_encode($marketplaceSkuMapping->toJson());
    }

    public function getPricingInfo(Request $request)
    {
        $this->product = Product::findOrFail($request->sku);
        $countryCode = strtoupper(substr($request->sellingPlatform, -2));
        $this->destination = CountryState::firstOrNew(['country_id' => $countryCode, 'is_default_state' => 1]);
        if ($this->destination->state_id === null) {
            $this->destination->state_id = '';
        }

        $marketplaceId = strtoupper(substr($request->sellingPlatform, 0, -2));
        $this->marketplaceControl = MpControl::whereMarketplaceId($marketplaceId)
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

        $totalCharged = $request->price + $deliveryCharge;

        $priceInfo = [];
        $deliveryOptions = ['STD', 'EXPED', 'EXP', 'FBA', 'MCF'];
        foreach ($deliveryOptions as $deliveryType) {
            if (in_array($deliveryType, array_keys($freightCost))) {
                $priceInfo[$deliveryType] = [];
                $priceInfo[$deliveryType]['tax'] = $tax;
                $priceInfo[$deliveryType]['duty'] = $duty;
                $priceInfo[$deliveryType]['esg_commission'] = $esgCommission;
                $priceInfo[$deliveryType]['marketplace_commission'] = $marketplaceCommission;
                $priceInfo[$deliveryType]['marketplace_listing_fee'] = $marketplaceListingFee;
                $priceInfo[$deliveryType]['marketplace_fixed_fee'] = $marketplaceFixedFee;
                $priceInfo[$deliveryType]['payment_gateway_fee'] = $paymentGatewayFee;
                $priceInfo[$deliveryType]['payment_gateway_admin_fee'] = $paymentGatewayAdminFee;
                $priceInfo[$deliveryType]['freight_cost'] = $freightCost[$deliveryType];
                $priceInfo[$deliveryType]['supplier_cost'] = $supplierCost;
                $priceInfo[$deliveryType]['accessory_cost'] = $accessoryCost;
                $priceInfo[$deliveryType]['delivery_charge'] = $deliveryCharge;
                $priceInfo[$deliveryType]['total_cost'] = array_sum($priceInfo[$deliveryType]);

                $priceInfo[$deliveryType]['price'] = $request->price;
                $priceInfo[$deliveryType]['declared_value'] = $declaredValue;
                $priceInfo[$deliveryType]['total_charged'] = $totalCharged;

                if ($request->price > 0) {
                    if ($pricingType == 'revenue') {
                        $priceInfo[$deliveryType]['profit'] = $esgCommission;
                    } elseif ($pricingType == 'cost') {
                        $priceInfo[$deliveryType]['profit'] = $priceInfo[$deliveryType]['total_charged'] - $priceInfo[$deliveryType]['total_cost'];
                    }
                    $priceInfo[$deliveryType]['margin'] = round($priceInfo[$deliveryType]['profit'] / $request->price * 100, 2);
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
            ->where('ref_from_amt', '>=', $request->price)
            ->where('ref_to_amt_exclusive', '<', $request->price)
            ->where('status', '=', 1)
            ->first();

        if ($exception) {
            if ($exception->absolute_value > 0) {
                $declaredValue = $exception->absolute_value;
            } else {
                $declaredValue = $exception->declared_ratio * $request->price / 100;
                $declaredValue = ($exception->max_absolute_value > 0) ? min($declaredValue, $exception->max_absolute_value) : $declaredValue;
            }
        } else {
            $exception = Declaration::where('platform_type', '=', 'ACCELERATOR')
                ->select(['default_declaration_percent'])
                ->firstOrFail();
            $declaredValue = $exception->default_declaration_percent * $request->price / 100;
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
            if ($request->price > $countryTax->critical_point_threshold) {
                $tax = $countryTax->absolute_amount + $tax;
            }
        }

        return round($tax, 2);
    }

    public function getDuty(Request $request, $declaredValue)
    {
        $dutyInfo = Product::join('hscode_duty_country', 'product.hscode_cat_id', '=', 'hscode_duty_country.hscode_cat_id')
            ->select(['hscode_duty_country.duty_in_percent'])
            ->where('sku', '=', $request->sku)
            ->where('hscode_duty_country.country_id', '=', $this->destination->country_id)
            ->firstOrFail();

        return round($declaredValue * $dutyInfo->duty_in_percent / 100, 2);
    }

    public function getMarketplaceCommission(Request $request)
    {
        $marketplaceId = strtoupper(substr($request->sellingPlatform, 0, -2));

        $product = Product::find($request->sku);
        $categoryCommission = MpCategoryCommission::join('mp_category', 'mp_category.id', '=', 'mp_category_commission.mp_id')
            ->select(['mp_commission', 'maximum'])
            ->where('mp_category.control_id', '=', $this->marketplaceControl->control_id)
            ->where('mp_category.esg_cat_id', '=', $product->cat_id)
            ->where('mp_category.esg_sub_cat_id', '=', $product->sub_cat_id)
            ->where('mp_category.esg_sub_sub_cat_id', '=', $product->sub_sub_cat_id)
            //->where('from_price', '<=', $request->price)
            //->where('to_price', '>', $request->price)
            ->firstOrFail();

        $marketplaceCommission = min($request->price * $categoryCommission->mp_commission / 100, $categoryCommission->maximum);

        return round($marketplaceCommission, 2);
    }

    public function getMarketplaceListingFee(Request $request)
    {
        $marketplaceListingFee = 0;
        $marketplaceId = strtoupper(substr($request->sellingPlatform, 0, -2));
        $controlId = MpControl::select(['control_id'])
            ->where('marketplace_id', '=', $marketplaceId)
            ->where('country_id', '=', $this->destination->country_id)
            ->firstOrFail()
            ->control_id;

        $mpListingFee = MpListingFee::select('mp_listing_fee')
            ->where('control_id', '=', $controlId)
            ->where('from_price', '<=', $request->price)
            ->where('to_price', '>', $request->price)
            ->first();

        if ($mpListingFee) {
            $marketplaceListingFee = $mpListingFee->mp_listing_fee;
        }

        return round($marketplaceListingFee, 2);
    }

    public function getMarketplaceFixedFee(Request $request)
    {
        $marketplaceFixedFee = 0;
        $marketplaceId = strtoupper(substr($request->sellingPlatform, 0, -2));
        $controlId = MpControl::select(['control_id'])
            ->where('marketplace_id', '=', $marketplaceId)
            ->where('country_id', '=', $this->destination->country_id)
            ->firstOrFail()
            ->control_id;

        $mpFixedFee =  MpFixedFee::select('mp_fixed_fee')
            ->where('control_id', '=', $controlId)
            ->where('from_price', '<=', $request->price)
            ->where('to_price', '>', $request->price)
            ->first();

        if ($mpFixedFee) {
            $marketplaceFixedFee = $mpFixedFee->mp_fixed_fee;
        }

        return round($marketplaceFixedFee, 2);
    }

    public function getPaymentGatewayFee(Request $request)
    {
        $account = substr($request->sellingPlatform, 0, 2);
        $marketplaceId = substr($request->sellingPlatform, 2, -2);
        $countryCode = strtolower(substr($request->sellingPlatform, -2));
        $countryCode = ($countryCode == 'gb') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_',[$account, $marketplaceId, $countryCode]));
        $paymentGatewayRate = PaymentGateway::findOrFail($paymentGatewayId)->payment_gateway_rate;

        return round($request->price * $paymentGatewayRate / 100, 2);
    }

    public function getPaymentGatewayAdminFee(Request $request)
    {
        $account = substr($request->sellingPlatform, 0, 2);
        $marketplaceId = substr($request->sellingPlatform, 2, -2);
        $countryCode = strtolower(substr($request->sellingPlatform, -2));
        $countryCode = ($countryCode == 'gb') ? 'uk' : $countryCode;

        $paymentGatewayId = strtolower(implode('_',[$account, $marketplaceId, $countryCode]));
        $paymentGateway = PaymentGateway::findOrFail($paymentGatewayId);
        $paymentGatewayAdminFee = $paymentGateway->admin_fee_abs + $request->price * $paymentGateway->admin_fee_percent / 100;

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