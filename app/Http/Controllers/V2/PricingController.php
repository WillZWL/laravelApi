<?php

namespace App\Http\Controllers\V2;

use App\Services\PricingToolService;
use App\Models\Marketplace;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\Product;
use App\Models\HscodeCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PricingController extends Controller
{
    private $pricingToolService;

    public function __construct(PricingToolService $pricingToolService)
    {
        $this->pricingToolService = $pricingToolService;
    }

    public function index(Request $request)
    {
        $request->flash();
        $marketplaces = Marketplace::whereStatus(1)->get(['id']);
        $skuList = $this->getSkuList($request);

        $data = [];
        if ($request->input('marketplaceSku')) {
            $data = $this->getPriceInfo($request);
        }

        return response()->view('v2.pricing.index', compact('marketplaces', 'skuList', 'data'));
    }

    public function getSkuList(Request $request)
    {
        $search = $request->input('search');

        $marketplaceProducts = MarketplaceSkuMapping::select('product.sku', 'name', 'marketplace_sku', 'mp_control_id', 'marketplace_sku_mapping.asin', 'mp_category_id', 'mp_sub_category_id')
            ->join('atomesg.mp_control', 'mp_control_id', '=', 'control_id')
            ->join('atomesg.product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
            ->join('atomesg.sku_mapping', 'product.sku', '=', 'sku_mapping.sku')
            ->where('mp_control.marketplace_id', '=', $request->input('marketplace'))
            ->where(function ($sql) use ($search) {
                return $sql->where('product.sku', 'like', "%{$search}%")
                    ->orWhere('product.name', 'like', "%{$search}%")
                    ->orWhere('sku_mapping.ext_sku', 'like', "%{$search}%");
            })
            ->groupBy(['marketplace_sku', 'mp_control.marketplace_id'])
            ->orderBy('marketplace_sku');

        $products = $marketplaceProducts->paginate(1000);

        return $products;
    }

    public function getPriceInfo(Request $request)
    {
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereMarketplaceSku($request->input('marketplaceSku'))
            ->join('atomesg.mp_control', 'mp_control_id', '=', 'control_id')
            ->where('mp_control.marketplace_id', '=', $request->input('marketplace'))
            ->orderBy(\DB::raw('FIELD(marketplace_sku_mapping.country_id, "MY", "PH", "SG", "TH", "JP", "DE", "ES", "FR", "GB", "ID", "IT", "MX", "CA", "US")'), 'DESC')
            ->get();

        $result = [];
        foreach ($marketplaceSkuMapping as $mappingItem) {
            $request->merge([
                'id' => $mappingItem->id,
                'country' => $mappingItem->country_id,
                'price' => $mappingItem->price,
                'sku' => $mappingItem->sku,
                'selectedDeliveryType' => $mappingItem->delivery_type,
            ]);

            $productObj = Product::where('sku', '=', $mappingItem->sku)->first();
            $hscodeCategoryObj = HscodeCategory::where('id', '=', $productObj->hscode_cat_id)->first();

            $result[$request->input('marketplace').$request->input('country')]['deliveryOptions'] = $this->pricingToolService->getPricingInfo($request);

            $result[$request->input('marketplace').$request->input('country')]['listingStatus'] = $mappingItem->listing_status;
            $result[$request->input('marketplace').$request->input('country')]['inventory'] = $mappingItem->inventory;
            $result[$request->input('marketplace').$request->input('country')]['platformBrand'] = $mappingItem->brand;
            $result[$request->input('marketplace').$request->input('country')]['condition'] = $mappingItem->condition;
            $result[$request->input('marketplace').$request->input('country')]['conditionNote'] = $mappingItem->condition_note;
            $result[$request->input('marketplace').$request->input('country')]['fulfillmentLatency'] = $mappingItem->fulfillment_latency;
            $result[$request->input('marketplace').$request->input('country')]['asin'] = $mappingItem->asin;
            $result[$request->input('marketplace').$request->input('country')]['currency'] = $mappingItem->currency;
            $result[$request->input('marketplace').$request->input('country')]['price'] = $mappingItem->price;
            $result[$request->input('marketplace').$request->input('country')]['delivery_type'] = $mappingItem->delivery_type;
            $result[$request->input('marketplace').$request->input('country')]['margin'] = $mappingItem->margin;
            $result[$request->input('marketplace').$request->input('country')]['weight'] = $productObj->weight;
            $result[$request->input('marketplace').$request->input('country')]['vol_weight'] = $productObj->vol_weight;
            $result[$request->input('marketplace').$request->input('country')]['link'] = $mappingItem->link.$mappingItem->asin;
            $result[$request->input('marketplace').$request->input('country')]['hscode_category'] = $hscodeCategoryObj->name;
        }

        return $result;
        //return response()->view('v2.pricing.pricing-table', ['data' => $result]);
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

        $mpControl = MpControl::select(['link'])
            ->where('marketplace_id', '=', $marketplaceId)
            ->where('country_id', '=', $countryCode)
            ->firstOrFail();

        $request->merge([
            'id' => $marketplaceMapping->id,
            'marketplace' => $marketplaceMapping->marketplace_id,
            'country' => $marketplaceMapping->country_id,
            'sku' => $marketplaceMapping->product->sku,
            'selectedDeliveryType' => $marketplaceMapping->delivery_type,
        ]);
        $hscodeCategoryObj = HscodeCategory::where('id', '=', $marketplaceMapping->product->hscode_cat_id)->first();

        $result[$request->input('sellingPlatform')]['deliveryOptions'] = $this->pricingToolService->getPricingInfo($request);
        $result[$request->input('sellingPlatform')]['listingStatus'] = $marketplaceMapping->listing_status;
        $result[$request->input('sellingPlatform')]['inventory'] = $marketplaceMapping->inventory;
        $result[$request->input('sellingPlatform')]['platformBrand'] = $marketplaceMapping->brand;
        $result[$request->input('sellingPlatform')]['condition'] = $marketplaceMapping->condition;
        $result[$request->input('sellingPlatform')]['conditionNote'] = $marketplaceMapping->condition_note;
        $result[$request->input('sellingPlatform')]['fulfillmentLatency'] = $marketplaceMapping->fulfillment_latency;
        $result[$request->input('sellingPlatform')]['asin'] = $marketplaceMapping->asin;
        $result[$request->input('sellingPlatform')]['currency'] = $marketplaceMapping->currency;
        $result[$request->input('sellingPlatform')]['price'] = $marketplaceMapping->price;
        $result[$request->input('sellingPlatform')]['delivery_type'] = $marketplaceMapping->delivery_type;
        $result[$request->input('sellingPlatform')]['margin'] = $marketplaceMapping->margin;
        $result[$request->input('sellingPlatform')]['weight'] = $marketplaceMapping->product->weight;
        $result[$request->input('sellingPlatform')]['vol_weight'] = $marketplaceMapping->product->vol_weight;
        $result[$request->input('sellingPlatform')]['link'] = $mpControl->link.$marketplaceMapping->asin;
        $result[$request->input('sellingPlatform')]['hscode_category'] = $hscodeCategoryObj->name;

        return response()->view('v2.pricing.platform-pricing-info', ['data' => $result]);
    }
}
