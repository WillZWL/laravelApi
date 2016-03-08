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
            $isCreated = $this->createLocalOrder($amazonOrder);

            if ($isCreated) {
                $amazonOrder->acknowledge = 1;
                $amazonOrder->save();
            }
        }
    }

    /***
     * @param AmazonOrder $order
     * @return \App\Models\Client
     */
    private function createLocalClient(AmazonOrder $order)
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
        $client = $this->createLocalClient($order);

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

                $sellingPlatfromId = 'AC-BCAZ-'.$merchant->short_id.substr($order->platform, -2);
                $platformBizVar = PlatformBizVar::where('selling_platform_id', '=', $sellingPlatfromId)->first();

                if (!$platformBizVar) {
                    throw new \Exception("Amazon order id: {$order->amazon_order_id}. {$sellingPlatfromId} isn't exists", 1);
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

                $so = $this->createSo($order);
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
                $soItem = $this->createSoItem($order->amazonOrderItem[$i]);
                $soItem->so_no = $soNumber;

                // TODO:
                // need check CA product for FBM. maybe need this order have multi items.

                $soItemDetail = $this->createSoItemDetail($order->amazonOrderItem[$i]);
                $soItemDetail->so_no = $soNumber;

                // TODO:
                // also need consider CA product. maybe need add item to so_item_detail table.

                $soPaymentStatus = $this->createSoPaymentStatus($order);
                $soPaymentStatus->so_no = $soNumber;

                $soExtend = $this->createSoExtend($order);
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
            mail('amazon_us@brandsconnect.net, handy.hon@eservicesgroup.com', '[BrandConnect] Amazon Order Import failed', $e->getMessage());
            return false;
        }

        return true;
    }

    /***
     * @return int local order number.
     */
    private function generateSoNumber()
    {
        $sequence = Sequence::where('seq_name', '=', 'customer_order')->first();
        $sequence->value += 1;
        $sequence->save();

        return $sequence->value;
        //\DB::transaction(function() {
        //});
    }

    private function createSo(AmazonOrder $order)
    {
        $newOrder = new So;

        //$newOrder->so_no = $order->local_so_no;
        //$newOrder->platform_order_id = $order->local_platform_order_id;
        //$newOrder->platform = $order->local_platform;
        //$newOrder->txn_id = $order->local_platform_order_id;
        //$newOrder->client_id = $order->local_client_id;
        $newOrder->biz_type = ($order->fulfillment_channel === 'AFN') ? 'FBA' : 'AMAZON';
        $newOrder->weight = 1;  // can't get this data from amazon. hard code it.
        $newOrder->currency_id = $order->currency;
        $newOrder->delivery_name = $order->amazonShippingAddress->name;
        $newOrder->delivery_address = $order->amazonShippingAddress->address_line_1
                                            . (($order->amazonShippingAddress->address_line_2) ? (' | ') : '' . $order->amazonShippingAddress->address_line_2)
                                            . (($order->amazonShippingAddress->address_line_3) ? (' | ') : '' . $order->amazonShippingAddress->address_line_3);
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

        return $newOrder;
    }

    private function createSoItem(AmazonOrderItem $orderItem)
    {
        $newOrderItem = new SoItem;

        $newOrderItem->prod_sku = $orderItem->seller_sku;
        $newOrderItem->line_no = 1;     // TODO: temporary set 1. need consider multi items case.
        $newOrderItem->prod_name = $orderItem->title;
        $newOrderItem->ext_item_cd = $orderItem->order_item_id;
        $newOrderItem->qty = $orderItem->quantity_ordered;
        $newOrderItem->unit_price = $orderItem->item_price;
        $newOrderItem->vat_total = 0;   // not sure.
        $newOrderItem->amount = $orderItem->item_price;
        $newOrderItem->create_on = Carbon::now();
        $newOrderItem->modify_on = Carbon::now();

        return $newOrderItem;
    }

    private function createSoItemDetail(AmazonOrderItem $orderItem)
    {
        $newOrderItemDetail = new SoItemDetail;

        $newOrderItemDetail->item_sku = $orderItem->seller_sku;
        $newOrderItemDetail->line_no = 1;     // TODO: temporary set 1. need consider multi items case.
        $newOrderItemDetail->qty = $orderItem->quantity_ordered;
        $newOrderItemDetail->outstanding_qty = $orderItem->quantity_ordered;
        $newOrderItemDetail->unit_price = $orderItem->item_price;
        $newOrderItemDetail->vat_total = 0;   // not sure.
        $newOrderItemDetail->amount = $orderItem->item_price;
        $newOrderItemDetail->create_on = Carbon::now();
        $newOrderItemDetail->modify_on = Carbon::now();

        return $newOrderItemDetail;
    }

    private function createSoPaymentStatus(AmazonOrder $order)
    {
        $soPaymentStatus = new SoPaymentStatus;

        $countryCode = strtolower(substr($order->platform, -2));
        $countryCode = ($countryCode === 'gb') ? 'uk' : $countryCode;
        $soPaymentStatus->payment_gateway_id = 'bc_amazon_'.$countryCode;
        $soPaymentStatus->payment_status = 'S';
        $soPaymentStatus->create_on = Carbon::now();
        $soPaymentStatus->modify_on = Carbon::now();

        return $soPaymentStatus;
    }

    private function createSoExtend(AmazonOrder $order)
    {
        $soExtend = new SoExtend;
        $soExtend->create_on = Carbon::now();
        $soExtend->modify_on = Carbon::now();

        return $soExtend;
    }
}
