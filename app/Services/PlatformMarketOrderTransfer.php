<?php

namespace App\Services;

use App\Http\Requests\ProfitEstimateRequest;
use App\Models\AcceleratorShipping;
use App\Models\CourierInfo;
use App\Models\DeliveryTypeMapping;
use App\Models\SalesOrderStatistic;
use App\Repository\DeliveryQuotationRepository;
use App\Services\PlatformValidate\AmazonValidateService;
use App\Services\PlatformValidate\LazadaValidateService;
use App\Services\PlatformValidate\PriceMinisterValidateService;
use App\Services\PlatformValidate\FnacValidateService;
use App\Services\PlatformValidate\NeweggValidateService;
use App\Services\PlatformValidate\TangaValidateService;
use App\Services\PlatformValidate\Qoo10ValidateService;
use App\Models\PlatformMarketOrder;
use App\Models\Client;
use App\Models\CountryState;
use App\Models\CourierCost;
use App\Models\ExchangeRate;
use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantProductMapping;
use App\Models\MerchantQuotation;
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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PlatformMarketOrderTransfer
{
    private $request;
    private $platformGroup = array(
            'Amazon' => 'AZ',
            'Lazada' => 'LZ',
            'PriceMinister' => 'PM',
            'Fnac' => 'FN',
            'Qoo10' => 'QO',
            'Newegg' => 'NE',
            'Tanga' => 'TG',
        );

    public function __construct()
    {
        $this->pricingService = new PricingService(new DeliveryQuotationRepository());
    }

    public function transferReadyOrder()
    {
        $orderList = PlatformMarketOrder::readyOrder()->get();
        $this->transferOrder($orderList);
    }

    public function transferOrderById($orderIds)
    {
        $orderList = PlatformMarketOrder::whereIn('id', $orderIds)->get();
        $this->transferOrder($orderList);
    }

    public function transferOrder($orderList)
    {
        if ($orderList) {
            foreach ($orderList as $order) {
                if ($this->getPlatformMarketValidateService($order)->validateOrder()) {
                    \DB::beginTransaction();
                    \DB::connection('mysql_esg')->beginTransaction();
                    try {
                        $this->createSplitOrder($order);
                        $soNo = $this->createGroupOrder($order);
                        $order->acknowledge = 1;
                        $order->so_no = $soNo;
                        $order->save();
                        \DB::connection('mysql_esg')->commit();
                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::connection('mysql_esg')->rollBack();
                        \DB::rollBack();
                        mail('jimmy.gao@eservicesgroup.com, handy.hon@eservicesgroup.com, brave.liu@eservicesgroup.com', $order->biz_type.' order import - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: "
                            .$e->getLine());
                    }
                }
            }
        }
    }

    public function getPlatformMarketValidateService($order)
    {
        switch ($order->biz_type) {
            case 'Amazon':
                $validateService = new AmazonValidateService($order);
                break;
            case 'Lazada':
                $validateService = new LazadaValidateService($order);
                break;
            case 'PriceMinister':
                $validateService = new PriceministerValidateService($order);
                break;
            case 'Fnac':
                $validateService = new FnacValidateService($order);
                break;
            case 'Newegg':
                $validateService = new NeweggValidateService($order);
                break;
            case 'Tanga':
                $validateService = new TangaValidateService($order);
                break;
            case 'Qoo10':
                $validateService =new Qoo10ValidateService($order);
                break;
            /* case 'Paytm':
                $validateService =new PaytmValidateService($order);
                break;*/
        }

        return $validateService;
    }

    /**
     * @param PlatformMarketOrder $order
     */
    public function createGroupOrder(PlatformMarketOrder $order)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $platformAccount = strtoupper(substr($order->platform, 0, 2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));

        $merchant = [];

        $_orderItem = $this->groupPlatformMarketOrderItem($order->platformMarketOrderItem()->get(), $marketplaceId, $countryCode);

        $so = $this->createOrder($order, $_orderItem);

        $countryCode = strtoupper(substr($order->platform, -2));
        $so->platform_id = 'AC-'.$platformAccount.$this->platformGroup[$order->biz_type].'-GROUP'.$countryCode;
        //need update
        $so->platform_split_order = 0;

        $splitOrder = So::where('platform_order_id', '=', $so->platform_order_id)
            ->where('platform_split_order', '=', '1')
            ->first();
        $so->incoterm = $splitOrder->incoterm;
        if ($order->fulfillment_channel === 'SBN') {
                $so->dispatch_date = date('Y-m-d H:i:s');
        }
        $so->save();

        $this->saveSoItem($so, $_orderItem);
        $this->saveSoItemDetail($so, $_orderItem);
        $this->saveSoPaymentStatus($so, $order);
        $this->saveSoExtend($so, $order);
        $this->saveSalesOrderStatistic($so, $_orderItem);
        $this->addAssemblyProduct($so);
        $this->addComplementaryAccessory($so);
        $this->setGroupOrderRecommendCourierAndCharge($so);
        return $so->so_no;
    }

    public function createSplitOrder(PlatformMarketOrder $order)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $platformAccount = strtoupper(substr($order->platform, 0, 2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));

        $merchant = [];
        $_orderItem = $this->groupPlatformMarketOrderItem($order->platformMarketOrderItem()->get(), $marketplaceId, $countryCode);
        foreach ($_orderItem as $item) {
            $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
                ->where('sku', '=', $item->mapping->sku)
                ->firstOrFail();
            // group items by merchant (short id).
            if (!array_key_exists($merchantProductMapping->short_id, $merchant)) {
                $merchant[$merchantProductMapping->short_id] = new Collection();
            }
            $item->seller_sku = $item->mapping->sku;
            //$item->mapping = $mapping;
            $merchant[$merchantProductMapping->short_id]->add($item);
        }

        foreach ($merchant as $merchantShortId => $items) {
            $so = $this->createOrder($order, $items);
            $countryCode = strtoupper(substr($order->platform, -2));
            $platformAccount = strtoupper(substr($order->platform, 0, 2));
            $so->platform_id = 'AC-'.$platformAccount.$this->platformGroup[$order->biz_type].'-'.$merchantShortId.$countryCode;
            $so->platform_group_order = 0;

            $spIncoterm = SpIncoterm::wherePlatformId($so->platform_id)->whereDeliveryTypeId($so->delivery_type_id)->first();
            if ($spIncoterm) {
                $so->incoterm = $spIncoterm->incoterm;
            }
            if ($order->fulfillment_channel === 'AFN') {
                //重新赋值delivery_type_id;
                $so->delivery_type_id = 'FBA';
                $so->dispatch_date = $order->latest_ship_date;
            } elseif ($order->fulfillment_channel === 'SBN') {
                $so->delivery_type_id = 'SBN';
                $so->esg_quotation_courier_id = '132';
                $so->dispatch_date = date('Y-m-d H:i:s');
            } elseif ($order->biz_type == "Lazada") {
                $so->delivery_type_id = 'EXP';
            } else {
                $marketplaceProduct = MarketplaceSkuMapping::whereIn('sku', $items->pluck('seller_sku'))
                    ->whereMpControlId($items->first()->mapping->mp_control_id)
                    ->whereIn('delivery_type', ['EXP', 'EXPED', 'STD'])
                    ->orderBy(\DB::raw('FIELD(delivery_type, "EXP", "EXPED", "STD")'))
                    ->first();
                if ($marketplaceProduct) {
                    $so->delivery_type_id = $marketplaceProduct->delivery_type;
                } else {
                    $so->delivery_type_id = 'STD';
                }
            }

            $so->save();
            $this->saveSoItem($so, $items);
            $this->saveSoItemDetail($so, $items);
            $this->saveSoPaymentStatus($so, $order);
            $this->saveSoExtend($so, $order);
            $this->addAssemblyProduct($so);
            $this->addComplementaryAccessory($so);
            $this->setSplitOrderRecommendCourierAndCharge($so);
        }
    }

    /***
     * @param PlatformMarketOrder $order
     * @return \App\Models\Client
     */
    private function createOrUpdateClient(PlatformMarketOrder $order)
    {
        $client = Client::firstOrNew(['email' => $order->buyer_email]);
        $client->password = bcrypt(Carbon::now());
        $client->forename = $order->buyer_name;
        $countryCode = $order->platformMarketShippingAddress->country_code;
        if (empty($countryCode)) {
            $countryCode = strtoupper(substr($order->platform, -2));
        }
        $client->country_id = $countryCode;
        $client->del_name = $order->platformMarketShippingAddress->name;
        $client->del_address_1 = $order->platformMarketShippingAddress->address_line_1;
        $client->del_address_2 = $order->platformMarketShippingAddress->address_line_2;
        $client->del_address_3 = $order->platformMarketShippingAddress->address_line_3;
        $client->del_postcode = $order->platformMarketShippingAddress->postal_code;
        $client->del_city = $order->platformMarketShippingAddress->city;
        $client->del_state = $order->platformMarketShippingAddress->state_or_region;
        //$client->del_country_id = $order->platformMarketShippingAddress->country_code;
        $client->del_country_id = $countryCode;
        $client->tel_3 = $order->platformMarketShippingAddress->phone;
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
     * @param PlatformMarketShippingAddressOrder $order
     * @param Collection                         $orderItems
     *
     * @return So
     */
    private function createOrder(PlatformMarketOrder $order, Collection $orderItems)
    {
        $newOrder = new So();
        $client = $this->createOrUpdateClient($order);
        $newOrder->so_no = $this->generateSoNumber();
        $newOrder->platform_order_id = $order->platform_order_no;
        $newOrder->platform_id = 'AC-BCAZ-GROUPUS'; // it should depends on group order or split order. temporary set it this.
        //need update platform_id
        $newOrder->txn_id = $order->platform_order_id;
        $newOrder->client_id = $client->id;
        $newOrder->biz_type = $order->biz_type;
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
        $newOrder->delivery_type_id = 'MCF';
        //this value will be update
        //need update delivery_type_id
        $newOrder->delivery_name = $order->platformMarketShippingAddress->name;
        $newOrder->delivery_address = implode(' | ', array_filter([
            $order->platformMarketShippingAddress->address_line_1,
            $order->platformMarketShippingAddress->address_line_2,
            $order->platformMarketShippingAddress->address_line_3,
        ]));
        $newOrder->delivery_postcode = $order->platformMarketShippingAddress->postal_code;
        $newOrder->delivery_city = $order->platformMarketShippingAddress->city;
        $newOrder->delivery_country_id = $order->platformMarketShippingAddress->country_code;
        $newOrder->delivery_state = CountryState::getStateId($order->platformMarketShippingAddress->country_code, $order->platformMarketShippingAddress->state_or_region);
        // if fulfillment by Platform market , marked as shipped (6), if fulfillment by ESG, marked as 3, no need credit check.
        //$newOrder->status = ($order->fulfillment_channel === 'AFN') ? '6' : '3';
        $newOrder->status = $this->getEsgOrderStatus($order);
        //need update status
        $newOrder->order_create_date = $order->purchase_date;
        $newOrder->del_tel_3 = $order->platformMarketShippingAddress->phone;

        if ($order->platformMarketShippingAddress->bill_name) {
            $newOrder->bill_name = $order->platformMarketShippingAddress->bill_name;
            $newOrder->bill_address = implode(' | ', array_filter([
                $order->platformMarketShippingAddress->bill_address_line_1,
                $order->platformMarketShippingAddress->bill_address_line_2,
                $order->platformMarketShippingAddress->bill_address_line_3,
            ]));
            $newOrder->bill_postcode = $order->platformMarketShippingAddress->bill_postal_code;
            $newOrder->bill_city = $order->platformMarketShippingAddress->bill_city;
            $newOrder->bill_country_id = $order->platformMarketShippingAddress->bill_country_code;
            $newOrder->bill_state = CountryState::getStateId($order->platformMarketShippingAddress->bill_country_code, $order->platformMarketShippingAddress->bill_state_or_region);
        } else {
            $newOrder->bill_country_id = $order->platformMarketShippingAddress->country_code;
        }
        $newOrder->statistic = 0;
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
            $newOrderItem->ext_seller_sku = $item->ext_seller_sku;
            $newOrderItem->qty = $item->quantity_ordered;
            $newOrderItem->unit_price = $item->item_price / $item->quantity_ordered;
            $newOrderItem->amount = $item->item_price;
            $newOrderItem->vat_total = 0;   // not sure.
            $newOrderItem->duty_total_percent = 0;
            $newOrderItem->full_duty_percent = 0;
            $newOrderItem->hidden_to_client = 0;
            $newOrderItem->status = 0;

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
            if ($costDetails = $marginAndProfit->get($item->mapping->delivery_type)) {
                $newOrderItemDetail->profit = $costDetails['profit'] * $item->quantity_ordered;
                $newOrderItemDetail->margin = $costDetails['margin'];
            }

            $newOrderItemDetail->amount = $item->item_price;
            $newOrderItemDetail->create_on = Carbon::now();
            $newOrderItemDetail->modify_on = Carbon::now();
            $newOrderItemDetail->save();
        }
    }

    /**
     * @param So $so
     */
    private function saveSoPaymentStatus(So $so, PlatformMarketOrder $order)
    {
        $soPaymentStatus = new SoPaymentStatus();
        $soPaymentStatus->so_no = $so->so_no;
        $soPaymentStatus->payment_gateway_id = $this->getPaymentGateway($so, strtolower($order->biz_type));
        $soPaymentStatus->payment_status = 'S';
        $soPaymentStatus->create_on = Carbon::now();
        $soPaymentStatus->modify_on = Carbon::now();
        $soPaymentStatus->save();
    }

    /**
     * @param So                  $so
     * @param PlatformMarketOrder $order
     */
    private function saveSoExtend(So $so, PlatformMarketOrder $order)
    {
        $soExtend = new SoExtend();
        $soExtend->so_no = $so->so_no;
        $soExtend->create_on = Carbon::now();
        $soExtend->modify_on = Carbon::now();

        $soExtend->save();
    }
    //need update getPaymentGateway
    private function getPaymentGateway(So $so, $bizType)
    {
        $countryCode = strtolower(substr($so->platform_id, -2));
        $countryCode = ($countryCode === 'gb') ? 'uk' : $countryCode;
        $platformAccount = strtolower(substr($so->platform_id, 3, 2));

        return $platformAccount.'_'.$bizType.'_'.$countryCode;
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

    public function groupPlatformMarketOrderItem($orderItems, $marketplaceId, $countryCode)
    {
        $gourpOrderItems = new Collection();
        foreach ($orderItems as $orderItem) {
            if (isset($gourpOrderItems[$orderItem->seller_sku])) {
                $gourpOrderItems[$orderItem->seller_sku]->quantity_ordered += $orderItem->quantity_ordered;
                $gourpOrderItems[$orderItem->seller_sku]->order_item_id .= '||'.$orderItem->order_item_id;
                $gourpOrderItems[$orderItem->seller_sku]->item_price += $orderItem->item_price;
            } else {
                $gourpOrderItems[$orderItem->seller_sku] = $orderItem;
            }
            $mapping = MarketplaceSkuMapping::where('marketplace_sku', '=', $orderItem->seller_sku)
                ->where('marketplace_id', '=', $marketplaceId)
                ->where('country_id', '=', $countryCode)
                ->firstOrFail();
            $gourpOrderItems[$orderItem->seller_sku]->mapping = $mapping;
            $gourpOrderItems[$orderItem->seller_sku]->ext_seller_sku = $mapping->marketplace_sku;
            $gourpOrderItems[$orderItem->seller_sku]->seller_sku = $mapping->sku;
        }
        return $gourpOrderItems;
    }

    private function getEsgOrderStatus($order)
    {
        $shippedFulfillment = array(
            "amazon" => array('AFN'),
            "newegg" => array('SBN')
        );
        $type = strtolower($order->biz_type);
        if (isset($shippedFulfillment[$type]) && in_array($order->fulfillment_channel, $shippedFulfillment[$type])) {
            $status = 6;
        } else {
            $status = 3;
        }
        return $status;
    }

    private function saveSalesOrderStatistic(So $so, Collection $orderItem)
    {
        $lineNumber = 1;
        foreach ($orderItem as $item) {
            $salesOrderStatistic = new SalesOrderStatistic();
            $salesOrderStatistic->so_no = $so->so_no;
            $salesOrderStatistic->line_no = $lineNumber++;

            $unit_price = $item->item_price / $item->quantity_ordered;
            $request = new ProfitEstimateRequest();
            $request->merge([
                'id' => $item->mapping->id,
                'selling_price' => $unit_price,
            ]);

            $marginAndProfit = $this->pricingService->availableShippingWithProfit($request);
            if ($costDetails = $marginAndProfit->get($item->mapping->delivery_type)) {
                $salesOrderStatistic->supplier_cost = $costDetails['supplierCost'] * $item->quantity_ordered;
                $salesOrderStatistic->accessory_cost = $costDetails['accessoryCost'] * $item->quantity_ordered;
                $salesOrderStatistic->marketplace_list_fee = $costDetails['marketplaceListingFee'] * $item->quantity_ordered;
                $salesOrderStatistic->marketplace_fixed_fee = $costDetails['marketplaceFixedFee'] * $item->quantity_ordered;
                $salesOrderStatistic->marketplace_commission = $costDetails['marketplaceCommission'] * $item->quantity_ordered;
                $salesOrderStatistic->marketplace_fee = $salesOrderStatistic->marketplace_list_fee
                    + $salesOrderStatistic->marketplace_fixed_fee
                    + $salesOrderStatistic->marketplace_commission;
                $salesOrderStatistic->vat = $costDetails['tax'] * $item->quantity_ordered;
                $salesOrderStatistic->duty = $costDetails['duty'] * $item->quantity_ordered;
                $salesOrderStatistic->payment_gateway_fee = $costDetails['paymentGatewayFee'] * $item->quantity_ordered;
                $salesOrderStatistic->psp_admin_fee = $costDetails['paymentGatewayAdminFee'] * $item->quantity_ordered;
                $salesOrderStatistic->shipping_cost = $costDetails['deliveryCost'] * $item->quantity_ordered;
                $salesOrderStatistic->warehouse_cost = $costDetails['warehouseCost'] * $item->quantity_ordered;
                $salesOrderStatistic->fulfilment_by_marketplace_fee = $costDetails['fulfilmentByMarketplaceFee'] * $item->quantity_ordered;
                $salesOrderStatistic->tuv_fee = $costDetails['tuvFee'] * $item->quantity_ordered;
                $salesOrderStatistic->to_usd_rate = $so->rate;
            }
            $salesOrderStatistic->save();
        }
    }
}
