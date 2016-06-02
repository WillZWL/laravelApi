<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Country;
use App\Models\Marketplace;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpCategory;
use App\Models\MpControl;
use App\Models\PlatformBizVar;
use App\Models\Product;
use Illuminate\Http\Request;

use App\Http\Requests;

class ListingSkuManagement extends Controller
{
    const PRODUCT_UPDATED = 1;
    const PRICE_UPDATED = 2;
    const INVENTORY_UPDATED = 4;
    const PRODUCT_DISCONTINUED = 8;

    public function index(Request $request)
    {
        $data['esgSku'] = $request->input('esgSku');
        $data['marketplaceAndCountry'] = Marketplace::join('mp_control', 'marketplace.id', '=', 'mp_control.marketplace_id')
            ->get(['mp_control.marketplace_id', 'mp_control.country_id'])
            ->groupBy('marketplace_id')
            ->toJson();

        return response()->view('listing.index', $data);
    }

    public function getListing(Request $request)
    {
        $esgSKU = $request->input('esgSKU');
        $marketplaceId = $request->input('marketplace');
        $countryId = $request->input('country');

        $data['marketplaces'] = Marketplace::whereStatus(1)->get();

        $data['esgSku'] = $esgSKU;
        $data['marketplaceId'] = $marketplaceId;
        $data['country'] = $countryId;

        $marketplaceSKU = MarketplaceSkuMapping::whereSku($esgSKU)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryId)
            ->first();

        $data['marketplaceSKU'] = $marketplaceSKU;

        //$allCountries = MpCategory::join('product', function ($q) {
        //        $q->on('esg_cat_id', '=', 'cat_id')
        //            ->on('esg_sub_cat_id', '=', 'sub_cat_id')
        //            ->on('esg_sub_sub_cat_id', '=', 'sub_sub_cat_id');
        //})->join('mp_control', 'mp_control.control_id', '=', 'mp_category.control_id')
        //    ->where('product.sku', '=', $esgSku)
        //    ->where('mp_control.marketplace_id', '=', $marketplaceId)
        //    ->get(['mp_control.country_id', 'mp_category.parent_cat_id', 'mp_category.id'])
        //    ->keyBy('country_id')
        //    ->toArray();

        // get first listing sku listed countries.
        //$listedCountries = MarketplaceSkuMapping::where('marketplace_sku_mapping.marketplace_id', '=', $marketplaceId)
        //    ->where('marketplace_sku', '=', $marketplaceSkus->pluck('marketplace_sku')->first())
        //    ->get(['marketplace_sku_mapping.country_id', 'marketplace_sku_mapping.mp_category_id', 'marketplace_sku_mapping.mp_sub_category_id'])
        //    ->keyBy('country_id')
        //    ->toArray();

        //$listingCountries = array_merge($allCountries, $listedCountries);

        //$data['listingCountries'] = $listingCountries;

        $mpCategories = MpCategory::join('mp_control', 'mp_category.control_id', '=', 'mp_control.control_id')
            ->where('mp_control.country_id', '=', $countryId)
            ->where('mp_control.marketplace_id', '=', $marketplaceId)
            ->get();

        //$data['marketplaceSkus'] = $marketplaceSkus;

        return response()->view('listing.form', $data);
    }

    public function getData(Request $request)
    {
        $esgSku = $request->input('esgSku');
        $marketplaceId = $request->input('marketplace');
        $countryId = $request->input('country');
        //$marketplaceSku = $request->input('marketplaceSku');

        $marketplaceSkus = MarketplaceSkuMapping::whereSku($esgSku)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryId)
            ->get();
        $data['marketplaceSkus'] = $marketplaceSkus;


        $mpCategories = MpCategory::join('mp_control', 'mp_control.control_id', '=', 'mp_category.control_id')
            ->where('mp_control.marketplace_id', '=', $marketplaceId)
            ->where('mp_control.country_id', '=', $countryId)
            ->where('mp_category.level', '=', '1')
            ->get(['mp_category.*']);

        $data['mpCategories'] = $mpCategories;

        return response()->json($data);
    }

    public function getCategory(Request $request)
    {
        $marketplaceId = $request->input('marketplace');
        $countryId = $request->input('country');
        $categoryId = $request->input('categoryId');

        $mpCategories = MpCategory::join('mp_control', 'mp_control.control_id', '=', 'mp_category.control_id')
            ->where('mp_control.marketplace_id', '=', $marketplaceId)
            ->where('mp_control.country_id', '=', $countryId)
            ->where('mp_category.parent_cat_id', '=', $categoryId)
            ->get(['mp_category.*']);

        $data['mpCategories'] = $mpCategories;

        return response()->json($data);
    }

    public function add(Request $request)
    {
        $country = Country::findOrFail($request->input('country'));
        $marketplaceSku = trim($request->input('marketplaceSku'));
        $esgSku = trim($request->input('esgSku'));
        $ean = trim($request->input('EAN'));
        $upc = trim($request->input('UPC'));
        $asin = trim($request->input('ASIN'));

        $marketplaceControl = MpControl::where('marketplace_id', '=', $request->input('marketplace'))
            ->where('country_id', '=', $request->input('country'))
            ->firstOrFail();

        $marketplaceSkuMapping = MarketplaceSkuMapping::where('marketplace_sku', $marketplaceSku )
            ->where('sku', $esgSku)
            ->where('marketplace_id', $request->input('marketplace'))
            ->where('country_id', $request->input('country'))
            ->first();

        if (!$marketplaceSkuMapping) {
            $marketplaceSkuMapping = new MarketplaceSkuMapping();
        }

        $marketplaceSkuMapping->marketplace_sku = $marketplaceSku;
        $marketplaceSkuMapping->sku = $esgSku;
        $marketplaceSkuMapping->mp_control_id = $marketplaceControl->control_id;
        $marketplaceSkuMapping->marketplace_id = $request->input('marketplace');
        $marketplaceSkuMapping->country_id = $request->input('country');
        $marketplaceSkuMapping->inventory = $request->input('inventory');
        $marketplaceSkuMapping->ean = $ean;
        $marketplaceSkuMapping->upc = $upc;
        $marketplaceSkuMapping->asin = $asin;
        $marketplaceSkuMapping->process_status = self::PRODUCT_UPDATED | self::INVENTORY_UPDATED;     // waiting for post product feed to amazon.
        $marketplaceSkuMapping->mp_category_id = $request->input('categoryId');
        $marketplaceSkuMapping->mp_sub_category_id = $request->input('subCategoryId');
        $marketplaceSkuMapping->currency = $country->currency_id;
        $marketplaceSkuMapping->lang_id = $country->language_id;

        $marketplaceSkuMapping->save();

        return redirect('http://admincentre.eservicesgroup.com/marketing/listing_sku_management');
    }

    public function save(Request $request)
    {
        $countryCode = substr($request->input('sellingPlatform'), -2);
        $marketplaceId = substr($request->input('sellingPlatform'), 0, -2);

        $mapping = MarketplaceSkuMapping::whereMarketplaceSku($request->input('marketplace_sku'))
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryCode)
            ->firstOrFail();

        $mapping->process_status = $mapping->process_status | self::PRICE_UPDATED;
        $mapping->listing_status = $request->input('listingStatus');
        if ($mapping->listing_status === 'N') {
            $mapping->process_status = $mapping->process_status | self::PRODUCT_DISCONTINUED;
        }
        $mapping->delivery_type = $request->input('delivery_type');
        switch ($mapping->delivery_type) {
            case 'FBA':
                $mapping->fulfillment = 'AFN';
                break;
            default :
                $mapping->fulfillment = 'MFN';
                break;
        }

        $mapping->price = $request->input('price');
        $mapping->profit = $request->input('profit');
        $mapping->margin = $request->input('margin');
        if ($mapping->save()) {
            echo json_encode('success');
        } else {
            echo json_encode('save failure');
        }
    }
}
