<?php

namespace App\Console\Commands;

use App\Models\AmazonOrder;
use App\Models\AmazonOrderItem;
use App\Models\Client;
use App\Models\ExchangeRate;
use App\Models\PlatformBizVar;
use App\Models\PlatformOrderDeliveryType;
use App\Models\Sequence;
use App\Models\So;
use App\Models\SoExtend;
use App\Models\SoItem;
use App\Models\SoItemDetail;
use App\Models\SoPaymentStatus;
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
            \DB::transaction(function() use ($amazonOrder) {
                //$this->createSplitOrder($amazonOrder);
                $this->createGroupOrder($amazonOrder);
                $amazonOrder->acknowledge = 1;
                $amazonOrder->save();
            });
        }
    }

    /**
     * @param AmazonOrder $order
     */
    public function createGroupOrder(AmazonOrder $order)
    {
        $so = $this->createOrder($order);
        $this->saveSoItem($so, $order->amazonOrderItem);
        $this->saveSoItemDetail($so, $order->amazonOrderItem);
        $this->saveSoPaymentStatus($so);
        $this->saveSoExtend($so, $order);
    }

    /**
     * @param AmazonOrder $order
     */
    public function createSplitOrder(AmazonOrder $order)
    {

    }

    /***
     * @param AmazonOrder $order
     * @return \App\Models\Client
     */
    private function createOrUpdateClient(AmazonOrder $order)
    {
        $client = Client::where('email', '=', $order->buyer_email)->first();

        if (!$client) {
            $client = new Client();
        }

        $client->email = $order->buyer_email;
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
     * @param AmazonOrder $order
     * @return boolean
     */
    private function createLocalOrder(AmazonOrder $order)
    {
        $client = $this->createOrUpdateClient($order);
        $skuList = $order->amazonOrderItem->pluck('seller_sku')->toArray();
        $merchantList = \DB::connection('mysql_esg')->table('merchant_product_mapping')
            ->join('merchant', 'merchant_id', '=', 'id')
            ->select('short_id')
            ->whereIn('sku', $skuList)
            ->groupBy('id')
            ->get();

        // all items belong to same merchant.
        if (count($merchantList) === 1) {
            $so = $this->createLocalOrder($order);

            $soItem = [];
            $soItemDetail = [];
            foreach ($order->amazonOrderItem as $orderItem) {
                $soItem[] = $this->saveSoItem($orderItem);
                $soItemDetail[] = $this->saveSoItemDetail($orderItem);
            }

        }


        try {
            // if amazon order contains multi items, split it.
            for ($i = 0, $totalItem = count($order->amazonOrderItem); $i < $totalItem; $i++) {

                $soNumber = $this->generateSoNumber();

                $localSKU = $order->amazonOrderItem[$i]->seller_sku;

                $merchant = \DB::connection('mysql_esg')->table('merchant_product_mapping')
                    ->join('merchant', 'merchant_id', '=', 'id')
                    ->select('short_id')
                    ->where('sku', '=', $localSKU)
                    ->first();

                if (!$merchant) {
                    throw new \Exception("Amazon order id: {$order->amazon_order_id}. {$localSKU} can't find a merchant", 1);
                }

                $sellingPlatformId = 'AC-BCAZ-'.$merchant->short_id.substr($order->platform, -2);
                $platformBizVar = PlatformBizVar::where('selling_platform_id', '=', $sellingPlatformId)->first();

                if (!$platformBizVar) {
                    throw new \Exception("Amazon order id: {$order->amazon_order_id}. {$sellingPlatformId} isn't exists", 1);
                }

                $exchangeRate = ExchangeRate::where('from_currency_id', '=', $order->currency)
                                                ->where('to_currency_id', '=', 'USD')
                                                ->first();

                $countryCode = strtoupper($order->amazonShippingAddress->country_code);
                $countryCode = ($countryCode === 'GB') ? 'UK' : $countryCode;
                $platformOrderDeliveryType = PlatformOrderDeliveryType::where('sku', '=', $localSKU)
                                                                        ->where('platform_type', '=', 'BCAMAZON')
                                                                        ->where('country_id', '=', $countryCode)
                                                                        ->first();

                if (!$platformOrderDeliveryType) {
                    throw new \Exception("Amazon order id: {$order->amazon_order_id}. SKU: {$localSKU}. Can't decide which delivery type.", 1);
                }

                $so = $this->createLocalOrder($order);
                $so->so_no = $soNumber;
                $so->platform_id = $platformBizVar->selling_platform_id;
                $so->platform_order_id = $order->amazon_order_id.'-'.$order->amazonOrderItem[$i]->order_item_id;

                $so->txn_id = $so->platform_order_id;
                $so->client_id = $client->id;
                $so->amount = $order->amazonOrderItem[$i]->item_price;
                $so->cost = $so->amount;    // temporary as amount, should get data from price table.
                $so->vat_percent = $platformBizVar->vat_percent;
                $so->vat = 0;    // not sure.
                $so->rate = $exchangeRate->rate;
                $so->delivery_type_id = $platformOrderDeliveryType->delivery_type_id;

                //dd($so);
                $soItem = $this->saveSoItem($order->amazonOrderItem[$i]);
                $soItem->so_no = $soNumber;

                // TODO:
                // need check CA product for FBM. maybe need this order have multi items.

                $soItemDetail = $this->saveSoItemDetail($order->amazonOrderItem[$i]);
                $soItemDetail->so_no = $soNumber;

                // TODO:
                // also need consider CA product. maybe need add item to so_item_detail table.

                $soPaymentStatus = $this->saveSoPaymentStatus($order);
                $soPaymentStatus->so_no = $soNumber;

                $soExtend = $this->saveSoExtend($order);
                $soExtend->so_no = $soNumber;
                $so->save();
                $soItem->save();
                $soItemDetail->save();
                $soPaymentStatus->save();
                $soExtend->save();
                //\DB::transaction(function($so, $soItem, $soItemDetail, $soPaymentStatus, $soExtend) {
                //
                //});
            }

        } catch (\Exception $e) {
            mail('handy.hon@eservicesgroup.com', '[BrandsConnect] Amazon Order Import failed', $e->getMessage());
            return false;
        }

        return true;
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
     * @return So
     */
    private function createOrder(AmazonOrder $order)
    {
        $newOrder = new So;

        $soNumber = $this->generateSoNumber();
        $client = $this->createOrUpdateClient($order);

        $newOrder->so_no = $soNumber;
        $newOrder->platform_order_id = $order->amazon_order_id;
        $newOrder->platform_id = 'AC-GROUP';  // TODO:: should add in selling_platform and platform_biz_var table.
        $newOrder->txn_id = $order->amazon_order_id;
        $newOrder->client_id = $client->id;
        $newOrder->biz_type = $order->fulfillment_channel;
        $newOrder->weight = 1;  // TODO: need calculate base esg sku.
        $newOrder->amount = $order->amazonOrderItem->pluck('item_price')->sum();
        $newOrder->cost = $newOrder->amount;   // temporary as amount, should get data from price table.
        $newOrder->currency_id = $order->currency;
        $newOrder->rate = ExchangeRate::where('from_currency_id', '=', $order->currency)
            ->where('to_currency_id', '=', 'USD')
            ->first()
            ->rate;
        $newOrder->vat_percent = 0;     // not sure.
        $newOrder->vat = 0;             // not sure.
        $newOrder->delivery_address = $order->amazonOrderItem->pluck('shipping_price')->sum();
        $newOrder->delivery_type_id = 'STD';// TODO: should base split order, have a rule in Doc, do it later as settle.
        $newOrder->delivery_name = $order->amazonShippingAddress->name;
        $newOrder->delivery_address = implode(' | ', array_filter([
            $order->amazonShippingAddress->address_line_1,
            $order->amazonShippingAddress->address_line_2,
            $order->amazonShippingAddress->address_line_3
        ]));
        $newOrder->delivery_postcode = $order->amazonShippingAddress->postal_code;
        $newOrder->delivery_city = $order->amazonShippingAddress->city;
        $newOrder->delivery_state = $order->amazonShippingAddress->state_or_region;
        $newOrder->delivery_country_id = $order->amazonShippingAddress->country_code;
        // if fulfillment by amazon, marked as shipped (6), if fulfillment by ESG, marked as 3, no need credit check.
        $newOrder->status = ($order->fulfillment_channel === 'AFN') ? '6' : '3';
        $newOrder->order_create_date = $order->purchase_date;
        $newOrder->del_tel_3 = $order->amazonShippingAddress->phone;
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
        $line_no = 1;
        foreach ($orderItem as $item) {
            $newOrderItem = new SoItem;

            $newOrderItem->so_no = $so->so_no;
            $newOrderItem->line_no = $line_no++;
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
        $line_no = 1;
        foreach ($orderItem as $item) {
            $newOrderItemDetail = new SoItemDetail;

            $newOrderItemDetail->so_no = $so->so_no;
            $newOrderItemDetail->item_sku = $item->seller_sku;
            $newOrderItemDetail->line_no = $line_no++;
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

        if ($so->platform_id === 'AC-GROUP') {
            $soPaymentStatus->payment_gateway_id = 'amazon';
        } else {
            $countryCode = strtolower(substr($so->platform_id, -2));
            $countryCode = ($countryCode === 'gb') ? 'uk' : $countryCode;
            $soPaymentStatus->payment_gateway_id = 'bc_amazon_'.$countryCode;
        }

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
}
