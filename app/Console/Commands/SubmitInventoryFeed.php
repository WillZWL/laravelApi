<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
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

        $waitingSKUs = MarketplaceSkuMapping::where('process_status', '&', 4)
            ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
            ->where('marketplace_id', 'like', '%AMAZON')
            ->get();

        foreach ($waitingSKUs as $perSKU) {
            $marketplace = $perSKU->marketplace_id.$perSKU->country_id;
            $fulfillment = $perSKU->fulfillment;

            if (isset($stores[$marketplace])) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>';
                $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
                $xml .=     '<Header>';
                $xml .=         '<DocumentVersion>1.01</DocumentVersion>';
                $xml .=         '<MerchantIdentifier>'.$stores[$marketplace]['merchantId'].'</MerchantIdentifier>';
                $xml .=     '</Header>';
                $xml .=     '<MessageType>Product</MessageType>';
                $xml .=         '<Message>';
                $xml .=             '<MessageID>1</MessageID>';
                $xml .=             '<OperationType>Update</OperationType>';
                $xml .=             '<Inventory>';
                $xml .=                 '<SKU>'.$perSKU->marketplace_sku.'</SKU>';
                $xml .=                 '<FulfillmentCenterID>DEFAULT</FulfillmentCenterID>';
                $xml .=                 '<Quantity>'.$perSKU->inventory.'</Quantity>';
                //$xml .=                 '<FulfillmentLatency>18</FulfillmentLatency>';
                $xml .=                 '<SwitchFulfillmentTo>'.$fulfillment.'</SwitchFulfillmentTo>';
                $xml .=             '</Inventory>';
                $xml .=         '</Message>';
                $xml .= '</AmazonEnvelope>';

                $platformProductFeed = new PlatformProductFeed();
                $platformProductFeed->platform = $marketplace;
                $platformProductFeed->marketplace_sku = $perSKU->marketplace_sku;
                $platformProductFeed->feed_type = '_POST_INVENTORY_AVAILABILITY_DATA_';

                $feed = new AmazonFeed($marketplace);
                $feed->setFeedType('_POST_INVENTORY_AVAILABILITY_DATA_');
                $feed->setFeedContent($xml);

                if ($feed->submitFeed() === false) {
                    $platformProductFeed->feed_processing_status = '_SUBMITTED_FAILED';
                } else {
                    $response = $feed->getResponse();
                    $platformProductFeed->feed_submission_id = $response['FeedSubmissionId'];
                    $platformProductFeed->submitted_date = $response['SubmittedDate'];
                    $platformProductFeed->feed_processing_status = $response['FeedProcessingStatus'];
                }
                $platformProductFeed->save();
            }
        }
    }
}
