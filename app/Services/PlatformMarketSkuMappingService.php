<?php

namespace App\Services;

use App\Models\MpControl;
use App\Models\MarketplaceSkuMapping;
use App\Models\SellingPlatform;
use App\Models\PlatformBizVar;
use Excel;
use Illuminate\Http\Request;
use App\Repository\PlatformMarketOrderRepository;

class PlatformMarketSkuMappingService
{
    use ApiPlatformTraitService;

    private $platformGroup = array(
            'amazon' => 'AZ',
            'lazada' => 'LZ',
            'priceminister' => 'PM',
            'fnac' => 'FN',
            'qoo10' => 'QO',
            'newegg' => 'NE',
            'tanga' => 'TG',
        );
    public function __construct($stores = null)
    {
        $this->stores = $stores;
    }

    public function uploadMarketplaceSkuMapping($fileName = '')
    {
        $result = [];
        $filePath = 'storage/marketplace-sku-mapping/'.$fileName;
        Excel::selectSheetsByIndex(0)->load($filePath, function ($reader) {
            $sheetItem = $reader->all();
            $mappingData = null;
            foreach ($sheetItem as $item) {
                $itemData = $item->toArray();
                foreach ($this->stores as $store) {
                    $marketplaceId = $store->store_code.$store->marketplace;
                    $countryCode = $store->country;
                    $currency = $store->currency;
                    if (($itemData['marketplace_id'] == $marketplaceId)
                        && ($itemData['country_id']) == $countryCode) {
                        $mpControl = MpControl::where('marketplace_id', $marketplaceId)
                                        ->where('country_id', '=', $countryCode)
                                        ->where('status', '=', '1')
                                        ->first();
                        if ($mpControl) {
                            $mappingData = [];
                            $mappingData['marketplace_sku'] = $itemData['marketplace_sku'];
                            $mappingData['sku'] = $itemData['esg_sku'];
                            $mappingData['mp_category_id'] = $itemData['mp_category_id'];
                            $mappingData['mp_sub_category_id'] = $itemData['mp_sub_category_id'];
                            $mappingData['delivery_type'] = $itemData['delivery_type'] ? $itemData['delivery_type'] : 'EXP';
                            $mappingData['mp_control_id'] = $mpControl->control_id;
                            $mappingData['marketplace_id'] = $marketplaceId;
                            $mappingData['country_id'] = $countryCode;
                            $mappingData['lang_id'] = 'en';
                            $mappingData['asin'] = isset($itemData['asin']) ? $itemData['asin'] : '';
                            $mappingData['currency'] = $currency;
                            $this->createOrUpdateMarketplaceSkuMapping($mappingData);
                        } else{
                            $result["error_sku"][] = $itemData['esg_sku'];
                        }
                    } else {
                       $result["error_sku"][] = $itemData['esg_sku'];
                    }
                }
            }
        });
        return $result;
    }

    //2 init Marketplace SKU Mapping
    public function updateOrCreateSellingPlatform($storeName, $store)
    {
        $countryCode = strtoupper(substr($storeName, -2));
        $platformAccount = strtoupper(substr($storeName, 0, 2));
        $marketplaceId = strtoupper(substr($storeName, 0, -2));
        $marketplace = strtolower(substr($storeName, 2, -2));
        $id = 'AC-'.$platformAccount.$this->platformGroup[$marketplace].'-GROUP'.$countryCode;
        $object = array();
        $object['id'] = $id;
        $object['type'] = 'ACCELERATOR';
        $object['marketplace'] = $marketplaceId;
        $object['merchant_id'] = 'ESG';
        $object['name'] = $store['name'].' GROU '.$countryCode;
        $object['description'] = $store['name'].' GROU '.$countryCode;
        $object['create_on'] = date('Y-m-d H:i:s');
        $sellingPlatform = SellingPlatform::updateOrCreate(
            ['id' => $id],
            $object
        );

        return $sellingPlatform;
    }
    //3 init Marketplace SKU Mapping
    public function updateOrCreatePlatformBizVar($storeName, $store)
    {
        $countryCode = strtoupper(substr($storeName, -2));
        $platformAccount = strtoupper(substr($storeName, 0, 2));
        $marketplaceId = strtoupper(substr($storeName, 0, -2));
        $marketplace = strtolower(substr($storeName, 2, -2));
        $sellingPlatformId = 'AC-'.$platformAccount.$this->platformGroup[$marketplace].'-GROUP'.$countryCode;
        $object = array();
        $object['selling_platform_id'] = $sellingPlatformId;
        $object['platform_country_id'] = $countryCode;
        $object['dest_country'] = $countryCode;
        if(isset($store['currency'])){
            $currency =  $store['currency'];
        }else if(isset($store['orderCurrency'])){
            $currency =  $store['orderCurrency'];
        }
        $object['platform_currency_id'] = $currency;
        $object['language_id'] = 'en';
        $object['delivery_type'] = 'EXP';
        $object['create_on'] = date('Y-m-d H:i:s');
        $platformBizVar = PlatformBizVar::updateOrCreate(
            ['selling_platform_id' => $sellingPlatformId],
            $object
        );

        return $platformBizVar;
    }
    //4 init Marketplace SKU Mapping
    public function createOrUpdateMarketplaceSkuMapping($mappingData)
    {
        $object = array();
        $object['marketplace_sku'] = $mappingData['marketplace_sku'];
        $object['sku'] = $mappingData['sku'];
        $object['mp_control_id'] = $mappingData['mp_control_id'];
        $object['marketplace_id'] = $mappingData['marketplace_id'];
        $object['country_id'] = $mappingData['country_id'];
        $object['lang_id'] = $mappingData['lang_id'];
        $object['mp_category_id'] = $mappingData['mp_category_id'];
        $object['mp_sub_category_id'] = $mappingData['mp_sub_category_id'];
        $object['asin'] = $mappingData['asin'];
        $object['condition'] = 'New';
        $object['delivery_type'] = $mappingData['delivery_type'];
        $object['currency'] = $mappingData['currency'];
        $object['status'] = 1;
        //$object['create_on'] = date("Y-m-d H:i:s");
       // MarketplaceSkuMapping::firstOrCreate($object);
        $marketplaceSkuMapping = MarketplaceSkuMapping::updateOrCreate(
            [
                'marketplace_sku' => $mappingData['marketplace_sku'],
                'marketplace_id' => $mappingData['marketplace_id'],
                'country_id' => $mappingData['country_id'],
            ],
            $object
        );
    }

    public function exportLazadaPricingCsv(Request $request)
    {
        $marketplaceQuery = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
            ->select('marketplace_sku_mapping.*', 'product.name as product_name');
        $countryCode = $request->input('country_code');
        if ($request->input('all_marketplace')) {
            $marketplaceSkuMapping = $marketplaceQuery->where('marketplace_id', 'like', '%LAZADA')->get();
        } elseif (empty($countryCode)) {
            $marketplaceId = $request->input('marketplace_id');
            $marketplaceSkuMapping = $marketplaceQuery->where('marketplace_id', '=', $marketplaceId)
                ->get();
        } else {
            $marketplaceId = $request->input('marketplace_id');
            $marketplaceSkuMapping = $marketplaceQuery->where('marketplace_id', '=', $marketplaceId)
                ->where('country_id', '=', $countryCode)
                ->get();
        }
        $cellData[] = array('Marketplace', 'Country', 'ESG SKU', 'SellerSku', 'QTY', 'Price', 'SalePrice', 'SaleStartDate', 'SaleEndDate', 'Name', 'Delivery Type', 'Profit', 'Margin', 'Listing Status');
        foreach ($marketplaceSkuMapping as $mappingData) {
            $cellRow = array(
                'marketplace_id' => $mappingData->marketplace_id,
                'country_id' => $mappingData->country_id,
                'sku' => $mappingData->sku,
                'marketplace_sku' => $mappingData->marketplace_sku,
                'inventory' => $mappingData->inventory,
                'price' => round($mappingData->price * 1.3, 2),
                'saleprice' => $mappingData->price,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+4 year')),
                'name' => $mappingData->product_name,
                'delivery_type' => $mappingData->delivery_type,
                'profit' => $mappingData->profit,
                'margin' => $mappingData->margin,
                'listing_status' => $mappingData->listing_status,
            );
            $cellData[] = $cellRow;
        }
        //Excel文件导出功能
        Excel::create('LazadaPricingDetail', function ($excel) use ($cellData) {
            $excel->sheet('LazadaPricing', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('csv');
    }

    public function exportMarketplaceSkuMapping($marketplace_id)
    {
        $lists = MarketplaceSkuMapping::where('marketplace_id', $marketplace_id)->get();
        $path = \Storage::disk('skuMapping')->getDriver()->getAdapter()->getPathPrefix()."excel/";

        $cellData[] = [
            'marketplace_sku',
            'esg_sku',
            'marketplace_id',
            'country_id',
            'mp_category_id',
            'mp_sub_category_id',
            'delivery_type',
            'asin',
            'lang_id',
            'currency'
        ];
        foreach ($lists as $value) {
            $cellData[] = [
                'marketplace_sku' => $value->marketplace_sku,
                'esg_sku' => $value->sku,
                'marketplace_id' => $value->marketplace_id,
                'country_id' => $value->country_id,
                'mp_category_id' => $value->mp_category_id,
                'mp_sub_category_id' =>$value->mp_sub_category_id,
                'delivery_type' => $value->delivery_type,
                'asin' => $value->asin,
                'lang_id' => $value->lang_id,
                'currency' => $value->currency
            ];
        };

        $cellDataArr['mapping'] = $cellData;
        $excelFileName = $marketplace_id."_Marketplace_Sku_Mapping";
        $excelFile = $this->generateMultipleSheetsExcel($excelFileName,$cellDataArr,$path);
        return $excelFile["path"].$excelFile["file_name"];
    }
}
