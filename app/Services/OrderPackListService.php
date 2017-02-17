<?php

namespace App\Services;

use App;
use App\Models\So;
use App\Models\SoItem;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Country;
use App\Models\SellingPlatform;
use App\models\PlatformBizVar;
use App\Models\ProductAssemblyMapping;
use DNS1D;
use PDF;

class OrderPackListService
{
    private $website = "http://admincentre.eservicesgroup.com";
    private $lang = "";

    public function generateDeliveryNote($soNo)
    {
        $soObj = So::where("so_no",$soNo)->first();
        if ($soObj) {
            $result = $this->getDeliveryNote($soObj);
            if ($result) {
                $returnHTML = view('packlist.delivery-note',$result)->render();
                $pickListNo = "PlistNo";
                $filePath = \Storage::disk('packlist')->getDriver()->getAdapter()->getPathPrefix().$pickListNo;
                $file = $filePath."/delivery_note/". $soNo . '.pdf';
                PDF::loadHTML($returnHTML)->save($file,true);
            }
        }
    }

    public function generateCustomInvoice($soNo, $courierId)
    {
        $soObj = So::where("so_no",$soNo)->first();
        if ($soObj) {
            $result = $this->getCustomInvoice($soObj, $courierId);
        }
    }

    public function getCustomInvoice($soObj)
    {

    }

    public function getDeliveryNote($soObj)
    {
        $result = [];
        $pbvObj = PlatformBizVar::where("selling_platform_id", $soObj->platform_id)->first();
        if ($pbvObj) {
            $platformId = $soObj->platform_id;
            $bizType = $soObj->biz_type;
            if ( ((substr($platformId, 0, 4) == 'TF3D') && ($bizType == 'SPECIAL'))
                    || (substr($platformId, 0, 4) == 'EXAA')
                    || (substr($platformId, 0, 4) == 'DPAA') ) {
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
                foreach($soObj->soItem as $item)
                {
                    $soItemResult[] = $this->getSoItemDeliveryNote($item);
                }
            }

            $deliveryCountry = Country::where("id",$soObj->delivery_country_id)->first();
            $deliveryAddress = ($soObj->delivery_company ? $soObj->delivery_company."<br/>" : "").trim(str_replace("|", "<br/>", $soObj->delivery_address))."".$soObj->delivery_city." ".$soObj->delivery_state." ".$soObj->delivery_postcode."<br/>".$deliveryCountry->name;

            $billingCountry = Country::where("id",$soObj->bill_country_id)->first();
            $billingAddress = ($soObj->bill_company ? $soObj->bill_company."<br/>" : "").trim(str_replace("|", "<br/>", $soObj->bill_address))."".$soObj->bill_city." ".$soObj->bill_state." ".$soObj->bill_postcode."<br/>".$billingCountry->name;

            $result["so"] = $soObj;
            $result["soItem"] = $soItemResult;
            $result["delivery_address"] = $deliveryAddress;
            $result["billing_address"] = $billingAddress;
            $result["website"] = $this->website;

            return $result;
        }
    }

    public function getSoItemDeliveryNote($soItem, $assembly = 0)
    {
        $result = [];

        $product = $soItem->product;
        $productAssemblyMapping = ProductAssemblyMapping::where("main_sku",$product->sku)->where("is_replace_main_sku",1)->first();

        if ($productAssemblyMapping) {
            $itemSku = $productAssemblyMapping->sku ? $productAssemblyMapping->sku : $product->sku;
            $qty = $productAssemblyMapping->replace_qty*$soItem->qty ? $productAssemblyMapping->replace_qty*$soItem->qty : $soItem->qty;
        } else {
            $itemSku = $product->sku;
            $qty = $soItem->qty;
        }

        $imagePath = $this->website . "/images/product/" . $product->sku . "_s." . $product->image;
        if (!@fopen($imagePath,"r")) {
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

    public function getBatteryType($battery='')
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
}