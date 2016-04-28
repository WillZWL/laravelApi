<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
use App\Models\PlatformOrderFeed;
use App\Models\PlatformProductFeed;
use Illuminate\Console\Command;
use Config;
use Peron\AmazonMws\AmazonFeed;

class SubmitProductFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post product feed to amazon';

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
        $waitingUpdateSKUs = MarketplaceSkuMapping::join('product', 'marketplace_sku_mapping.sku', '=', 'product.sku')
            ->join('product_content', function ($q) {
                $q->on('product.sku', '=', 'product_content.prod_sku')
                    ->on('marketplace_sku_mapping.lang_id', '=', 'product_content.lang_id');
            })
            ->join('brand', 'brand.id', '=', 'product.brand_id')
            ->where('marketplace_sku_mapping.marketplace_id', 'like', '%AMAZON')
            ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
            ->where('marketplace_sku_mapping.process_status', '&', 1)  // bit 1, PRODUCT_UPDATED
            ->get();

        foreach ($waitingUpdateSKUs as $perSKU) {
            $marketplace = $perSKU->marketplace_id.$perSKU->country_id;
            if (isset($stores[$marketplace])) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>';
                $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
                $xml .=     '<Header>';
                $xml .=         '<DocumentVersion>1.01</DocumentVersion>';
                $xml .=         '<MerchantIdentifier>'.$stores[$marketplace]['merchantId'].'</MerchantIdentifier>';
                $xml .=     '</Header>';
                $xml .=     '<MessageType>Product</MessageType>';
                $xml .=     '<Message>';
                $xml .=         '<MessageID>1</MessageID>';
                $xml .=         '<OperationType>Update</OperationType>';
                $xml .=         '<Product>';
                $xml .=             '<SKU>'.$perSKU->marketplace_sku.'</SKU>';
                $xml .=             '<StandardProductID>';
                $xml .=                 '<Type>ASIN</Type>';
                $xml .=                 '<Value>'.$perSKU->asin.'</Value>';
                $xml .=             '</StandardProductID>';
                $xml .=             '<ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>';
                $xml .=             '<LaunchDate>2014-04-22T04:00:00</LaunchDate>';
                $xml .=             '<Condition>';
                $xml .=                 '<ConditionType>New</ConditionType>';
                $xml .=             '</Condition>';
                $xml .=             '<DescriptionData>';
                $xml .=                 '<Title><![CDATA['.$perSKU->prod_name.']]></Title>';
                $xml .=                 '<Brand><![CDATA['.$perSKU->brand_name.']]></Brand>';
                $xml .=                 '<Description>';
                $xml .=                     '<![CDATA['.$perSKU->contents.']]>';
                $xml .=                 '</Description>';
                $xml .=                 '<ShippingWeight unitOfMeasure="KG">'.number_format($perSKU->weight, 2, '.', '').'</ShippingWeight>';
                $xml .=                 '<Manufacturer><![CDATA['.$perSKU->brand_name.']]></Manufacturer>';
                $xml .=                 '<IsGiftWrapAvailable>false</IsGiftWrapAvailable>';
                $xml .=                 '<IsGiftMessageAvailable>false</IsGiftMessageAvailable>';
                $xml .=                 '<DeliveryChannel>direct_ship</DeliveryChannel>';
                $xml .=             '</DescriptionData>';
                $xml .=         '</Product>';
                $xml .=     '</Message>';
                $xml .= '</AmazonEnvelope>';

                $platformProductFeed = new PlatformProductFeed();
                $platformProductFeed->platform = $marketplace;
                $platformProductFeed->marketplace_sku = $perSKU->marketplace_sku;
                $platformProductFeed->feed_type = '_POST_PRODUCT_DATA_';

                $feed = new AmazonFeed($marketplace);
                $feed->setFeedType('_POST_PRODUCT_DATA_');
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
