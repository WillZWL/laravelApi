<?php

namespace App\Console\Commands;

use App\Models\PlatformOrderFeed;
use App\Models\SoShipment;
use Carbon\Carbon;
use Config;
use App\Models\AmazonOrder;
use App\Models\So;
use Illuminate\Console\Command;
use Peron\AmazonMws\AmazonFeed;

class SubmitOrderFulfillmentFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:fulfillment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post order fulfillment feed to amazon';

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
        $stores = Config::get('amazon-mws.store');
        $amazonOrderList = AmazonOrder::unshippedOrder()
            ->leftJoin('platform_order_feeds', 'amazon_orders.amazon_order_id', '=', 'platform_order_feeds.platform_order_id')
            ->whereNull('platform_order_feeds.platform_order_id')
            ->select('amazon_orders.*')
            ->get();
        $platformOrderIdList = $amazonOrderList->pluck('platform', 'amazon_order_id')->toArray();

        $esgOrders = So::whereIn('platform_order_id', array_keys($platformOrderIdList))
            ->where('platform_group_order', '=', '1')
            ->where('status', '=', '6')
            ->get();

        foreach ($esgOrders as $esgOrder) {
            $esgOrderShipment = SoShipment::where('sh_no', '=', $esgOrder->so_no."-01")->where('status', '=', '2')->first();
            if ($esgOrderShipment) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>';
                $xml .= '<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">';
                $xml .= '<Header>';
                $xml .= '<DocumentVersion>1.01</DocumentVersion>';
                $xml .= '<MerchantIdentifier>'.$stores[$platformOrderIdList[$esgOrder->platform_order_id]]['merchantId'].'</MerchantIdentifier>';
                $xml .= '</Header>';
                $xml .= '<MessageType>OrderFulfillment</MessageType>';
                $xml .= '<Message>';
                $xml .= '<MessageID>1</MessageID>';
                $xml .= '<OrderFulfillment>';
                $xml .= '<AmazonOrderID>'.$esgOrder->platform_order_id.'</AmazonOrderID>';
                $xml .= '<MerchantFulfillmentID>'.$esgOrder->so_no.'</MerchantFulfillmentID>';
                $xml .= '<FulfillmentDate>'.Carbon::parse($esgOrder->dispatch_date)->format('c').'</FulfillmentDate>';
                $xml .= '<FulfillmentData>';
                $xml .= '<CarrierName>'.$esgOrderShipment->courierInfo->courier_name.'</CarrierName>';
                $xml .= '<ShippingMethod>Standard</ShippingMethod>';
                $xml .= '<ShipperTrackingNumber>'.$esgOrderShipment->tracking_no.'</ShipperTrackingNumber>';
                $xml .= '</FulfillmentData>';
                $xml .= '</OrderFulfillment>';
                $xml .= '</Message>';
                $xml .= '</AmazonEnvelope>';

                $platformOrderFeed = PlatformOrderFeed::firstOrNew(['platform_order_id' => $esgOrder->platform_order_id]);
                $platformOrderFeed->platform = $platformOrderIdList[$esgOrder->platform_order_id];
                $platformOrderFeed->feed_type = '_POST_ORDER_FULFILLMENT_DATA_';

                $feed = new AmazonFeed($platformOrderIdList[$esgOrder->platform_order_id]);
                $feed->setFeedType('_POST_ORDER_FULFILLMENT_DATA_');
                $feed->setFeedContent($xml);

                if ($feed->submitFeed() === false) {
                    $platformOrderFeed->feed_processing_status = '_SUBMITTED_FAILED';
                } else {
                    $response = $feed->getResponse();
                    $platformOrderFeed->feed_submission_id = $response['FeedSubmissionId'];
                    $platformOrderFeed->submitted_date = $response['SubmittedDate'];
                    $platformOrderFeed->feed_processing_status = $response['FeedProcessingStatus'];

                    $this->markSplitOrderShipped($esgOrder);
                }

                $platformOrderFeed->save();
            }
        }
    }

    public function markSplitOrderShipped(So $order)
    {
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_id)
            ->where('platform_split_order', '=', 1)->get();

        $splitOrders->map(function($splitOrder) use($order) {
            $splitOrder->dispatch_date = $order->dispatch_date;
            $splitOrder->status = 6;
            $splitOrder->save();
        });
    }
}
