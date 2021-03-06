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
use App\Models\CourierInfo;
use App\Models\PlatformBizVar;
use App\Models\ProductAssemblyMapping;
use App\Models\SupplierProd;
use App\Models\ExchangeRate;
use App\Models\ProductCustomClassification;
use App\Models\HscodeDutyCountry;
use App\Models\SkuMapping;
use App\Models\Declaration;
use App\Models\DeclarationException;
use App\Repository\FulfillmentOrderRepository;
use Illuminate\Http\Request;
use DNS1D;
use PDF;

class OrderPackListService
{
    protected $website = "http://admincentre.eservicesgroup.com";
    protected $country = null;
    protected $courierList = null;
    protected $per_page = 50;
    private $exceptionMsg = [];
    protected $invoiceSuccess = [];
    protected $deliverySuccess = [];

    public function __construct()
    {
        $this->orderRepository = new FulfillmentOrderRepository();
        $this->setCountryList();
        $this->setCourierList();
    }

    public function processPackList()
    {
        $request = new Request;
        do {
            $soList = $this->getProcessOrder($request);
            if (! $soList->getCollection()->isEmpty()) {
                $soNoList = [];
                foreach ($soList as $soObj) {
                    $invoice = $this->generateCustomInvoice($soObj);
                    $dnote = $this->generateDeliveryNote($soObj);
                    if ($invoice && $dnote) {
                        $soNoList[] = $soObj->so_no;
                    }
                }
                $this->updateDeliveryNoteInvoiceStatus($soNoList);
                $this->updateDeliveryNoteInvoiceStatus(array_keys($this->exceptionMsg), '-1');
            }
        } while (! $soList->getCollection()->isEmpty());

        $this->sendEmailAlert();
        $this->sendSuccessEmail();
    }

    public function updateDeliveryNoteInvoiceStatus($soNoList, $status = 2)
    {
        if (!empty($soNoList)) {
            So::whereIn('so_no', $soNoList)->update(['dnote_invoice_status' => $status]);
        }
    }

    public function reprocessPackList($soNoList)
    {
        if ($soNoList) {
            $success = [];
            foreach ($soNoList as $soNo) {
                $dnoteRes = $this->regenerateDeliveryNote($soNo);
                $invoiceRes = $this->regenerateCustomInvoice($soNo);
                if ($dnoteRes && $invoiceRes) {
                    $success[] = $soNo;
                }
            }
            $this->updateDeliveryNoteInvoiceStatus($success);
            $this->updateDeliveryNoteInvoiceStatus(array_keys($this->exceptionMsg), '-1');
            $this->sendEmailAlert();
        }
    }

    public function sendEmailAlert()
    {
        if ($this->getExceptionMsg()) {
            mail("milo.chen@eservicesgroup.com", "generate Invoice or Delivery Note failed", implode(PHP_EOL, $this->getExceptionMsg()));
        }
    }

    public function sendSuccessEmail()
    {
        $message = "";
        if ($this->invoiceSuccess) {
            $message .= "Create Invoice Success:".PHP_EOL;
            foreach ($this->invoiceSuccess as $pickListNo => $list) {
                $message .= "PickListNo:".$pickListNo.PHP_EOL.implode(",", $list).PHP_EOL;
            }
        }

        if ($this->deliverySuccess) {
            $message .= "Create DeliveryNote Success:".PHP_EOL;
            foreach ($this->deliverySuccess as $pickListNo => $list) {
                $message .= "PickListNo:".$pickListNo.PHP_EOL.implode(",", $list).PHP_EOL;
            }
        }

        if ($message) {
            mail("milo.chen@eservicesgroup.com", "generate Invoice and Delivery Success", $message);
        }
    }

    public function createFolder($folder)
    {
        if (!is_dir($folder)) {
            mkdir($folder, 775, true);
            mkdir($folder."/delivery_note", 775, true);
            mkdir($folder."/invoice", 775, true);
        }
    }

    public function getProcessOrder(Request $request, $page = 1)
    {
        $request->merge(
            [
                'per_page' => $this->per_page,
                'page' => $page,
                'dnote_invoice_status' => 0,
                'status' => 5,
                'refund_status' => 0,
                'hold_status' => 0,
                'prepay_hold_status' => 0,
                'merchant_hold_status' => 0,
                'exist_pick_list_no' => true
            ]
        );
        return $this->orderRepository->getOrders($request);
    }

    public function getOrderBySoNo($soNo)
    {
        return So::where("so_no", $soNo)->whereNotNull("pick_list_no")->where("pick_list_no", "<>", "")->first();
    }

    public function regenerateDeliveryNote($soNo)
    {
        $soObj = $this->getOrderBySoNo($soNo);
        if ($soObj) {
            return $this->generateDeliveryNote($soObj);
        } else {
            return false;
        }
    }

    public function generateDeliveryNote($soObj)
    {
        $result = $this->getDeliveryNote($soObj);
        if ($result) {
            $returnHTML = view('packlist.delivery-note', $result)->render();
            $pickListNo = $soObj->pick_list_no;
            if (! $soObj->esg_quotation_courier_id) {
                $this->exceptionMsg[$soObj->so_no] = "DeliveryNote so: {$soObj->so_no},message:  no rec_courier";
                return false;
            }
            $courierInfo = $this->getCourierInfo($soObj->esg_quotation_courier_id);
            $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo;
            $file = $filePath."/delivery_note/". $courierInfo->courier_name ."/". $soObj->so_no . '_delivery_note.pdf';
            PDF::loadHTML($returnHTML)->save($file, true);
            $this->deliverySuccess[$pickListNo][] = $soObj->so_no;
            return true;
        }
        return false;
    }

    public function regenerateCustomInvoice($soNo, $courierId = "")
    {
        $soObj = $this->getOrderBySoNo($soNo);
        if ($soObj) {
            return $this->generateCustomInvoice($soObj, $courierId);
        } else {
            return false;
        }
    }

    public function generateCustomInvoice($soObj, $courierId = "")
    {
        if (!$courierId) {
            $courierId = $soObj->esg_quotation_courier_id;
        } else {
            $soObj->esg_quotation_courier_id = $courierId;
        }
        if (!$courierId) {
            $this->exceptionMsg[$soObj->so_no] = "CustomInvoice so: {$soObj->so_no},message:  no rec_courier";
            return false;
        }
        //LGS order do not create invoice
        if (in_array($courierId, ['93', '103', '130', '131', '136', '170'])) {
            return true;
        }

        $result = $this->getCustomInvoice($soObj, $courierId);
        if ($result) {
            $returnHTML = view('packlist.custom-invoice', $result)->render();
            $pickListNo = $soObj->pick_list_no;
            if (! $soObj->esg_quotation_courier_id) {
                $this->exceptionMsg[$soObj->so_no] = "CustomInvoice so: {$soObj->so_no},message:  no rec_courier";
                return false;
            }
            $courierInfo = $this->getCourierInfo($soObj->esg_quotation_courier_id);
            $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo;
            $file = $filePath."/invoice/". $courierInfo->courier_name ."/". $soObj->so_no . '_invoice.pdf';
            PDF::loadHTML($returnHTML)->save($file, true);
            $this->invoiceSuccess[$pickListNo][] = $soObj->so_no;
            return true;
        }
        return false;
    }

    public function getExceptionMsg()
    {
        return $this->exceptionMsg;
    }

    public function getCustomInvoice($soObj, $courierId = "")
    {
        $result = [];

        $isFedex = 0;
        if (in_array($courierId, ['8', '44', '52', '55', '56', '59'])) {
            $isFedex = 1;
        }

        $sellingPlatformObj = $soObj->sellingPlatform;
        $merchantId = $sellingPlatformObj->merchant_id;
        $useOptimizedHscodeDuty = $sellingPlatformObj->merchant->use_optimized_hscode_w_duty;

        $shipperName = $courierName = "";
        $courierMap = CourierMapping::where("courier_id", $courierId)->first();
        if ($courierMap) {
            $shipperName = $courierMap->shipper_name;
        }
        //sbf 11032
        // if ($merchantId == "PALETTEGEAR" && $sellingPlatformObj->type == "DISPATCH") {
        //     $shipperName = "Grant & Union Inc.(Palette) C/O <br/> E-SERVICES GROUP LIMITED";
        // }

        //$deliveryCountryObj = Country::where("id", $soObj->delivery_country_id)->first();
        $deliveryCountryObj = $this->getCountry($soObj->delivery_country_id);
        $declareType = $deliveryCountryObj ? $deliveryCountryObj->declare_type : "FV";
        $currencyCourierId = $this->getCurrencyCourierId($courierId, $deliveryCountryObj);

        $soExchangeRate = ExchangeRate::where("from_currency_id", $soObj->currency_id)->where("to_currency_id", $currencyCourierId)->first();
        $soRate = $soExchangeRate->rate;

        $sumItemAmount = 0;
        $itemDetailDiscount = 0;
        $useItemDetailDiscount = false;
        $fedexCustomInvoice = false;
        $soItem = $soObj->soItem;
        foreach ($soItem as $item) {
            if ($item->promo_disc_amt > 0) {
                $useItemDetailDiscount = true;
                $itemDetailDiscount += $item->promo_disc_amt;
            }
            if ($item->hidden_to_client == 0) {
                if ($this->isSpecialOrder($soObj)) {
                    $supplierProd = SupplierProd::where("prod_sku", $item->prod_sku)->where("order_default", 1)->first();
                    $exchangeRate = ExchangeRate::where("from_currency_id", $supplierProd->currency_id)->where("to_currency_id", $soObj->currency_id)->first();
                    $item->unit_price = round($exchangeRate->rate * $supplierProd->cost, 2);
                }
                $sumItemAmount += $item->unit_price * $item->qty;

                if ((($item->battery == '1') or ($item->battery == '2')) && ($isFedex == 1)) {
                    $fedexCustomInvoice = true;
                }
            }
        }
        $sumItemAmount *= $soRate;
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
        if ($discount > $sumItemAmount) {
            $discount = $sumItemAmount;
        }

        $totalOrderAmount = $sumItemAmount - $discount;

        if ($sumItemAmount == 0) {
            $itemTotalPercent = 0;
        } else {
            $itemTotalPercent = 1 - ($discount / $sumItemAmount);
        }

        $calculateDeclaredValue = $this->pickDeclaredValue($merchantId, $sellingPlatformObj->type, $soObj->delivery_country_id, $totalOrderAmount, $currencyCourierId, $soObj->incoterm);

        $itemResult = [];
        $declaredValue = 0;
        foreach ($soItem as $item) {
            if ($item->hidden_to_client == 0) {
                $unitPrice = $item->unit_price * $soRate;
                $unitDeclaredValue = $this->getUnitDeclaredValue($unitPrice, $sumItemAmount, $itemTotalPercent, $totalOrderAmount, $calculateDeclaredValue);
                //RPX
                if (in_array($courierId, ['88','91'])) {
                    $unitDeclaredValue = $item->item_declared_value / $item->qty;
                }
                $declaredValue += $unitDeclaredValue * $item->qty;
                $descAndCode = $this->getDeclaredDescAndCode($item, $useOptimizedHscodeDuty, $soObj->delivery_country_id);

                $isShow = 1;
                if ((substr($soObj->platform_id, 0, 2) == "TF" || substr($soObj->platform_id, 0, 2) == "AC") && $descAndCode["master_sku"] == "34753-MM-BK") {
                    $isShow = 0;
                }
                $itemResult[] = [
                        "prod_desc" => $descAndCode["prod_desc"],
                        "code" => $descAndCode["code"],
                        "qty" => $item->qty,
                        "unit_declared_value" =>number_format($unitDeclaredValue, 2),
                        "item_declared_value" =>number_format($unitDeclaredValue * $item->qty, 2),
                        "is_show" => $isShow
                    ];
            }
        }

        $result["so"] = $soObj;
        $result["soItem"] = $itemResult;
        $result["merchant_id"] = $merchantId;
        $result["courier_id"] = $courierId;
        $result["shipper"] = $this->getShipperData($soObj, $shipperName);
        $result["shipp_to"] = $this->getShipToData($soObj);
        $result["fedex_custom_invoice"] = $fedexCustomInvoice;

        $result["currency_courier_id"] = $currencyCourierId;
        $result["total_cost"] = number_format(($declaredValue + $discount), 2);
        $result["total_discount"] = number_format($discount, 2);
        $result["total_amount"] = number_format(($declaredValue), 2);

        return $result;
    }

    public function getDeliveryNote($soObj)
    {
        $result = [];
        $pbvObj = PlatformBizVar::where("selling_platform_id", $soObj->platform_id)->first();
        if ($pbvObj) {
            $platformId = $soObj->platform_id;
            $bizType = $soObj->biz_type;
            if (((substr($platformId, 0, 4) == 'TF3D') && ($bizType == 'SPECIAL'))
                    || (substr($platformId, 0, 4) == 'EXAA')
                    || (substr($platformId, 0, 4) == 'DPAA')) {
                $soItemResult = [];
                foreach ($soObj->soItem as $item) {
                    if ($item->hidden_to_client == 0) {
                        $soItemResult[] = $this->getSoItemDeliveryNote($item, 1);
                        $productAssemblyMapping = ProductAssemblyMapping::where("main_sku", $item->product->sku)->get();
                        foreach ($productAssemblyMapping as $assemblyObj) {
                            $assemblySoItem = SoItem::where("so_no", $item->so_no)->where("prod_sku", $assemblyObj->sku)->where("hidden_to_client", 1)->first();
                            if ($assemblySoItem) {
                                $soItemResult[] = $this->getSoItemDeliveryNote($assemblySoItem, 2);
                            }
                        }
                    }
                }
            } else {
                $soItemResult = [];
                foreach ($soObj->soItem as $item) {
                    $soItemResult[] = $this->getSoItemDeliveryNote($item);
                }
            }

            //$deliveryCountry = Country::where("id",$soObj->delivery_country_id)->first();
            $deliveryCountry = $this->getCountry($soObj->delivery_country_id);
            $deliveryAddress = ($soObj->delivery_company ? $soObj->delivery_company."<br/>" : "").trim(str_replace("|", "<br/>", $soObj->delivery_address))."".$soObj->delivery_city." ".$soObj->delivery_state." ".$soObj->delivery_postcode."<br/>".$deliveryCountry->name;

            //$billingCountry = Country::where("id",$soObj->bill_country_id)->first();
            $billingCountry = $this->getCountry($soObj->bill_country_id);
            $billingAddress = ($soObj->bill_company ? $soObj->bill_company."<br/>" : "").trim(str_replace("|", "<br/>", $soObj->bill_address))."".$soObj->bill_city." ".$soObj->bill_state." ".$soObj->bill_postcode."<br/>".$billingCountry->name;

            $result["so"] = $soObj;
            $courierInfo = $this->getCourierInfo($soObj->esg_quotation_courier_id);
            $result["courier_name"] = $courierInfo->courier_name;
            $result['merchant_name'] = $soObj->sellingPlatform->merchant_id;
            $result["soItem"] = $soItemResult;
            $result["delivery_address"] = $deliveryAddress;
            $result["billing_address"] = $billingAddress;
            $result["website"] = $this->website;

            return $result;
        } else {
            $this->exceptionMsg[$soObj->so_no] = "DeliveryNote so: {$soObj->so_no},message:  not found PlatformBizVar: {$soObj->platform_id}";
            return false;
        }
    }

    public function getSoItemDeliveryNote($soItem, $assembly = 0)
    {
        $result = [];

        $product = $soItem->product;
        $productAssemblyMapping = ProductAssemblyMapping::where("main_sku", $product->sku)->where("is_replace_main_sku", 1)->first();

        if ($productAssemblyMapping) {
            $itemSku = $productAssemblyMapping->sku ? $productAssemblyMapping->sku : $product->sku;
            $qty = $productAssemblyMapping->replace_qty*$soItem->qty ? $productAssemblyMapping->replace_qty*$soItem->qty : $soItem->qty;
        } else {
            $itemSku = $product->sku;
            $qty = $soItem->qty;
        }

        $imagePath = $this->website . "/images/product/" . $product->sku . "_s." . $product->image;
        if (!@fopen($imagePath, "r")) {
            $imagePath = $this->website . "/images/product/imageunavailable_s.jpg";
        }

        if ($assembly) {
            $assemblyProduct = Product::where("sku", $itemSku)->first();
            $batteryType = $this->getBatteryType($assemblyProduct->battery);
            $productName = $assemblyProduct->name;
        } else {
            $batteryType = $this->getBatteryType($product->battery);
            $productName = $product->name;
        }

        $result['assembly'] = $assembly;
        $result['qty'] = $qty;
        $result['name'] = $productName;
        $result['prod_name'] = $soItem->prod_name;
        $result['special_request'] = $product->special_request;
        $result['main_prod_sku'] = $soItem->prod_sku;
        $result['item_sku'] = $itemSku;
        $result['battery_type'] = $batteryType;
        $result['merchant_sku'] = $product->merchantProductMapping->merchant_sku;
        $result['imagePath'] = $imagePath;

        return $result;
    }

    public function getBatteryType($battery = '')
    {
        switch ($battery) {
            case '0':
                $battery = "Without";
                break;
            case '1':
                $battery = "Built-In";
                break;
            case '2':
                $battery = "External";
                break;
            default:
                $battery = "Without";
                break;
        }

        return $battery;
    }

    public function getShipperData($soObj, $shipperName = "")
    {
        $result = [
            "shipper_contact" => "",
            "shipper_name" => $shipperName,
            "shipper_phone" => "852-35430892",
            "saddr_1" => "10/F Wah Shing Industrial Building,",
            "saddr_2" => "18 Cheung Shun Street，",
            "saddr_3" => "Lai Chi Kok, ",
            "saddr_4" => "Kowloon,",
            "saddr_5" => "HongKong",
            "saddr_6" => "&nbsp;"
        ];
        if ($soAllocate = SoAllocate::where("so_no", $soObj->so_no)->first()) {
            if ($soAllocate->warehouse_id == 'ES_DGME') {
                $result["shipper_name"]    = '';
                $result["shipper_contact"] = "ME OPS Team";
                $result["shipper_phone"]   = "+86 (0) 769-26386615";
                $result["saddr_1"]         = "Cross-Border Ecommerce Park,";
                $result["saddr_2"]         = "1 Hai Jie Road, ";
                $result["saddr_3"]         = "Hu Men, ";
                $result["saddr_4"]         = "Dong Guan,";
                $result["saddr_5"]         = "Guangdong Province";
            }
        }
        return $result;
    }

    public function getShipToData($soObj)
    {
        $result = [];
        for ($i = 1; $i < 7; $i++) {
            $result["daddr_".$i] = "&nbsp;";
        }

        $lineNo = 1;
        $deliveryAddr = explode("|", $soObj->delivery_address);
        $deliveryAddr = array_pad($deliveryAddr, 3, "");
        list($deliveryAddr1, $deliveryAddr2, $deliveryAddr3) = $deliveryAddr;

        if ($soObj->delivery_company != "") {
            $result["daddr_".$lineNo] = $soObj->delivery_company;
            $lineNo++;
        }
        $result["daddr_".$lineNo] = $deliveryAddr1;
        $lineNo++;

        if ($deliveryAddr2 != "" || $deliveryAddr3 != "") {
            if ($deliveryAddr2 != "") {
                $result["daddr_".$lineNo] = $deliveryAddr2;
                $lineNo++;
            }

            if ($deliveryAddr3 != "") {
                $result["daddr_".$lineNo] = $deliveryAddr3;
                $lineNo++;
            }
        }

        $csz = "";
        if ($soObj->delivery_city != "") {
            $csz .= $soObj->delivery_city.", ";
        }
        if ($soObj->delivery_state != "") {
            $csz .= $soObj->delivery_state;
        }
        if ($soObj->delivery_postcode != "") {
            $csz .= " ".$soObj->delivery_postcode;
        }
        $csz = @preg_replace("{, $}", "", $csz);
        if (trim($csz)) {
            $result["daddr_".$lineNo] = $csz;
            $lineNo++;
        }
        $result["daddr_".$lineNo] = $soObj->delivery_country_id;

        return $result;
    }

    public function isSpecialOrder($soObj)
    {
        if ($soObj->biz_type == "SPECIAL" && $soObj->amount == '0.00' && in_array($soObj->sellingPlatform->type, ['TRANSFER', 'ACCELERATOR'])) {
            return true;
        }
        return false;
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

        return ["code"=>$code, "prod_desc"=>$prodDesc, "master_sku"=>$skuMapping ? $skuMapping->ext_sku : ""];
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

    public function setCountryList()
    {
        $countryCollect = Country::get();
        $country = [];
        foreach ($countryCollect as $value) {
            $country[$value->id] = $value;
        }
        $this->country = $country;
    }

    public function getCountry($countryId)
    {
        return isset($this->country[$countryId]) ? $this->country[$countryId] : null;
    }

    public function setCourierList()
    {
        $courierCollect = CourierInfo::get();
        $courierInfo = [];
        foreach ($courierCollect as $value) {
            $courierInfo[$value->courier_id] = $value;
        }
        $this->courierList = $courierInfo;
    }

    public function getCourierInfo($courierId)
    {
        return isset($this->courierList[$courierId]) ? $this->courierList[$courierId] : null;
    }
}
