<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\PlatformProductFeed;
use Illuminate\Console\Command;
use Config;
use Peron\AmazonMws\AmazonFeed;

class SubmitInventoryFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post inventory feed to amazon';

    const PENDING_INVENTORY = 4;
    const COMPLETE_INVENTORY = 16;

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

        //$waitingSKUs = MarketplaceSkuMapping::join('inventory', 'marketplace_sku_mapping.sku', '=', 'inventory.prod_sku')
        //    ->where('marketplace_sku_mapping.marketplace_id', 'like', '%AMAZON')
        //    ->where('inventory.warehouse_id', '=', 'ES_HK')
        //    ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
        //    ->where('marketplace_sku_mapping.process_status', '&', 4)
        //    ->get();

        $pendingSkus = MarketplaceSkuMapping::where('process_status', '&', self::PENDING_INVENTORY)
            ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
            ->where('marketplace_id', 'like', '%AMAZON')
            ->get();

        $pendingSkuGroups = $pendingSkus->groupBy('mp_control_id');

        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $marketplace = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .=     '<Header>';
            $xml .=         '<DocumentVersion>1.01</DocumentVersion>';
            $xml .=         '<MerchantIdentifier>'.$stores[$marketplace]['merchantId'].'</MerchantIdentifier>';
            $xml .=     '</Header>';
            $xml .=     '<MessageType>Product</MessageType>';

            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageNode =      '<Message>';
                $messageNode .=         '<MessageID>'.++$index.'</MessageID>';
                $messageNode .=         '<OperationType>Update</OperationType>';

                if ($pendingSku->fulfillment === 'AFN') {
                    $inventory = '';
                    $inventory .=       '<Inventory>';
                    $inventory .=           '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                    $inventory .=           '<Lookup>FulfillmentNetwork</Lookup>';
                    $inventory .=           '<FulfillmentLatency>'.$pendingSku->fulfillment_latency.'</FulfillmentLatency>';
                    $inventory .=           '<SwitchFulfillmentTo>AFN</SwitchFulfillmentTo>';
                    $inventory .=       '</Inventory>';
                } else {
                    $inventory = '';
                    $inventory .=       '<Inventory>';
                    $inventory .=           '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                    $inventory .=           '<Quantity>'.$pendingSku->inventory.'</Quantity>';
                    $inventory .=           '<FulfillmentLatency>'.$pendingSku->fulfillment_latency.'</FulfillmentLatency>';
                    $inventory .=           '<SwitchFulfillmentTo>MFN</SwitchFulfillmentTo>';
                    $inventory .=       '</Inventory>';
                }

                $messageNode .= $inventory;
                $messageNode .=     '</Message>';

                $xml .= $messageNode;
            }
            $xml .= '</AmazonEnvelope>';

            $platformProductFeed = new PlatformProductFeed();
            $platformProductFeed->platform = $marketplace;
            $platformProductFeed->feed_type = '_POST_INVENTORY_AVAILABILITY_DATA_';

            $feed = new AmazonFeed($marketplace);
            $feed->setFeedType('_POST_INVENTORY_AVAILABILITY_DATA_');
            $feed->setFeedContent($xml);

            if ($feed->submitFeed() === false) {
                $platformProductFeed->feed_processing_status = '_SUBMITTED_FAILED';
            } else {

                $pendingSkuGroup->transform(function ($pendingSku) {
                    $pendingSku->process_status ^= self::PENDING_INVENTORY;
                    $pendingSku->process_status |= self::COMPLETE_INVENTORY;
                    $pendingSku->save();
                });

                $response = $feed->getResponse();
                $platformProductFeed->feed_submission_id = $response['FeedSubmissionId'];
                $platformProductFeed->submitted_date = $response['SubmittedDate'];
                $platformProductFeed->feed_processing_status = $response['FeedProcessingStatus'];
            }
            $platformProductFeed->save();
        }
    }
}
