<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\PlatformProductFeed;
use Illuminate\Console\Command;
use Config;
use Peron\AmazonMws\AmazonFeed;

class SubmitPriceFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post price feed to amazon';

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

        $pendingSkus = MarketplaceSkuMapping::where('process_status', '&', 2)
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
                $messageDom  =  '<Message>';
                $messageDom .=      '<MessageID>'.++$index.'</MessageID>';
                $messageDom .=      '<Price>';
                $messageDom .=          '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                $messageDom .=          '<StandardPrice currency="'.$pendingSku->currency.'">'.$pendingSku->price.'</StandardPrice>';
                $messageDom .=      '</Price>';
                $messageDom .=  '</Message>';

                $xml .= $messageDom;
            }

            $xml .= '</AmazonEnvelope>';

            $platformProductFeed = new PlatformProductFeed();
            $platformProductFeed->platform = $marketplace;
            $platformProductFeed->feed_type = '_POST_PRODUCT_PRICING_DATA_';

            $feed = new AmazonFeed($marketplace);
            $feed->setFeedType('_POST_PRODUCT_PRICING_DATA_');
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
