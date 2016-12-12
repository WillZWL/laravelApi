<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use Excel;

class PriceUploadService
{

    public function handleUploadFile($fileName = '')
    {
        if (file_exists($fileName)) {
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) {
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
                            mail('milo.zhang@eservicesgroup.com', 'Price Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                        }
                    }

                }
            }, 'UTF-8');
        }
    }


    private function updateMarketplaceSkuMapping($skuMapping,$item = [])
    {
        $skuMapping->inventory = $item['listing_quantity'];
        $skuMapping->delivery_type = $item['delivery_type'];
        $skuMapping->price = $item['selling_price'];
        $skuMapping->listing_status = $item['listing_status'];
        $skuMapping->save();
    }

}