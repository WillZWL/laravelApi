<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use Excel;

class PriceUploadService
{
    const PRODUCT_UPDATED = 1;
    const PRICE_UPDATED = 2;
    const INVENTORY_UPDATED = 4;
    const PRODUCT_DISCONTINUED = 64;

    public function handleUploadFile($fileName = '')
    {
        if (file_exists($fileName)) {
            $message = '';
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) use (&$message) {
                $sheetItems = $reader->all();
                $sheetItems = $sheetItems->toArray();
                array_filter($sheetItems);

                foreach ($sheetItems as $item) {
                    $skuMapping = '';
                    if (trim($item['marketplace_id']) && trim($item['country_id']) && trim($item['esg_sku']) && trim($item['mp_sku']) ) {
                        $skuMapping = MarketplaceSkuMapping::where("marketplace_sku",$item['mp_sku'])
                                                           ->where("country_id",$item['country_id'])
                                                           ->where("marketplace_id",$item['marketplace_id'])
                                                           ->where("sku",$item['esg_sku'])
                                                           ->first();
                    }

                    if ($skuMapping) {
                        \DB::connection('mysql_esg')->beginTransaction();
                        try {
                            $this->updateMarketplaceSkuMapping($skuMapping,$item);
                            \DB::connection('mysql_esg')->commit();
                        } catch (\Exception $e) {
                            \DB::connection('mysql_esg')->rollBack();
                            $message .= 'Price Upload - Exception: '.$e->getMessage()."<br/>";
                            mail('milo.chen@eservicesgroup.com', 'Price Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                        }
                    } else {
                        $message .= 'marketplace_id:'.$item['marketplace_id']." country_id: ".$item['country_id']." mp_sku: ".$item['mp_sku']." not found.\r\n";
                    }

                }
            }, 'UTF-8');
            return $message;
        }
    }


    private function updateMarketplaceSkuMapping($skuMapping,$item = [])
    {
        if ($skuMapping->inventory != $item['listing_quantity']) {
            $skuMapping->inventory = $item['listing_quantity'];
            $skuMapping->process_status = $skuMapping->process_status | self::INVENTORY_UPDATED;
        }
        $skuMapping->delivery_type = $item['delivery_type'];
        if ($skuMapping->delivery_type == "FBA") {
            $skuMapping->fulfillment = "AFN";
        } else {
            $skuMapping->fulfillment = "MFN";
        }
        if ($skuMapping->price != $item['selling_price']) {
            $skuMapping->price = $item['selling_price'];
            $skuMapping->process_status = $skuMapping->process_status | self::PRICE_UPDATED;
        }
        $skuMapping->listing_status = $item['listing_status'];
        if ($skuMapping->listing_status === "N") {
            $skuMapping->process_status = $skuMapping->process_status | self::PRODUCT_DISCONTINUED;
        } elseif ($skuMapping->listing_status === "Y") {
            $skuMapping->process_status = $skuMapping->process_status ^ self::PRODUCT_DISCONTINUED;
        }

        $skuMapping->save();
    }

}