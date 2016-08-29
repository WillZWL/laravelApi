<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\AmazonOrder;
use App\Models\AmazonOrderItem;
use App\Models\AmazonShippingAddress;
use Config;
use Peron\AmazonMws\AmazonOrderItemList;
use Peron\AmazonMws\AmazonOrderList;

class OrderRetrieve extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:retrieve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve orders from amazon';

    /**
     * Create a new command instance.
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
        $stores = Config::get('amazon-mws.store');

        foreach ($stores as $storeName => $store) {
            $previousSchedule = Schedule::where('store_name', '=', $storeName)
                                        ->where('status', '=', 'C')
                                        ->orderBy('last_access_time', 'desc')
                                        ->first();

            $currentSchedule = Schedule::create([
                    'store_name' => $storeName,
                    'status' => 'N',
                    // MWS API requested: Must be no later than two minutes before the time that the request was submitted.
                    'last_access_time' => Carbon::now()->subMinutes(2),
                ]);

            if (!$previousSchedule) {
                $previousSchedule = $currentSchedule;
            }

            $amazonOrderList = new AmazonOrderList($storeName);

            $result = $this->retrieveOrder($storeName, $amazonOrderList, $previousSchedule);
            if ($result) {
                $currentSchedule->status = 'C';
            } else {
                $currentSchedule->status = 'F';
                $currentSchedule->remark = json_encode($amazonOrderList->getLastResponse());
            }
            $currentSchedule->save();
        }
    }

    public function retrieveOrder($storeName, AmazonOrderList $amazonOrderList, Schedule $schedule)
    {
        $amazonOrderList->setLimits('Modified', $schedule->last_access_time);
        $amazonOrderList->setUseToken();
        $fetchResult = $amazonOrderList->fetchOrders();
        if ($fetchResult === false) {
            return false;
        }
        $originOrders = $amazonOrderList->getList();
        $amazonOrderItemList = new AmazonOrderItemList($storeName);

        foreach ($originOrders as $originOrder) {
            $orderData = $originOrder->getData();
            $amazonOrderItemList->setOrderId($orderData['AmazonOrderId']);
            $amazonOrderItemList->setUseToken();
            $amazonOrderItemList->fetchItems();
            $originOrderItemList = $amazonOrderItemList->getItems();

            $amazonShippingAddressRecord = [
                'amazon_order_id' => $orderData['AmazonOrderId'],
            ];
            if (isset($orderData['ShippingAddress'])) {
                $amazonShippingAddressRecord['name'] = $orderData['ShippingAddress']['Name'];
                $amazonShippingAddressRecord['address_line_1'] = $orderData['ShippingAddress']['AddressLine1'];
                $amazonShippingAddressRecord['address_line_2'] = $orderData['ShippingAddress']['AddressLine2'];
                $amazonShippingAddressRecord['address_line_3'] = $orderData['ShippingAddress']['AddressLine3'];
                $amazonShippingAddressRecord['city'] = $orderData['ShippingAddress']['City'];
                $amazonShippingAddressRecord['county'] = $orderData['ShippingAddress']['County'];
                $amazonShippingAddressRecord['district'] = $orderData['ShippingAddress']['District'];
                $amazonShippingAddressRecord['state_or_region'] = $orderData['ShippingAddress']['StateOrRegion'];
                $amazonShippingAddressRecord['postal_code'] = $orderData['ShippingAddress']['PostalCode'];
                $amazonShippingAddressRecord['country_code'] = $orderData['ShippingAddress']['CountryCode'];
                $amazonShippingAddressRecord['phone'] = $orderData['ShippingAddress']['Phone'];
            }

            $amazonShippingAddress = AmazonShippingAddress::updateOrCreate(
                ['amazon_order_id' => $orderData['AmazonOrderId']],
                $amazonShippingAddressRecord
            );

            $amazonOrderRecord = [
                'platform' => $schedule->store_name,
                'amazon_order_id' => $orderData['AmazonOrderId'],
                'purchase_date' => $orderData['PurchaseDate'],
                'last_update_date' => $orderData['LastUpdateDate'],
                'order_status' => $orderData['OrderStatus'],
                'shipping_address_id' => $amazonShippingAddress->id,
            ];

            if (isset($orderData['FulfillmentChannel'])) {
                $amazonOrderRecord['fulfillment_channel'] = $orderData['FulfillmentChannel'];
            }
            if (isset($orderData['SalesChannel'])) {
                $amazonOrderRecord['sales_channel'] = $orderData['SalesChannel'];
            }
            if (isset($orderData['ShipServiceLevel'])) {
                $amazonOrderRecord['ship_service_level'] = $orderData['ShipServiceLevel'];
            }
            if (isset($orderData['OrderTotal'])) {
                $amazonOrderRecord['total_amount'] = $orderData['OrderTotal']['Amount'];
            }
            if (isset($orderData['OrderTotal']['CurrencyCode'])) {
                $amazonOrderRecord['currency'] = $orderData['OrderTotal']['CurrencyCode'];
            }
            if (isset($orderData['NumberOfItemsShipped'])) {
                $amazonOrderRecord['number_of_items_shipped'] = $orderData['NumberOfItemsShipped'];
            }
            if (isset($orderData['NumberOfItemsUnshipped'])) {
                $amazonOrderRecord['number_of_items_unshippped'] = $orderData['NumberOfItemsUnshipped'];
            }
            if (isset($orderData['PaymentMethod'])) {
                $amazonOrderRecord['payment_method'] = $orderData['PaymentMethod'];
            }
            if (isset($orderData['BuyerName'])) {
                $amazonOrderRecord['buyer_name'] = $orderData['BuyerName'];
            }
            if (isset($orderData['BuyerEmail'])) {
                $amazonOrderRecord['buyer_email'] = $orderData['BuyerEmail'];
            }
            if (isset($orderData['EarliestShipDate'])) {
                $amazonOrderRecord['earliest_ship_date'] = $orderData['EarliestShipDate'];
            }
            if (isset($orderData['LatestShipDate'])) {
                $amazonOrderRecord['latest_ship_date'] = $orderData['LatestShipDate'];
            }
            if (isset($orderData['EarliestDeliveryDate'])) {
                $amazonOrderRecord['earliest_delivery_date'] = $orderData['EarliestDeliveryDate'];
            }
            if (isset($orderData['LatestDeliveryDate'])) {
                $amazonOrderRecord['latest_delivery_date'] = $orderData['LatestDeliveryDate'];
            }

            AmazonOrder::updateOrCreate(
                [
                    'platform' => $schedule->store_name,
                    'amazon_order_id' => $orderData['AmazonOrderId'],
                ],
                $amazonOrderRecord
            );

            foreach ($originOrderItemList as $orderItem) {
                $amazonOrderItemRecord = [
                    'amazon_order_id' => $orderData['AmazonOrderId'],
                    'asin' => $orderItem['ASIN'],
                    'seller_sku' => $orderItem['SellerSKU'],
                    'order_item_id' => $orderItem['OrderItemId'],
                    'title' => $orderItem['Title'],
                    'quantity_ordered' => $orderItem['QuantityOrdered'],
                ];

                if (isset($orderItem['QuantityShipped'])) {
                    $amazonOrderItemRecord['quantity_shipped'] = $orderItem['QuantityShipped'];
                }
                if (isset($orderItem['ItemPrice'])) {
                    $amazonOrderItemRecord['item_price'] = $orderItem['ItemPrice']['Amount'];
                }
                if (isset($orderItem['ShippingPrice'])) {
                    $amazonOrderItemRecord['shipping_price'] = $orderItem['ShippingPrice']['Amount'];
                }
                if (isset($orderItem['GiftWrapPrice'])) {
                    $amazonOrderItemRecord['gift_wrap_price'] = $orderItem['GiftWrapPrice']['Amount'];
                }
                if (isset($orderItem['ItemTax'])) {
                    $amazonOrderItemRecord['item_tax'] = $orderItem['ItemTax']['Amount'];
                }
                if (isset($orderItem['ShippingTax'])) {
                    $amazonOrderItemRecord['shipping_tax'] = $orderItem['ShippingTax']['Amount'];
                }
                if (isset($orderItem['GiftWrapTax'])) {
                    $amazonOrderItemRecord['gift_wrap_tax'] = $orderItem['GiftWrapTax']['Amount'];
                }
                if (isset($orderItem['ShippingDiscount'])) {
                    $amazonOrderItemRecord['shipping_discount'] = $orderItem['ShippingDiscount']['Amount'];
                }
                if (isset($orderItem['PromotionDiscount'])) {
                    $amazonOrderItemRecord['promotion_discount'] = $orderItem['PromotionDiscount']['Amount'];
                }

                $amazonOrderItem = AmazonOrderItem::updateOrCreate(
                    [
                        'amazon_order_id' => $orderData['AmazonOrderId'],
                        'order_item_id' => $orderItem['OrderItemId'],
                    ],
                    $amazonOrderItemRecord
                );
            }
        }

        return true;
    }
}
