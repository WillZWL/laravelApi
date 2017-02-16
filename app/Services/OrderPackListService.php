<?php

namespace App\Services;

use App;
use App\Models\So;
use App\Models\Currency;
use App\Models\Country;
use App\Models\SellingPlatform;
use App\models\PlatformBizVar;
use App\Models\ProductAssemblyMapping;
use DNS1D;

class OrderPackListService
{
    private $website = "http://admincentre.eservicesgroup.com";
    private $domian = "eservicesgroup.com";
    private $lang = "";

    public function generateDeliveryNote($soNo)
    {
        $soObj = So::where("so_no",$soNo)->first();
        if (!$soObj) {
            echo "订单".$soNo."不存在";
            continue;
        } else {
            $result = $this->getDeliveryNote($soObj);
            if ($result) {
                $returnHTML = view('plist.delivery-note',$result)->render();
                echo $returnHTML;die;
            }
        }
    }

    public function generateCustomInvoice($value='')
    {
        $result = [];
        $returnHTML = view('plist.custom-invoice')->with('orderList', $result)->render();
        echo $returnHTML;die;
        $filePath = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix();
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/label/";
        $file = "picklist-".date("H-s-i").'.pdf';
        PDF::loadHTML($returnHTML)->save($pdfFilePath.$file);
        $pdfFile = url("api/merchant-api/download-label/".$file);
        $result = array("status"=>"success","document"=>$pdfFile);
        return \Response::json($result);
    }

    public function generateInvoice($soList, $lang = "")
    {
        $this->lang = $lang;
        $currencys = Currency::get();

        $currencyArray = [];
        foreach ($currencys as $currency) {
            $currencyArray[$currency->id] = $currency->sign;
        }

        if (count($soList)) {
            foreach ($soList as $soNo) {
                $soObj = So::where("so_no",$soNo)->first();
                if (!$soObj) {
                    echo "订单".$soNo."不存在";
                    continue;
                } else {
                    $this->getInvoiceContent($soObj);
                }
            }
        }
    }

    public function getInvoiceContent($soObj)
    {
        $sellingPlatformObj = $soObj->sellingPlatform;

        $merchantId = "";
        if ($sellingPlatformObj) {
            $merchantId = $sellingPlatformObj->merchant_id;
            $merchantName = $sellingPlatformObj->merchant->merchant_name;
        }
        $pbvObj = PlatformBizVar::where("selling_platform_id",$soObj->platform_id)->first();

        if(empty($this->lang))
        {
            $this->lang = $pbvObj->language_id;
        }

        $replace = array();
        //$replace["cursign"] = $this->currencyArray[$soObj->currency_id];
        $replace['merchant_id'] = $merchantId;
        if($merchantName)
            $replace['merchant_id'] = $merchantName;

        switch($merchantId)
        {
            case "3DOODLER":  $replace["contact_us_url"] = "http://the3doodler.com/contact-us";
                              $replace["contact_us_text"] = "http://the3doodler.com/contact-us";
                              $replace['logo'] = "logo.png";
                              break;
            default: $replace["contact_us_url"] = $replace["contact_us_text"] = '';
                    $replace['logo'] = "logo.png";
        }
        App::setLocale($this->lang);
        $replace = array_merge($replace, trans("invoice"));
        $clientObj = $soObj->client;
        #SBF #2960 Add NIF/CIF to invoice if info was supplied
        if($soObj->platform_id == "WEBES" && $clientObj->client_id_no)
        {
            // $replace["client_id_no"] = <<<html
            // <p>$client_id_no</p>
            // <p>&nbsp</p>
            // html;
        }
        else
        {
            $replace["label_client_id_no"] = "";
            $replace["client_id_no"] = "";
        }
        $replace["platform_id"] = $soObj->platform_id;
        switch ($soObj->platform_id)
        {
            case "AMUS":
                $replace["isAmazon"] = 1;
                $replace["sales_email"] = "amazoncentral@valuebasket.com";
                $replace["csemail"] = "amazoncentral@valuebasket.com";
                $replace["return_email"] = "returns@valuebasket.com";
                break;
            case "AMDE":
            case "AMFR":
            case "AMUK":
                $replace["isAmazon"] = 1;
                $replace["sales_email"] = "amazoncentral@valuebasket.com";
                $replace["csemail"] = "amazoncentral@valuebasket.com";
                $replace["return_email"] = "returns@valuebasket.com";
                break;
            default:
                $replace["isAmazon"] = 0;
                $replace["sales_email"] = "no-reply@valuebasket.com";
                $replace["csemail"] = "no-reply@valuebasket.com";
                $replace["return_email"] = "no-reply@valuebasket.com";
                break;
        }
    }

    public function getCustomInvoice()
    {
        // $content = "";
        // if ($so_no_list)
        // {
        //     include_once(APPPATH . "libraries/service/template_service.php");
        //     $tpl_srv = new Template_service();
        //     $tpl_id = "delivery_note";
        //     $content .= @file_get_contents(APPPATH.$this->get_config()->value_of("tpl_path").$tpl_id."/".$tpl_id."_header.html");

        //     foreach ($so_no_list as $so_no)
        //     {
        //         if ($so_obj = $this->get(array("so_no"=>$so_no)))
        //         {
        //             $cur_platform_id = $so_obj->get_platform_id();
        //             if (!isset($ar_pbv_obj[$cur_platform_id]))
        //             {
        //                 $ar_pbv_obj[$cur_platform_id] = $this->get_pbv_srv()->get(array("selling_platform_id"=>$cur_platform_id));
        //             }

        //             if ($pbv_obj = $ar_pbv_obj[$cur_platform_id])
        //             {
        //                 $replace = array();

        //                 $cur_lang_id = "en";
        //                 if (!isset($ar_lang[$cur_lang_id]))
        //                 {
        //                     include_once APPPATH."language/ORD001001_".$cur_lang_id.".php";
        //                     $ar_lang[$cur_lang_id] = $lang;
        //                 }

        //                 $replace["so_no"] = $so_no;
        //                 $replace["client_id"] = $so_obj->get_client_id();
        //                 $replace["platform_so_no"] = $so_obj->get_platform_order_id();
        //                 $replace["order_create_date"] = date("d/m/Y", strtotime($so_obj->get_order_create_date()));
        //                 $replace["delivery_name"] = $so_obj->get_delivery_name();
        //                 $country = $this->get_region_srv()->country_dao->get(array("id"=>$so_obj->get_delivery_country_id()));
        //                 $billing_country = $this->get_region_srv()->country_dao->get(array("id"=>$so_obj->get_bill_country_id()));
        //                 $replace["delivery_address_text"] = ($so_obj->get_delivery_company() ? $so_obj->get_delivery_company()."\n" : "").trim(str_replace("|", "\n", $so_obj->get_delivery_address()))."\n".$so_obj->get_delivery_city()." ".$so_obj->get_delivery_state()." ".$so_obj->get_delivery_postcode()."\n".$country->get_name();
        //                 $replace["delivery_address"] = nl2br($replace["delivery_address_text"]);
        //                 $replace["billing_name"] = $so_obj->get_bill_name();
        //                 $replace["billing_address_text"] = ($so_obj->get_bill_company() ? $so_obj->get_bill_company()."\n" : "").trim(str_replace("|", "\n", $so_obj->get_bill_address()))."\n".$so_obj->get_bill_city()." ".$so_obj->get_bill_state()." ".$so_obj->get_bill_postcode()."\n".$billing_country->get_name();
        //                 $replace["billing_address"] = nl2br($replace["billing_address_text"]);

        //                 $replace["lang_order_no"] = $ar_lang[$cur_lang_id]["order_no"];
        //                 $replace["lang_order_date"] = $ar_lang[$cur_lang_id]["order_date"];
        //                 $replace["lang_ship_to"] = $ar_lang[$cur_lang_id]["ship_to"];
        //                 $replace["lang_bill_to"] = $ar_lang[$cur_lang_id]["bill_to"];
        //                 $replace["lang_order_details"] = $ar_lang[$cur_lang_id]["order_details"];
        //                 $replace["lang_description"] = $ar_lang[$cur_lang_id]["description"];
        //                 $replace["lang_battery_type"] = $ar_lang[$cur_lang_id]["battery_type"];
        //                 $replace["lang_qty"] = $ar_lang[$cur_lang_id]["qty"];
        //                 $replace["lang_thank_you"] = $ar_lang[$cur_lang_id]["thank_you"];
        //                 $replace["lang_need_assistance"] = $ar_lang[$cur_lang_id]["need_assistance"];
        //                 $replace["lang_our_return_policy"] = $ar_lang[$cur_lang_id]["our_return_policy"];
        //                 $replace["lang_return_policy_part1"] = $ar_lang[$cur_lang_id]["return_policy_part1"];
        //                 $replace["lang_return_policy_part2"] = $ar_lang[$cur_lang_id]["return_policy_part2"];
        //                 $replace["return_email"] = $this->get_return_email($cur_lang_id);
        //                 $replace["cs_support_email"] = $this->get_cs_support_email($cur_lang_id);
        //                 $barcodelist = array('15768-AA-NA', '15767-AA-NA', '15766-AA-NA', '15765-AA-NA');
        //                 $biz_type = $so_obj->get_biz_type();
        //                 if ( ((substr($cur_platform_id, 0, 4) == 'TF3D') && ($biz_type == 'SPECIAL')) || (substr($cur_platform_id, 0, 4) == 'EXAA') || (substr($cur_platform_id, 0, 4) == 'DPAA') ) {
        //                     if ($main_list = $this->get_soi_dao()->get_items_w_name_w_merchant_sku(array("soi.so_no"=>$so_no, "soi.hidden_to_client"=>0))) {
        //                         $main_list_sku = array();
        //                         foreach ($main_list as $main_obj) {
        //                             $main_list_sku[] = $main_obj->get_prod_sku();
        //                         }
        //                     }
        //                     foreach ($main_list_sku as $main_sku) {
        //                         $assembly_sku_list = $this->get_prod_srv()->get_product_assembly_mapping_dao()->get_list(array('main_sku'=>$main_sku));
        //                         $assembly_sku_arr = array();
        //                         $assembly_sku_arr[] = $main_sku;
        //                         foreach ($assembly_sku_list as $assembly_sku_obj) {
        //                             $assembly_sku_arr[] = $assembly_sku_obj->get_sku();
        //                         }
        //                         $i = count($assembly_sku_arr);
        //                         foreach ($assembly_sku_arr as $sku) {
        //                             // var_dump($sku);
        //                             if ($itemlist = $this->get_soi_dao()->get_items_w_name_w_merchant_sku(array("soi.so_no"=>$so_no, "soi.prod_sku"=>$sku)))
        //                             {
        //                                 foreach($itemlist as $item_obj)
        //                                 {
        //                                     $tmp = $this->get_prod_srv()->get(array("sku"=>$sku));
        //                                     if(in_array($item_obj->get_item_sku(), $barcodelist)){
        //                                         $skubarcode = "<br><img src='".base_url()."order/integrated_order_fulfillment/get_barcode2/".$item_obj->get_item_sku()."' style='float:right'>";
        //                                     }else{
        //                                         $skubarcode = "";
        //                                     }
        //                                     $imagepath = base_url().get_image_file($tmp->get_image(),'s',$tmp->get_sku());
        //                                     if ($i > 1) {
        //                                         $acassembly_sku = $this->get_prod_srv()->get_product_assembly_mapping_dao()->get(array('sku'=>$sku));
        //                                         if ($acassembly_sku) {
        //                                             $aca_css = "background:#CCCCCC;";
        //                                             $item_qty = '';
        //                                         } else {
        //                                             $aca_css = '';
        //                                             $item_qty = $item_obj->get_assembly_qty();
        //                                         }
        //                                     } else{
        //                                         $aca_css = '';
        //                                         $item_qty = $item_obj->get_assembly_qty();
        //                                     }
        //                                     if ($item_obj->get_special_request()) {
        //                                 $special_request = "<p style='font-size: 11pt !important;font-weight:bold;font-style:italic;'>{$item_obj->get_special_request()}</p>";
        //                                     } else {
        //                                         $special_request = "";
        //                                     }
        //                                     $tmp_item_obj = $this->get_prod_srv()->get(array("sku"=>$item_obj->get_item_sku()));
        //                                     $item_battery_type = $this->get_battery_type($tmp_item_obj->get_battery());
        //                                     $replace["so_items"] .="
        //                         <tr>
        //                             <td align='center' style='{$aca_css}'><img src='{$imagepath}'><br>{$item_obj->get_merchant_sku()}</td>
        //                             <td valign=top style='{$aca_css}'>{$item_obj->get_main_prod_sku()} - {$item_obj->get_item_sku()}- {$item_obj->get_prod_name()} - {$tmp_item_obj->get_name()}$skubarcode
        //                                 {$special_request}
        //                             </td>
        //                             <td valign=top style='{$aca_css}'>{$item_battery_type}</td>
        //                             <td valign=top style='{$aca_css}'>{$item_qty}</td>
        //                         </tr>";
        //                                 }
        //                             }
        //                         }
        //                     }
        //                 } else {
        //                     if ($itemlist = $this->get_soi_dao()->get_items_w_name_w_merchant_sku(array("soi.so_no"=>$so_no)))
        //                     {
        //                         foreach($itemlist as $item_obj)
        //                         {
        //                             $tmp = $this->get_prod_srv()->get(array("sku"=>$item_obj->get_main_prod_sku()));
        //                             $item_battery_type = $this->get_battery_type($tmp->get_battery());

        //                             if(in_array($item_obj->get_item_sku(), $barcodelist)){
        //                             $skubarcode = "<br><img src='".base_url()."order/integrated_order_fulfillment/get_barcode2/".$item_obj->get_item_sku()."' style='float:right'>";
        //                             }else{
        //                             $skubarcode = "";
        //                             }
        //                             $imagepath = base_url().get_image_file($tmp->get_image(),'s',$tmp->get_sku());
        //                             if ($item_obj->get_special_request()) {
        //                                 $special_request = "<p style='font-size: 11pt !important;font-weight:bold;font-style:italic;'>{$item_obj->get_special_request()}</p>";
        //                             } else {
        //                                 $special_request = "";
        //                             }
        //                             $replace["so_items"] .="
        //                 <tr>
        //                     <td align='center'><img src='{$imagepath}'><br>{$item_obj->get_merchant_sku()}</td>
        //                     <td valign=top>
        //                         {$item_obj->get_item_sku()} - {$item_obj->get_name()}$skubarcode
        //                         {$special_request}
        //                     </td>
        //                     <td valign=top>{$item_battery_type}</td>
        //                     <td valign=top>{$item_obj->get_assembly_qty()}</td>
        //                 </tr>";
        //                         }
        //                     }
        //                 }

        //                 $replace["barcode"] = "<img src='".base_url()."order/integrated_order_fulfillment/get_barcode/$so_no' style='float:right'>";
        //                 if ($tpl_obj = $tpl_srv->get_msg_tpl_w_att(array("id" => $tpl_id, "lang_id" => $cur_lang_id), $replace))
        //                 {
        //                     $content .= $tpl_obj->template->get_message();
        //                 }

        //             }
        //         }
        //     }
        //     $content .= @file_get_contents(APPPATH.$this->get_config()->value_of("tpl_path").$tpl_id."/".$tpl_id."_footer.html");
        // }

        // return $content;
    }

    public function getDeliveryNote($soObj)
    {
        $result = [];
        $pbvObj = PlatformBizVar::where("selling_platform_id",$soObj->platform_id)->first();
        if ($pbvObj) {
            $platformId = $soObj->platform_id;
            $bizType = $soObj->biz_type;
            if ( ((substr($platformId, 0, 4) == 'TF3D') && ($bizType == 'SPECIAL'))
                    || (substr($platformId, 0, 4) == 'EXAA')
                    || (substr($platformId, 0, 4) == 'DPAA') ) {
                //@todo SPECIAL order
            } else {
                $soItem = $soObj->soItem;
                foreach($soItem as $item)
                {
                    $product = $item->product;

                    $merchantProductMapping = $product->merchantProductMapping;
                    $item->merchant_sku = $merchantProductMapping->merchant_sku;

                    $productAssemblyMapping = ProductAssemblyMapping::where("main_sku",$product->sku)->where("is_replace_main_sku",1)->first();
                    if ($productAssemblyMapping) {
                        $itemSku = $productAssemblyMapping->sku ? $productAssemblyMapping->sku : $product->sku;
                        $qty = $productAssemblyMapping->replace_qty*$item->qty ? $productAssemblyMapping->replace_qty*$item->qty : $item->qty;
                    } else {
                        $itemSku = $product->sku;
                        $qty = $item->qty;
                    }
                    $item->qty = $qty;
                    $item->item_sku = $itemSku;
                    $item->battery_type = $this->getBatteryType($product->battery);

                    $imagePath = $this->website . "/images/product/" . $product->sku . "_s" . $product->image;
                    if (!@fopen($imagePath,"r")) {
                        $imagePath = $this->website . "/images/product/imageunavailable_s.jpg";
                    }
                    $item->imagePath = $imagePath;
                }

                $deliveryCountry = Country::where("id",$soObj->delivery_country_id)->first();
                $deliveryAddress = ($soObj->delivery_company ? $soObj->delivery_company."\n" : "").trim(str_replace("|", "\n", $soObj->delivery_address))."\n".$soObj->delivery_city." ".$soObj->delivery_state." ".$soObj->delivery_postcode."\n".$deliveryCountry->name;

                $billingCountry = Country::where("id",$soObj->bill_country_id)->first();
                $billingAddress = ($soObj->bill_company ? $soObj->bill_company."\n" : "").trim(str_replace("|", "\n", $soObj->bill_address))."\n".$soObj->bill_city." ".$soObj->bill_state." ".$soObj->bill_postcode."\n".$billingCountry->name;

                $result["so"] = $soObj;
                $result["soItem"] = $soItem;
                $result["delivery_address"] = $deliveryAddress;
                $result["billing_address"] = $billingAddress;
                $result["website"] = $this->website;

                return $result;
            }
        }
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