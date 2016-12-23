<?php

namespace App\Console\Commands;

use App\Http\Requests\ProfitEstimateRequest;
use App\Models\AcceleratorShipping;
use App\Models\AmazonOrder;
use App\Models\Client;
use App\Models\CountryState;
use App\Models\CourierCost;
use App\Models\CourierInfo;
use App\Models\DeliveryTypeMapping;
use App\Models\ExchangeRate;
use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantProductMapping;
use App\Models\MerchantQuotation;
use App\Models\PlatformBizVar;
use App\Models\Product;
use App\Models\ProductAssemblyMapping;
use App\Models\ProductComplementaryAcc;
use App\Models\Sequence;
use App\Models\So;
use App\Models\SoExtend;
use App\Models\SoItem;
use App\Models\SoItemDetail;
use App\Models\SoPaymentStatus;
use App\Models\SpIncoterm;
use App\Models\WeightCourier;
use App\Repository\DeliveryQuotationRepository;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class OrderTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert amazon order to atomesg database';

    private $pricingService;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->pricingService = new PricingService(new DeliveryQuotationRepository());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $amazonOrderList = AmazonOrder::readyOrder()->get();
        foreach ($amazonOrderList as $amazonOrder) {
            if ($this->validateAmazonOrder($amazonOrder)) {
                \DB::beginTransaction();
                \DB::connection('mysql_esg')->beginTransaction();
                try {
                    $this->createSplitOrder($amazonOrder);
                    $this->createGroupOrder($amazonOrder);
                    $amazonOrder->acknowledge = 1;
                    $amazonOrder->save();
                    \DB::connection('mysql_esg')->commit();
                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::connection('mysql_esg')->rollBack();
                    \DB::rollBack();
                    mail('handy.hon@eservicesgroup.com', 'Amazon order import - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                }
            }
        }
    }

    /**
     * @param AmazonOrder $order
     *
     * @return bool
     */
    public function validateAmazonOrder(AmazonOrder $order)
    {
        $usCountries = ['US', 'CA', 'MX'];

        $countryCode = strtoupper(substr($order->platform, -2));
        //$countryCode = $order->amazonShippingAddress->country_code;
        $amazonAccount = strtoupper(substr($order->platform, 0, 2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));

        $amazonAccountName = '';
        switch ($amazonAccount) {
            case 'BC':
                $amazonAccountName = 'BrandsConnect';
                $alertEmail = 'amazon_us@brandsconnect.net';
                break;

            case 'PX':
                $amazonAccountName = 'ProductXpress';
                if (in_array($countryCode, $usCountries)) {
                    $alertEmail = 'amazonus@productxpress.com';
                } else {
                    $alertEmail = 'amazoneu@productxpress.com';
                }
                break;

            case 'CV':
                $amazonAccountName = 'ChatAndVision';
                $alertEmail = 'amazonus-group@chatandvision.com';
                break;

            case '3D':
                $amazonAccountName = '3Doodler';
                $alertEmail = 'brands@eservicesgroup.com';
                break;

            default:
                $alertEmail = 'handy.hon@eservicesgroup.com';
        }

        //$alertEmail = 'handy.hon@eservicesgroup.com';

        // check marketplace sku mapping
        $marketplaceSkuList = $order->amazonOrderItem->pluck('seller_sku')->toArray();
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereIn('marketplace_sku', $marketplaceSkuList)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($countryCode)
            ->get();
        $mappedMarketplaceSkuList = $marketplaceSkuMapping->pluck('marketplace_sku')->toArray();
        $notMappedMarketplaceSkuList = array_diff($marketplaceSkuList, $mappedMarketplaceSkuList);
        if ($notMappedMarketplaceSkuList) {
            $missingSku = implode(',', $notMappedMarketplaceSkuList);
            $subject = "[{$amazonAccountName}] Amazon Order Import Failed!";
            $message = "MarketPlace: {$order->platform}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
            $message .= "Marketplace SKU <{$missingSku}> not exist in esg admin. please add listing sku mapping first. Thanks";
            mail("{$alertEmail}, handy.hon@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');

            return false;
        }

        // Does sku have a merchant?
        $esgSkuList = $marketplaceSkuMapping->pluck('sku')->toArray();
        $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
            ->whereIn('sku', $esgSkuList)
            ->get();
        $mappedEsgSkuList = $merchantProductMapping->pluck('sku')->toArray();
        $notMappedEskSkuList = array_diff($esgSkuList, $mappedEsgSkuList);
        if ($notMappedEskSkuList) {
            $missingSku = implode(',', $notMappedEskSkuList);
            $subject = "[{$amazonAccountName}] Amazon Order Import Failed!";
            $message = "MarketPlace: {$order->platform}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
            $message .= "ESG SKU <{$missingSku}> not belong to any merchant. please add merchant sku mapping first. Thanks";
            mail("{$alertEmail}, handy.hon@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');

            return false;
        }

        // check selling platform is exist or not.
        $sellingPlatformId = $merchantProductMapping->pluck('short_id')->map(function ($item) use ($amazonAccount, $countryCode) {
            return 'AC-'.$amazonAccount.'AZ-'.$item.$countryCode;
        })->toArray();
        $platformIdFromDB = PlatformBizVar::whereIn('selling_platform_id', $sellingPlatformId)
            ->get()
            ->pluck('selling_platform_id')
            ->toArray();
        $notExistPlatform = array_diff($sellingPlatformId, $platformIdFromDB);
        if ($notExistPlatform) {
            $missingSellingPlatform = implode(',', $notExistPlatform);
            $subject = "[{$amazonAccountName}] Amazon Order Import Failed!";
            $message = "MarketPlace: {$order->platform}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
            $message .= "Selling Platform Id <{$missingSellingPlatform}> not exists in esg system, please add it. Thanks";
            mail("{$alertEmail}, handy.hon@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');

            return false;
        }

        // check sku delivery type.
        $notHaveDeliveryTypeSku = $marketplaceSkuMapping->where('delivery_type', '');
        if (!$notHaveDeliveryTypeSku->isEmpty()) {
            $notHaveDeliveryTypeSku->load('product');
            $subject = "[{$amazonAccountName}] Delivery Type Missing - Amazon Order Import Failed!";
            $message = "MarketPlace: {$order->platform}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";

            $message = $notHaveDeliveryTypeSku->reduce(function ($message, $marketplaceProduct) {
                return $message .= "Marketplace SKU <{$marketplaceProduct->marketplace_sku}>, product title <{$marketplaceProduct->product->name}>\r\n";
            }, $message);

            $message .= 'Please set delivery type in pricing tool, Thanks.';

            mail("{$alertEmail}, handy.hon@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');

            return false;
        }

        return true;
    }

    /**
     * @param AmazonOrder $order
     */
    public function createGroupOrder(AmazonOrder $order)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $amazonAccount = strtoupper(substr($order->platform, 0, 2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));

        $so = $this->createOrder($order, $order->amazonOrderItem);

        $countryCode = strtoupper(substr($order->platform, -2));
        $amazonAccount = strtoupper(substr($order->platform, 0, 2));
        $so->platform_id = 'AC-'.$amazonAccount.'AZ-GROUP'.$countryCode;
        $so->platform_split_order = 0;

        $splitOrder = So::where('platform_order_id', '=', $so->platform_order_id)
            ->where('platform_split_order', '=', '1')
            ->first();
        $so->incoterm = $splitOrder->incoterm;

        $so->save();

        $this->saveSoItem($so, $order->amazonOrderItem);
        $this->saveSoItemDetail($so, $order->amazonOrderItem);
        $this->saveSoPaymentStatus($so);
        $this->saveSoExtend($so, $order);

        $this->addAssemblyProduct($so);
        $this->addComplementaryAccessory($so);
        $this->setGroupOrderRecommendCourierAndCharge($so);
    }

    public function createSplitOrder(AmazonOrder $order)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));

        $merchant = [];
        foreach ($order->amazonOrderItem as $item) {
            $mapping = MarketplaceSkuMapping::where('marketplace_sku', '=', $item->seller_sku)
                ->where('marketplace_id', '=', $marketplaceId)
                ->where('country_id', '=', $countryCode)
                ->firstOrFail();

            $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
                ->where('sku', '=', $mapping->sku)
                ->firstOrFail();

            // group items by merchant (short id).
            if (!array_key_exists($merchantProductMapping->short_id, $merchant)) {
                $merchant[$merchantProductMapping->short_id] = new Collection();
            }

            $item->seller_sku = $mapping->sku;
            $item->mapping = $mapping;
            $merchant[$merchantProductMapping->short_id]->add($item);
        }

        foreach ($merchant as $merchantShortId => $items) {
            $so = $this->createOrder($order, $items);

            $countryCode = strtoupper(substr($order->platform, -2));
            $amazonAccount = strtoupper(substr($order->platform, 0, 2));
            $so->platform_id = 'AC-'.$amazonAccount.'AZ-'.$merchantShortId.$countryCode;
            $so->platform_group_order = 0;

            // Decide delivery type here.
            if ($order->fulfillment_channel === 'MFN') {
                $marketplaceProduct = MarketplaceSkuMapping::whereIn('sku', $items->pluck('seller_sku'))
                    ->whereMpControlId($items->first()->mapping->mp_control_id)
                    ->whereIn('delivery_type', ['EXP', 'EXPED', 'STD'])
                    ->orderBy(\DB::raw('FIELD(delivery_type, "EXP", "EXPED", "STD")'))
                    ->first();

                if ($marketplaceProduct) {
                    $so->delivery_type_id = $marketplaceProduct->delivery_type;
                }
            }

            // must after get correct delivery type id.
            $spIncoterm = SpIncoterm::wherePlatformId($so->platform_id)->whereDeliveryTypeId($so->delivery_type_id)->first();
            if ($spIncoterm) {
                $so->incoterm = $spIncoterm->incoterm;
            }
            $so->save();

            $this->saveSoItem($so, $items);
            $this->saveSoItemDetail($so, $items);
            $this->saveSoPaymentStatus($so);
            $this->saveSoExtend($so, $order);

            $this->addAssemblyProduct($so);
            $this->addComplementaryAccessory($so);
            $this->setSplitOrderRecommendCourierAndCharge($so);

        }
    }

    /***
     * @param AmazonOrder $order
     * @return \App\Models\Client
     */
    private function createOrUpdateClient(AmazonOrder $order)
    {
        $client = Client::firstOrNew(['email' => $order->buyer_email]);
        $client->password = bcrypt(Carbon::now());
        $client->forename = $order->buyer_name;
        $client->country_id = $order->amazonShippingAddress->country_code;
        $client->del_name = $order->amazonShippingAddress->name;
        $client->del_address_1 = $order->amazonShippingAddress->address_line_1;
        $client->del_address_2 = $order->amazonShippingAddress->address_line_2;
        $client->del_address_3 = $order->amazonShippingAddress->address_line_3;
        $client->del_postcode = $order->amazonShippingAddress->postal_code;
        $client->del_city = $order->amazonShippingAddress->city;
        $client->del_state = $order->amazonShippingAddress->state_or_region;
        $client->del_country_id = $order->amazonShippingAddress->country_code;
        $client->tel_3 = $order->amazonShippingAddress->phone;
        $client->save();

        return $client;
    }

    /***
     * @return int local order number.
     */
    private function generateSoNumber()
    {
        $sequence = Sequence::where('seq_name', '=', 'customer_order')->lockForUpdate()->first();
        $sequence->value += 1;
        $sequence->save();

        return $sequence->value;
    }

    /**
     * @param AmazonOrder $order
     * @param Collection  $orderItems
     *
     * @return So
     */
    private function createOrder(AmazonOrder $order, Collection $orderItems)
    {
        $newOrder = new So();
        $client = $this->createOrUpdateClient($order);
        $newOrder->so_no = $this->generateSoNumber();
        $newOrder->platform_order_id = $order->amazon_order_id;
        $newOrder->platform_id = 'AC-BCAZ-GROUPUS'; // it should depends on group order or split order. temporary set it this.
        $newOrder->txn_id = $order->amazon_order_id;
        $newOrder->client_id = $client->id;
        $newOrder->biz_type = 'AMAZON';
        $newOrder->weight = $this->calculateOrderWeight($orderItems);
        $newOrder->delivery_charge = $orderItems->pluck('shipping_price')->sum()
            - $orderItems->pluck('shipping_discount')->sum();
        $newOrder->discount = $orderItems->pluck('promotion_discount')->sum();
        $newOrder->amount = $newOrder->delivery_charge
            + $orderItems->pluck('item_price')->sum()
            - $newOrder->discount;
        $newOrder->currency_id = $order->currency;
        $newOrder->rate = ExchangeRate::where('from_currency_id', '=', $order->currency)
            ->where('to_currency_id', '=', 'USD')
            ->first()
            ->rate;
        $newOrder->vat_percent = 0;     // not sure.
        $newOrder->vat = 0;             // not sure.
        if ($order->fulfillment_channel === 'AFN') {
            $newOrder->delivery_type_id = 'FBA';
            // set order import date as FBA dispatch date. Fiona required.
            $newOrder->dispatch_date = Carbon::now();
        } else {
            // assume 'STD' first to fit database rule, need to decide it at createSplitOrder() and createGroupOrder() later.
            $newOrder->delivery_type_id = 'STD';
        }
        $newOrder->delivery_name = $order->amazonShippingAddress->name;
        $newOrder->delivery_address = implode(' | ', array_filter([
            $order->amazonShippingAddress->address_line_1,
            $order->amazonShippingAddress->address_line_2,
            $order->amazonShippingAddress->address_line_3,
        ]));
        $newOrder->delivery_postcode = $order->amazonShippingAddress->postal_code;
        $newOrder->delivery_city = $order->amazonShippingAddress->city;
        $newOrder->delivery_country_id = $order->amazonShippingAddress->country_code;
        $newOrder->delivery_state = CountryState::getStateId($order->amazonShippingAddress->country_code, $order->amazonShippingAddress->state_or_region);
        // if fulfillment by amazon, marked as shipped (6), if fulfillment by ESG, marked as 3, no need credit check.
        $newOrder->status = ($order->fulfillment_channel === 'AFN') ? '6' : '3';
        $newOrder->order_create_date = $order->purchase_date;
        $newOrder->del_tel_3 = $order->amazonShippingAddress->phone;
        $newOrder->bill_country_id = $order->amazonShippingAddress->country_code;
        $newOrder->create_on = Carbon::now();
        $newOrder->modify_on = Carbon::now();
        $newOrder->save();

        return $newOrder;
    }

    /**
     * @param So         $so
     * @param Collection $orderItem
     */
    private function saveSoItem(So $so, Collection $orderItem)
    {
        $lineNumber = 1;
        foreach ($orderItem as $item) {
            $newOrderItem = new SoItem();
            $newOrderItem->so_no = $so->so_no;
            $newOrderItem->line_no = $lineNumber++;
            $newOrderItem->prod_sku = $item->seller_sku;
            $newOrderItem->prod_name = $item->title;
            $newOrderItem->ext_item_cd = $item->order_item_id;
            $newOrderItem->qty = $item->quantity_ordered;
            $newOrderItem->unit_price = $item->item_price / $item->quantity_ordered;
            $newOrderItem->vat_total = 0;   // not sure.
            $newOrderItem->amount = $item->item_price;
            $newOrderItem->create_on = Carbon::now();
            $newOrderItem->modify_on = Carbon::now();
            $newOrderItem->save();
        }
    }

    /**
     * @param So         $so
     * @param Collection $orderItem
     */
    private function saveSoItemDetail(So $so, Collection $orderItem)
    {
        $lineNumber = 1;
        foreach ($orderItem as $item) {
            $newOrderItemDetail = new SoItemDetail();
            $newOrderItemDetail->so_no = $so->so_no;
            $newOrderItemDetail->item_sku = $item->seller_sku;
            $newOrderItemDetail->line_no = $lineNumber++;
            $newOrderItemDetail->qty = $item->quantity_ordered;
            $newOrderItemDetail->outstanding_qty = $item->quantity_ordered;
            $newOrderItemDetail->unit_price = $item->item_price / $item->quantity_ordered;
            $newOrderItemDetail->vat_total = 0;   // not sure.

            $request = new ProfitEstimateRequest();
            $request->merge([
                'id' => $item->mapping->id,
                'selling_price' => $newOrderItemDetail->unit_price,
            ]);

            $marginAndProfit = $this->pricingService->availableShippingWithProfit($request);
            if (!$marginAndProfit->isEmpty()) {
                $profit = $marginAndProfit->get($item->mapping->delivery_type)['profit'];
                if ($profit) {
                    $selectedProfit = $marginAndProfit->get($item->mapping->delivery_type)['profit'];
                    $selectedMargin = $marginAndProfit->get($item->mapping->delivery_type)['margin'];
                } else {
                    $selectedProfit = 0;
                    $selectedMargin = 0;
                }
            } else {
                $selectedProfit = 0;
                $selectedMargin = 0;
            }

            $newOrderItemDetail->profit = $selectedProfit * $item->quantity_ordered;
            $newOrderItemDetail->margin = $selectedMargin;
            $newOrderItemDetail->amount = $item->item_price;
            $newOrderItemDetail->create_on = Carbon::now();
            $newOrderItemDetail->modify_on = Carbon::now();
            $newOrderItemDetail->save();
        }
    }

    /**
     * @param So $so
     */
    private function saveSoPaymentStatus(So $so)
    {
        $soPaymentStatus = new SoPaymentStatus();
        $soPaymentStatus->so_no = $so->so_no;
        $soPaymentStatus->payment_gateway_id = $this->getPaymentGateway($so);
        $soPaymentStatus->payment_status = 'S';
        $soPaymentStatus->create_on = Carbon::now();
        $soPaymentStatus->modify_on = Carbon::now();
        $soPaymentStatus->save();
    }

    /**
     * @param So          $so
     * @param AmazonOrder $order
     */
    private function saveSoExtend(So $so, AmazonOrder $order)
    {
        $soExtend = new SoExtend();
        $soExtend->so_no = $so->so_no;
        $soExtend->create_on = Carbon::now();
        $soExtend->modify_on = Carbon::now();

        $soExtend->save();
    }

    private function getPaymentGateway(So $so)
    {
        $countryCode = strtolower(substr($so->platform_id, -2));
        $countryCode = ($countryCode === 'gb') ? 'uk' : $countryCode;
        $amazonAccount = strtolower(substr($so->platform_id, 3, 2));

        return $amazonAccount.'_amazon_'.$countryCode;
    }

    /**
     * Not contain assembly product.
     *
     * @param Collection $orderItems
     *
     * @return mixed
     */
    private function calculateOrderWeight(Collection $orderItems)
    {
        $skuQty = $orderItems->pluck('quantity_ordered', 'seller_sku')->toArray();
        $skuWeight = Product::whereIn('sku', array_keys($skuQty))->get()->pluck('weight', 'sku');

        $totalWeight = $skuWeight->map(function ($weight, $sku) use ($skuQty) {
            return $skuQty[$sku] * $weight;
        })->sum();

        return $totalWeight;
    }

    private function setSplitOrderRecommendCourierAndCharge(So $order)
    {
        $merchantId = $order->sellingPlatform->merchant_id;
        $deliveryCountry = $order->delivery_country_id;
        $deliveryType = $order->delivery_type_id;

        $quotationTypes = DeliveryTypeMapping::where('delivery_type', $deliveryType)
            ->where('merchant_type', 'ACCELERATOR')
            ->get()
            ->pluck('quotation_type');

        $skus = $order->soItem()->pluck('prod_sku');
        $products = Product::findMany($skus);
        $defaultWarehouses = $products->map(function ($product) {
            if ($product->default_ship_to_warehouse) {
                return $product->default_ship_to_warehouse;
            } else {
                return $product->merchantProductMapping->merchant->default_ship_to_warehouse;
            }
        })->unique('')->toArray();

        $acceleratorShipping = AcceleratorShipping::whereIn('merchant_id', [$merchantId, 'ALL'])
            ->whereIn('warehouse', $defaultWarehouses)
            ->whereIn('courier_type', $quotationTypes)
            ->where('country_id', $deliveryCountry)
            ->orderBy('merchant_id')
            ->orderBy(\DB::raw('FIELD(warehouse, "ES_HK", "ES_DGME", "4PXDG_PL")'))
            ->first();

        if (!$acceleratorShipping) {
            // TODO
            // send mail to info user courier missing
            return false;
        }

        $selectedCourierId = $acceleratorShipping->courier_id;
        //TODO
        // check battery compact

        $order->esg_quotation_courier_id = $selectedCourierId;

        $courier = CourierInfo::whereCourierId($selectedCourierId)->first();

        if ($courier->type === 'POSTAGE') {
            $shippingWeight = $order->weight;
        } else {
            $shippingWeight = max($order->weight, $order->vol_weight);
        }
        $weightId = WeightCourier::where('weight', '>=', $shippingWeight)->first()->id;
        $courierCost = $courier->courierCost()->where('dest_country_id', $order->delivery_country_id)
            ->where('dest_state_id', $order->delivery_state)
            ->where('weight_id', $weightId)
            ->orderBy('dest_state_id', 'DESC')
            ->first();

        if ($courierCost) {
            $currencyRate = ExchangeRate::getRate('HKD', $order->currency_id);
            $order->esg_delivery_cost = $courierCost->delivery_cost * (1 + $courier->surcharge / 100) * $currencyRate / 0.9725;
        } else {
            $message = "Courier: {$courier->courier_id} \r\n Country: {$order->delivery_country_id} \r\n State: {$order->delivery_state} \r\n WeightId: {$weightId} \r\n ";
            mail('handy.hon@eservicesgroup.com', 'Missing Delivery Cost', $message);
        }

        $order->save();
    }

    private function setGroupOrderRecommendCourierAndCharge(So $order)
    {
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_id)
            ->where('platform_split_order', '=', '1')
            ->get();

        $weightId = WeightCourier::getWeightId($order->weight);

        // if only one split order, group order follow split order.
        if (count($splitOrders) === 1) {
            $order->delivery_type_id = $splitOrders[0]->delivery_type_id;
            $order->esg_delivery_cost = $splitOrders[0]->esg_delivery_cost;
            $order->esg_delivery_offer = $splitOrders[0]->esg_delivery_offer;
            $order->esg_quotation_courier_id = $splitOrders[0]->esg_quotation_courier_id;

            return $order->save();
        }

        if ($splitOrders[0]->courierInfo) {
            $courierCost = $this->getCourierCost($order->delivery_country_id, $order->delivery_state, $weightId, $splitOrders[0]->courierInfo->courier_id);

            if ($courierCost) {
                $currencyRate = ExchangeRate::getRate('HKD', $order->currency_id);
                $deliveryChargeInHKD = $courierCost->delivery_cost * (100 + $splitOrders[0]->courierInfo->surcharge) / 100;

                $order->esg_delivery_cost = $courierCost->delivery_cost * $currencyRate;
                $order->esg_delivery_offer = $deliveryChargeInHKD * $currencyRate / 0.9725;
                $order->esg_quotation_courier_id = $splitOrders[0]->courierInfo->courier_id;

                return $order->save();
            }
        }

        return false;
    }

    /**
     * @param $merchantShortId
     *
     * @return Collection|static[]
     */
    private function getAvailableMerchantQuotation($merchantShortId)
    {
        return MerchantQuotation::availableQuotation()
            ->join('merchant', 'merchant.id', '=', 'merchant_quotation.merchant_id')
            ->where('merchant.short_id', '=', $merchantShortId)
            ->select('merchant_quotation.*')
            ->get();
    }

    private function getCourierCost($destCountryId, $destStateId, $weightId, $courierId)
    {
        return CourierCost::where('dest_country_id', '=', $destCountryId)
            ->where('dest_state_id', '=', $destStateId)
            ->where('weight_id', '=', $weightId)
            ->where('courier_id', '=', $courierId)
            ->first();
    }

    private function addComplementaryAccessory(So $order)
    {
        $skuInOrder = $order->soItem->pluck('qty', 'prod_sku')->toArray();
        $complementaryAccessory = ProductComplementaryAcc::active()->whereIn('mainprod_sku', array_keys($skuInOrder))
            ->where('dest_country_id', '=', $order->delivery_country_id)
            ->get();

        if (!$complementaryAccessory->isEmpty()) {
            $order->load('soItem');
            $lineNumber = count($order->soItem) + 1;
            foreach ($complementaryAccessory as $item) {
                $soItem = new SoItem();
                $soItem->so_no = $order->so_no;
                $soItem->line_no = $lineNumber;
                $soItem->prod_sku = $item->accessory_sku;
                $soItem->prod_name = $item->product->name;
                $soItem->qty = $skuInOrder[$item->mainprod_sku];
                $soItem->unit_price = 0;
                $soItem->vat_total = 0;
                $soItem->amount = 0;
                $soItem->create_on = Carbon::now();
                $soItem->modify_on = Carbon::now();
                $order->soItem()->save($soItem);

                $soItemDetail = new SoItemDetail();
                $soItemDetail->so_no = $order->so_no;
                $soItemDetail->line_no = $lineNumber;
                $soItemDetail->item_sku = $item->accessory_sku;
                $soItemDetail->qty = $skuInOrder[$item->mainprod_sku];
                $soItemDetail->outstanding_qty = $skuInOrder[$item->mainprod_sku];
                $soItemDetail->unit_price = 0;
                $soItemDetail->vat_total = 0;
                $soItemDetail->amount = 0;
                $soItemDetail->create_on = Carbon::now();
                $soItemDetail->modify_on = Carbon::now();
                $order->soItemDetail()->save($soItemDetail);

                ++$lineNumber;
            }
        }
    }

    private function addAssemblyProduct(So $order)
    {
        $skuInOrder = $order->soItem->pluck('qty', 'prod_sku')->toArray();
        $assemblyMapping = ProductAssemblyMapping::active()->whereIn('main_sku', array_keys($skuInOrder))
            ->where('is_replace_main_sku', '=', '0')
            ->get();

        if (!$assemblyMapping->isEmpty()) {
            $order->load('soItem');
            $lineNumber = $order->soItem->max('line_no') + 1;
            foreach ($assemblyMapping as $item) {
                $soItem = new SoItem();
                $soItem->so_no = $order->so_no;
                $soItem->line_no = $lineNumber;
                $soItem->prod_sku = $item->sku;
                $soItem->prod_name = $item->product->name;
                $soItem->qty = $skuInOrder[$item->main_sku] * $item->replace_qty;
                $soItem->unit_price = 0;
                $soItem->vat_total = 0;
                $soItem->amount = 0;
                $soItem->hidden_to_client = 1;
                $soItem->create_on = Carbon::now();
                $soItem->modify_on = Carbon::now();
                $order->soItem()->save($soItem);

                $soItemDetail = new SoItemDetail();
                $soItemDetail->so_no = $order->so_no;
                $soItemDetail->line_no = $lineNumber;
                $soItemDetail->item_sku = $item->sku;
                $soItemDetail->qty = $skuInOrder[$item->main_sku] * $item->replace_qty;
                $soItemDetail->outstanding_qty = $soItemDetail->qty;
                $soItemDetail->unit_price = 0;
                $soItemDetail->vat_total = 0;
                $soItemDetail->amount = 0;
                $soItemDetail->create_on = Carbon::now();
                $soItemDetail->modify_on = Carbon::now();
                $order->soItemDetail()->save($soItemDetail);

                ++$lineNumber;
            }
        }
    }
}
