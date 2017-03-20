<?php

namespace App\Services;

use App;
use App\Models\So;
use App\Models\SoItem;
use App\Models\SoAllocate;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Country;
use App\Models\CourierMapping;
use App\Models\PlatformBizVar;
use App\Models\ProductAssemblyMapping;
use App\Models\SupplierProd;
use App\Models\ExchangeRate;
use App\Models\ProductCustomClassification;
use App\Models\HscodeDutyCountry;
use App\Models\SkuMapping;
use App\Models\Declaration;
use App\Models\DeclarationException;

trait TraitDeclaredService
{
    public function getOrderDeclaredObject($soObj)
    {
        $currencyCourierId = $this->getCurrencyCourierId($soObj->esg_quotation_courier_id, $soObj->country);
        $soRate = $this->getOrderRate($soObj, $currencyCourierId);
        $declareType= $this->getDeclareType($soObj);
        $sumItemAmount = $this->getOrderSumItemAmount($soObj, $soRate);
        $discount = $this->getOrderDiscount($soObj, $soRate, $declareType, $sumItemAmount);
        $totalOrderAmount = $sumItemAmount - $discount;
        if ($sumItemAmount == 0) {
            $itemTotalPercent = 0;
        } else {
            $itemTotalPercent = 1 - ($discount / $sumItemAmount);
        }
        $sellingPlatformObj = $soObj->sellingPlatform;
        $calculateDeclaredValue = $this->pickDeclaredValue($sellingPlatformObj->merchant_id, $sellingPlatformObj->type, $soObj->delivery_country_id, $totalOrderAmount, $currencyCourierId, $soObj->incoterm);
        $declaredObject = $this->getOrderItemsDeclaredObject($soObj, $soRate, $sumItemAmount , $itemTotalPercent, $totalOrderAmount, $calculateDeclaredValue );
        $declaredObject["declared_currency"] = $currencyCourierId;
        $declaredObject["total_order_amount"] = number_format($totalOrderAmount, 2, '.', '');
        $declaredObject["discount"] = number_format($discount, 2, '.', '');
        return $declaredObject;
    }

    public function getOrderDiscount($soObj, $soRate, $declareType, $sumItemAmount)
    {
        $useItemDetailDiscount = FALSE;
        $itemDetailDiscount = 0;
        foreach ($soObj->soItem as $item) {
            if ($item->promo_disc_amt > 0) {
                $useItemDetailDiscount = true;
                $itemDetailDiscount += $item->promo_disc_amt;
            }
        }
        if ($useItemDetailDiscount) {
            $discount = $itemDetailDiscount * $soRate;
        } else {
            $discount = $soObj->discount * $soRate;
        }
        # if must declare full value, force discount to zero
        if ($declareType == "FV") {
            $discount = 0;
        }
        # prevent negative
        if($discount > $sumItemAmount) {
            $discount = $sumItemAmount;
        }
        return $discount;
    }

    public function getOrderRate($soObj, $currencyCourierId)
    {
        $soExchangeRate = ExchangeRate::where("from_currency_id", $soObj->currency_id)->where("to_currency_id", $currencyCourierId)->first();
        $soRate = $soExchangeRate->rate;
        return $soRate;
    }

    public function getOrderSumItemAmount($soObj, $soRate)
    {
        $sumItemAmount = 0;
        $soItem = $soObj->soItem;
        foreach ($soItem as $item) {
            if ($item->hidden_to_client == 0) {
                if ($this->isSpecialOrder($soObj)) {
                    $supplierProd = SupplierProd::where("prod_sku", $item->prod_sku)->where("order_default", 1)->first();
                    $exchangeRate = ExchangeRate::where("from_currency_id", $supplierProd->currency_id)->where("to_currency_id", $soObj->currency_id)->first();
                    $item->unit_price = round($exchangeRate->rate * $supplierProd->cost, 2);
                }
                $sumItemAmount += $item->unit_price * $item->qty;
            }
        }
        $sumItemAmount *= $soRate;
        return $sumItemAmount;
    }

    public function pickDeclaredValue($merchantId, $platformType, $deliveryCountryId, $totalOrderAmount, $currencyCourierId, $incoterm)
    {
        $byDeclarationException = false;
        $defaultDeclarationPercent = 1;
        $declaredValue = 0;

        if ($platformType == 'DISPATCH' || $platformType == 'EXPANDER') {
            $declarationException = DeclarationException::where("platform_type", $platformType)->where("delivery_country_id", $deliveryCountryId)
                                                    ->where("ref_from_amt", "<=", $totalOrderAmount)->where("ref_to_amt_exclusive", ">", $totalOrderAmount)
                                                    ->where("status", 1)->where("merchant_id", $merchantId)->first();
            if (!$declarationException) {
                $declarationException = DeclarationException::where("platform_type", $platformType)->where("delivery_country_id", $deliveryCountryId)
                                                    ->where("ref_from_amt", "<=", $totalOrderAmount)->where("ref_to_amt_exclusive", ">", $totalOrderAmount)
                                                    ->where("status", 1)->where("merchant_id", "ALL")->first();
            }
        } else {
            $declarationException = DeclarationException::where("platform_type", $platformType)->where("delivery_country_id", $deliveryCountryId)
                                                    ->where("ref_from_amt", "<=", $totalOrderAmount)->where("ref_to_amt_exclusive", ">", $totalOrderAmount)
                                                    ->where("status", 1)->where("merchant_id", "ALL")->first();
        }

        if ($declarationException) {
            $currencyId = $declarationException->currency_id;
            $declarationRate = ExchangeRate::where("from_currency_id", $currencyId)->where("to_currency_id", $currencyCourierId)->first();
            $rate = $declarationRate->rate;
            // declared_value direct by absolute_value
            if ($declarationException->absolute_value > 0) {
                $declaredValue = $declarationException->absolute_value * $rate;
                $byDeclarationException = true;
            }
            if ($byDeclarationException === false && $declarationException->declared_ratio > 0) {
                // declared_value for total_order_amount * declared_percent
                $declaredValue = $totalOrderAmount * ($declarationException->declared_ratio / 100);
                // when declared_value > max_absolute_value will be replace for max_absolute_value;
                $maxAbsoluteValue = $declarationException->max_absolute_value * $rate;
                if ($maxAbsoluteValue > 0 && $declaredValue > $maxAbsoluteValue) {
                    $declaredValue = $maxAbsoluteValue;
                }
                $byDeclarationException = true;
            }
        }

        # no setting declaration_exception,  declared_value = total_order_amount * default_declaration_percent
        if ($byDeclarationException === false) {
            $declaration = Declaration::where("platform_type", $platformType)->first();
            if ($declaration) {
                $defaultDeclarationPercent = $declaration->default_declaration_percent / 100;
            }
            $declaredValue = $totalOrderAmount * $defaultDeclarationPercent;
        }

        #Not rounding, direct two decimal places
        $declaredValue = floor(($declaredValue) * 100) / 100;
        $declaredValue = sprintf('%.2f', (float)$declaredValue);

        return $declaredValue;
    }

    public function getCurrencyCourierId($courierId, $deliveryCountryObj)
    {
        switch ($courierId) {
            // PostNL
            case 69:
            case 70:
            case 133:
                $currencyCourierId = "USD";
                break;
            case 88:
            case 91:
                $currencyCourierId = "EUR";
                break;
            default:
                $currencyCourierId = $deliveryCountryObj->currency_courier_id;
                break;
        }
        return $currencyCourierId;
    }

    private function getDeclareType($soObj)
    {
        $deliveryCountryObj = $soObj->country;
        if(!empty($deliveryCountryObj)){
            $declareType = $deliveryCountryObj ? $deliveryCountryObj->declare_type : "FV";
            return $declareType;
        }
    }

    public function getOrderItemsDeclaredObject($soObj, $soRate, $sumItemAmount , $itemTotalPercent, $totalOrderAmount, $calculateDeclaredValue )
    {
        $itemResult = [];
        $declaredValue = 0;
        foreach ($soObj->soItem as $item) {
            if ($item->hidden_to_client == 0) {
                $unitPrice = $item->unit_price * $soRate;
                $unitDeclaredValue = $this->getUnitDeclaredValue($unitPrice, $sumItemAmount, $itemTotalPercent, $totalOrderAmount, $calculateDeclaredValue);
                //RPX
                /*if (in_array($soObj->esg_quotation_courier_id, ['88','91'])) {
                    $unitDeclaredValue = $item->item_declared_value / $item->qty;
                }*/
                $sellingPlatformObj = $soObj->sellingPlatform;
                $useOptimizedHscodeDuty = $sellingPlatformObj->merchant->use_optimized_hscode_w_duty;
                $declaredValue += $unitDeclaredValue * $item->qty;
                $descAndCode = $this->getDeclaredDescAndCode($item, $useOptimizedHscodeDuty, $soObj->delivery_country_id);
                $itemResult[$item->prod_sku] = [
                        "prod_desc" => $descAndCode["prod_desc"],
                        "code" => $descAndCode["code"],
                        "qty" => $item->qty,
                        "unit_declared_value" =>number_format($unitDeclaredValue, 2, '.', ''),
                        "item_declared_value" =>number_format($unitDeclaredValue * $item->qty, 2, '.', ''),
                    ];
            }
        }

        return $declaredObject = array(
            "declared_value" => number_format($declaredValue, 2, '.', ''),
            "items" => $itemResult,
            );
    }

    public function getDeclaredDescAndCode($itemObj, $useOptimizedHscodeDuty, $deliveryCountryId)
    {
        $code = "";
        $declaredDesc = "";
        $product = $itemObj->product;
        $hscodeCatId = $product->hscode_cat_id;

        if (in_array($hscodeCatId, ['21','22']) || $hscodeCatId == "" || $useOptimizedHscodeDuty == 0) {
            $productCustomClassification = ProductCustomClassification::where("sku", $product->sku)->where("country_id", $deliveryCountryId)->first();
            if ($productCustomClassification) {
                $code = $productCustomClassification->code;
            }
            if (strlen($code) > 8) {
                $code = substr($code, 0, 8);
            }
            if (strlen($code) < 8 && strlen($code) > 0) {
                $code = str_pad($code, 8, '0');
            }
            $declaredDesc = $product->declared_desc;
        } else {
            $hscodeCategory = $product->hscodeCategory;
            if ($hscodeCategory) {
                $hscodeDutyCountry = HscodeDutyCountry::where("hscode_cat_id", $hscodeCatId)->where("country_id", $deliveryCountryId)->first();
                if ($hscodeDutyCountry) {
                    $code = $hscodeDutyCountry->optimized_hscode;
                }
                if (!$code) {
                    $code = $hscodeCategory->general_hscode;
                }
                $declaredDesc = $hscodeCategory->name;
            }
        }
        $skuMapping = SkuMapping::where("sku", $itemObj->prod_sku)->where("ext_sys", "WMS")->first();
        $prodDesc = ($skuMapping ? $skuMapping->ext_sku : "") ." ".$declaredDesc;

        return ["code"=>$code, "prod_desc"=> $prodDesc, "declared_desc"=>$declaredDesc, "master_sku"=>$skuMapping ? $skuMapping->ext_sku : ""];
    }

    public function getUnitDeclaredValue($unitPrice, $sumItemAmount, $itemTotalPercent, $totalOrderAmount, $calculateDeclaredValue)
    {
        if ($sumItemAmount == 0) {
            $unitDeclaredPercent = 0;
        } else {
            $unitPriceValue = $unitPrice * $itemTotalPercent;
            $unitDeclaredPercent = $unitPriceValue / ($totalOrderAmount ? $totalOrderAmount : $sumItemAmount);
        }
        return $unitDeclaredValue = $calculateDeclaredValue * $unitDeclaredPercent;
    }

    public function isSpecialOrder($soObj)
    {
        if ($soObj->biz_type == "SPECIAL" && $soObj->amount == '0.00' && in_array($soObj->sellingPlatform->type, ['TRANSFER', 'ACCELERATOR'])) {
            return true;
        }
        return false;
    }
}