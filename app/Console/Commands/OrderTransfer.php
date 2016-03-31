<?php

namespace App\Console\Commands;

use App\Models\AmazonOrder;
use App\Models\AmazonOrderItem;
use App\Models\Client;
use App\Models\CountryState;
use App\Models\CourierCost;
use App\Models\ExchangeRate;
use App\Models\MerchantProductMapping;
use App\Models\MerchantQuotation;
use App\Models\PlatformBizVar;
use App\Models\PlatformOrderDeliveryScore;
use App\Models\Product;
use App\Models\ProductAssemblyMapping;
use App\Models\ProductComplementaryAcc;
use App\Models\Quotaton;
use App\Models\Sequence;
use App\Models\So;
use App\Models\SoExtend;
use App\Models\SoItem;
use App\Models\SoItemDetail;
use App\Models\SoPaymentStatus;
use App\Models\WeightCourier;
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
                    mail('handy.hon@eservicesgroup.com', '[BrandsConnect] - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                }
            }
        }
    }

    /**
     * @param AmazonOrder $order
     * @return bool
     */
    public function validateAmazonOrder(AmazonOrder $order)
    {
        $skuList = $order->amazonOrderItem->pluck('seller_sku')->toArray();

        // check sku is belong to which merchant.
        $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
            ->whereIn('sku', $skuList)
            ->get();
        $merchantSku = $merchantProductMapping->pluck('sku')->toArray();
        $notMatchSku = array_diff($skuList, $merchantSku);
        if ($notMatchSku) {
            // TODO: need rewrite this part, should move it to mail queue.
            foreach ($notMatchSku as $sku) {
                $subject = '[BrandsConnect] Amazon Order Import Failed!';
                $message = "MarketPlace: {$order->sales_channel}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
                $message .= "SKU <{$sku}> not match between amazon and esg, please note it. Thanks";
                mail('amazon_us@brandsconnect.net, handy.hon@eservicesgroup.com', $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
            }
            return false;
        }

        // check selling platform is exist or not.
        $countryCode = strtoupper(substr($order->platform, -2));
        $sellingPlatformId = $merchantProductMapping->pluck('short_id')->map(function($item) use ($countryCode) {
            return 'AC-BCAZ-'.$item.$countryCode;
        })->toArray();
        $platformIdFromDB = PlatformBizVar::whereIn('selling_platform_id', $sellingPlatformId)
            ->get()
            ->pluck('selling_platform_id')
            ->toArray();
        $notExistPlatform = array_diff($sellingPlatformId, $platformIdFromDB);
        if ($notExistPlatform) {
            foreach ($notExistPlatform as $platformId) {
                $subject = '[BrandsConnect] Amazon Order Import Failed!';
                $message = "MarketPlace: {$order->sales_channel}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
                $message .= "Selling Platform Id <{$platformId}> not exist in esg system, please add it. Thanks";
                mail('amazon_us@brandsconnect.net, handy.hon@eservicesgroup.com', $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
            }
            return false;
        }

        // check sku delivery type.
        $countryCode = ($countryCode === 'GB') ? 'UK' : $countryCode;
        $skuHaveDeliveryScore = PlatformOrderDeliveryScore::whereIn('sku', $skuList)
            ->where('platform_type', '=', 'BCAMAZON')
            ->where('country_id', '=', $countryCode)
            ->get()
            ->pluck('sku')
            ->toArray();
        $notHaveDeliveryType = array_diff($skuList, $skuHaveDeliveryScore);
        if ($notHaveDeliveryType) {
            foreach ($notHaveDeliveryType as $sku) {
                $subject = '[BrandsConnect] Amazon Order Import Failed!';
                $message = "MarketPlace: {$order->sales_channel}.\r\n Amazon Order Id: {$order->amazon_order_id}\r\n";
                $message .= "SKU <{$sku}> not set delivery type to {$countryCode} in esg system, please add it. Thanks";
                mail('amazon_us@brandsconnect.net, handy.hon@eservicesgroup.com', $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
            }
            return false;
        }

        return true;
    }

    /**
     * @param AmazonOrder $order
     */
    public function createGroupOrder(AmazonOrder $order)
    {
        $so = $this->createOrder($order, $order->amazonOrderItem);

        $countryCode = strtoupper(substr($order->platform, -2));
        $so->platform_id = 'AC-BCAZ-GROUP'.$countryCode;
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
        $merchant = [];
        foreach ($order->amazonOrderItem as $item) {
            $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
                ->where('sku', '=', $item->seller_sku)
                ->firstOrFail();

            // group items by merchant (short id).
            if (!array_key_exists($merchantProductMapping->short_id, $merchant)) {
                $merchant[$merchantProductMapping->short_id] = new Collection();
            }
            $merchant[$merchantProductMapping->short_id]->add($item);
        }

        foreach ($merchant as $merchantShortId => $items) {
            $so = $this->createOrder($order, $items);

            $countryCode = strtoupper(substr($order->platform, -2));
            //$countryCode = ($countryCode === 'UK') ? 'GB' : $countryCode;
            $so->platform_id = 'AC-BCAZ-'.$merchantShortId.$countryCode;
            $so->is_platform_split_order = 1;
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
        // TODO: need add transaction.
        $sequence = Sequence::where('seq_name', '=', 'customer_order')->first();
        $sequence->value += 1;
        $sequence->save();

        return $sequence->value;
    }

    /**
     * @param AmazonOrder $order
     * @param Collection $orderItems
     * @return So
     */
    private function createOrder(AmazonOrder $order, Collection $orderItems)
    {
        $newOrder = new So;
        $client = $this->createOrUpdateClient($order);
        $newOrder->so_no = $this->generateSoNumber();
        $newOrder->platform_order_id = $order->amazon_order_id;
        $newOrder->is_platform_split_order = 0;
        $newOrder->platform_id = 'AC-BCAZ-GROUPUS'; // it should depends on group order or split order. temporary set this.
        $newOrder->txn_id = $order->amazon_order_id;
        $newOrder->client_id = $client->id;
        $newOrder->biz_type = 'AMAZON';
        $newOrder->weight = $this->calculateOrderWeight($orderItems);
        $newOrder->amount = $orderItems->pluck('item_price')->sum();
        $newOrder->cost = $newOrder->amount;   // temporary as amount, should get data from price table.
        $newOrder->currency_id = $order->currency;
        $newOrder->rate = ExchangeRate::where('from_currency_id', '=', $order->currency)
            ->where('to_currency_id', '=', 'USD')
            ->first()
            ->rate;
        $newOrder->vat_percent = 0;     // not sure.
        $newOrder->vat = 0;             // not sure.
        $newOrder->delivery_address = $orderItems->pluck('shipping_price')->sum();

        if ($order->fulfillment_channel === 'AFN') {
            $newOrder->delivery_type_id = 'FBA';
        } else {
            $countryCode = strtoupper($order->amazonShippingAddress->country_code);
            $countryCode = ($countryCode === 'GB') ? 'UK' : $countryCode;
            $newOrder->delivery_type_id = PlatformOrderDeliveryScore::whereIn('sku', $orderItems->pluck('seller_sku'))
                ->where('country_id', '=', $countryCode)
                ->orderBy('score', 'desc')
                ->first()
                ->delivery_type_id;
        }

        $newOrder->delivery_name = $order->amazonShippingAddress->name;
        $newOrder->delivery_address = implode(' | ', array_filter([
            $order->amazonShippingAddress->address_line_1,
            $order->amazonShippingAddress->address_line_2,
            $order->amazonShippingAddress->address_line_3
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
     * @param So $so
     * @param Collection $orderItem
     */
    private function saveSoItem(So $so, Collection $orderItem)
    {
        $lineNumber = 1;
        foreach ($orderItem as $item) {
            $newOrderItem = new SoItem;
            $newOrderItem->so_no = $so->so_no;
            $newOrderItem->line_no = $lineNumber++;
            $newOrderItem->prod_sku = $item->seller_sku;
            $newOrderItem->prod_name = $item->title;
            $newOrderItem->ext_item_cd = $item->order_item_id;
            $newOrderItem->qty = $item->quantity_ordered;
            $newOrderItem->unit_price = $item->item_price;
            $newOrderItem->vat_total = 0;   // not sure.
            $newOrderItem->amount = $item->item_price;
            $newOrderItem->create_on = Carbon::now();
            $newOrderItem->modify_on = Carbon::now();
            $newOrderItem->save();
        }
    }

    /**
     * @param So $so
     * @param Collection $orderItem
     */
    private function saveSoItemDetail(So $so, Collection $orderItem)
    {
        $lineNumber = 1;
        foreach ($orderItem as $item) {
            $newOrderItemDetail = new SoItemDetail;
            $newOrderItemDetail->so_no = $so->so_no;
            $newOrderItemDetail->item_sku = $item->seller_sku;
            $newOrderItemDetail->line_no = $lineNumber++;
            $newOrderItemDetail->qty = $item->quantity_ordered;
            $newOrderItemDetail->outstanding_qty = $item->quantity_ordered;
            $newOrderItemDetail->unit_price = $item->item_price;
            $newOrderItemDetail->vat_total = 0;   // not sure.
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
        $soPaymentStatus = new SoPaymentStatus;
        $soPaymentStatus->so_no = $so->so_no;
        $soPaymentStatus->payment_gateway_id = $this->getPaymentGateway($so);
        $soPaymentStatus->payment_status = 'S';
        $soPaymentStatus->create_on = Carbon::now();
        $soPaymentStatus->modify_on = Carbon::now();
        $soPaymentStatus->save();
    }

    /**
     * @param So $so
     * @param AmazonOrder $order
     */
    private function saveSoExtend(So $so, AmazonOrder $order)
    {
        $soExtend = new SoExtend;
        $soExtend->so_no = $so->so_no;
        $soExtend->create_on = Carbon::now();
        $soExtend->modify_on = Carbon::now();

        $soExtend->save();
    }

    private function getPaymentGateway(So $so)
    {
        $countryCode = strtolower(substr($so->platform_id, -2));
        $countryCode = ($countryCode === 'gb') ? 'uk' : $countryCode;

        return 'bc_amazon_'.$countryCode;
    }

    /**
     * Not contain assembly product
     * @param Collection $orderItems
     * @return mixed
     */
    private function calculateOrderWeight(Collection $orderItems)
    {
        $skuQty = $orderItems->pluck('quantity_ordered', 'seller_sku')->toArray();
        $skuWeight = Product::whereIn('sku', array_keys($skuQty))->get()->pluck('weight', 'sku');

        $totalWeight = $skuWeight->map(function($weight, $sku) use ($skuQty) {
            return $skuQty[$sku] * $weight;
        })->sum();

        return $totalWeight;
    }

    private function setSplitOrderRecommendCourierAndCharge(So $order)
    {
        $merchantShortId = substr(last(explode('-', $order->platform_id)), 0, -2);
        $availableQuotation = $this->getAvailableMerchantQuotation($merchantShortId);

        if ( ! $availableQuotation->isEmpty()) {
            switch ($order->delivery_type_id) {
                case 'FBA':
                    $quotationType = ['acc_fbmp'];
                    break;

                case 'STD':
                    // see sbf 8255.
                    if ( ! $order->hasExternalBattery() && $order->hasInternalBattery())  {
                        $quotationType = ['acc_builtin_postage'];
                    } else {
                        $quotationType = ['acc_builtin_postage', 'acc_external_postage'];
                    }
                    break;

                case 'EXPED':
                    $quotationType = ['acc_courier'];
                    break;

                case 'EXP':
                    $quotationType = ['acc_courier_exp'];
                    break;

                case 'MCF':
                    $quotationType = ['acc_mcf'];
                    break;

                default :
                    $quotationType = [];
                    break;
            }

            $quotationVersionIds = $availableQuotation->whereIn('quotation_type', $quotationType)->pluck('id')->toArray();
            if (empty($quotationVersionIds)) {
                return false;
            }
            $weightId = WeightCourier::getWeightId($order->weight);
            if ($weightId === null) {
                // TODO
                // overweight case.
                $quotation = '';
            } else {
                if ($order->hasExternalBattery() || $order->hasInternalBattery()) {
                    $sort = 'DESC';             // use more expensive quotation of STD.
                } else {
                    $sort = 'ASC';              // use cheaper quotation of STD.
                }
                $quotation = Quotaton::whereIn('quotn_version_id', $quotationVersionIds)
                    ->where('dest_country_id', '=', $order->delivery_country_id)
                    ->where('dest_state_id', '=', $order->delivery_state)
                    ->where('weight_id', '=', $weightId)
                    ->orderBy('quotation', $sort)
                    ->first();
            }

            if (empty($quotation)) {
                return false;
            }

            $currencyRate = ExchangeRate::getRate('HKD', $order->currency_id);
            $order->esg_quotation_courier_id = $quotation->courier_id;
            $order->esg_delivery_cost = $quotation->cost * $currencyRate;
            $order->esg_delivery_offer = $quotation->cost * $currencyRate;
            $order->delivery_charge = $quotation->quotation * $currencyRate * 1.0125;   // 1.0125 is exchange rate mark up.
            $order->save();
        }
    }

    private function setGroupOrderRecommendCourierAndCharge(So $order)
    {
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_id)
            ->where('is_platform_split_order', '=', '1')
            ->get();

        $weightId = WeightCourier::getWeightId($order->weight);

        if ($splitOrders[0]->courierInfo) {
            $courierCost = $this->getCourierCost($order->delivery_country_id, $order->delivery_state, $weightId, $splitOrders[0]->courierInfo->courier_id);

            if ($courierCost) {
                $currencyRate = ExchangeRate::getRate('HKD', $order->currency_id);
                $deliveryChargeInHKD = $courierCost->delivery_cost * (100 + $splitOrders[0]->courierInfo->surcharge) / 100;

                $order->esg_delivery_cost = $courierCost->delivery_cost * $currencyRate;
                $order->esg_delivery_offer = $deliveryChargeInHKD * $currencyRate;
                $order->delivery_charge = 1.0125 * $order->esg_delivery_offer;
                $order->esg_quotation_courier_id = $splitOrders[0]->courierInfo->courier_id;
                return $order->save();
            }
        }

        return false;
    }

    /**
     * @param $merchantShortId
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

        if ( ! $complementaryAccessory->isEmpty()) {
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
                $soItemDetail->outstanding_qty = $skuInOrder[$item->mainprod_sku];;
                $soItemDetail->unit_price = 0;
                $soItemDetail->vat_total = 0;
                $soItemDetail->amount = 0;
                $soItemDetail->create_on = Carbon::now();
                $soItemDetail->modify_on = Carbon::now();
                $order->soItemDetail()->save($soItemDetail);

                $lineNumber++;
            }
        }
    }

    private function addAssemblyProduct(So $order)
    {
        $skuInOrder = $order->soItem->pluck('qty', 'prod_sku')->toArray();
        $assemblyMapping = ProductAssemblyMapping::active()->whereIn('main_sku', array_keys($skuInOrder))
            ->where('is_replace_main_sku', '=', '0')
            ->get();

        if ( ! $assemblyMapping->isEmpty()) {
            $order->load('soItem');
            $lineNumber = $order->soItem->max('line_no') + 1;
            foreach ($assemblyMapping as $item) {
                $soItem = new SoItem();
                $soItem->so_no = $order->so_no;
                $soItem->line_no = $lineNumber;
                $soItem->prod_sku = $item->sku;
                $soItem->prod_name = $item->product->name;
                $soItem->qty = $soItem->qty + $skuInOrder[$item->main_sku] * $item->replace_qty;
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
                $soItemDetail->qty = $soItemDetail->qty + $skuInOrder[$item->main_sku] * $item->replace_qty;
                $soItemDetail->outstanding_qty = $soItemDetail->qty;
                $soItemDetail->unit_price = 0;
                $soItemDetail->vat_total = 0;
                $soItemDetail->amount = 0;
                $soItemDetail->create_on = Carbon::now();
                $soItemDetail->modify_on = Carbon::now();
                $order->soItemDetail()->save($soItemDetail);

                $lineNumber++;
            }
        }
    }
}
