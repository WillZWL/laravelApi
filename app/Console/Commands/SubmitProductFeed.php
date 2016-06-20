<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
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

    const PENDING_PRODUCT = 1;
    const COMPLETE_PRODUCT = 128;

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
        $pendingSkus = MarketplaceSkuMapping::join('product', 'marketplace_sku_mapping.sku', '=', 'product.sku')
            ->join('product_content', function ($q) {
                $q->on('product.sku', '=', 'product_content.prod_sku')
                    ->on('marketplace_sku_mapping.lang_id', '=', 'product_content.lang_id');
            })->join('product_content_extend', function ($q) {
                $q->on('product.sku', '=', 'product_content_extend.prod_sku')
                    ->on('marketplace_sku_mapping.lang_id', '=', 'product_content_extend.lang_id');
            })
            ->join('brand', 'brand.id', '=', 'product.brand_id')
            ->where('marketplace_sku_mapping.marketplace_id', 'like', '%AMAZON')
            ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
            ->where('marketplace_sku_mapping.process_status', '&', self::PENDING_PRODUCT)  // bit 1, PRODUCT_UPDATED
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
                $messageNode =     '<Message>';
                $messageNode .=         '<MessageID>'.++$index.'</MessageID>';
                $messageNode .=         '<OperationType>Update</OperationType>';
                $messageNode .=         '<Product>';
                $messageNode .=             '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                $messageNode .=             '<StandardProductID>';
                $messageNode .=                 '<Type>ASIN</Type>';
                $messageNode .=                 '<Value>'.$pendingSku->asin.'</Value>';
                $messageNode .=             '</StandardProductID>';
                $messageNode .=             '<ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>';
                $messageNode .=             '<LaunchDate>2014-04-22T04:00:00</LaunchDate>';
                $messageNode .=             '<Condition>';
                $messageNode .=                 '<ConditionType>'.$pendingSku->condition.'</ConditionType>';
                $messageNode .=                 '<ConditionNote>'.$pendingSku->condition_note.'</ConditionNote>';
                $messageNode .=             '</Condition>';
                $messageNode .=             '<DescriptionData>';
                $messageNode .=                 '<Title><![CDATA['.$pendingSku->prod_name.']]></Title>';
                $messageNode .=                 '<Brand><![CDATA['.$pendingSku->brand_name.']]></Brand>';
                $messageNode .=                 '<Description>';
                $messageNode .=                     '<![CDATA['.$pendingSku->detail_desc.']]>';
                $messageNode .=                     '<![CDATA['.$pendingSku->contents.']]>';
                $messageNode .=                 '</Description>';
                foreach (explode("\n", $pendingSku->feature) as $bulletPoint) {
                    $messageNode .=             '<BulletPoint>';
                    $messageNode .=                 '<![CDATA['. $bulletPoint .']]>';
                    $messageNode .=             '</BulletPoint>';
                }
                $messageNode .=                 '<ItemDimensions>';
                $messageNode .=                     '<Length unitOfMeasure="CM">'.$pendingSku->length.'</Length>';
                $messageNode .=                     '<Width unitOfMeasure="CM">'.$pendingSku->width.'</Width>';
                $messageNode .=                     '<Height unitOfMeasure="CM">'.$pendingSku->height.'</Height>';
                $messageNode .=                 '</ItemDimensions>';
                $messageNode .=                 '<ShippingWeight unitOfMeasure="KG">'.number_format($pendingSku->weight, 2, '.', '').'</ShippingWeight>';
                $messageNode .=                 '<Manufacturer><![CDATA['.$pendingSku->brand_name.']]></Manufacturer>';
                foreach (explode("\n", $pendingSku->keywords) as $keyword) {
                    $messageNode .=                 '<SearchTerms>'.$keyword.'</SearchTerms>';
                }
                $messageNode .=                 '<IsGiftWrapAvailable>false</IsGiftWrapAvailable>';
                $messageNode .=                 '<IsGiftMessageAvailable>false</IsGiftMessageAvailable>';
                $messageNode .=                 '<DeliveryChannel>direct_ship</DeliveryChannel>';
                $messageNode .=             '</DescriptionData>';
                $messageNode .=         '</Product>';
                $messageNode .=     '</Message>';

                $xml .= $messageNode;
            }
            $xml .= '</AmazonEnvelope>';

            $platformProductFeed = new PlatformProductFeed();
            $platformProductFeed->platform = $marketplace;
            $platformProductFeed->feed_type = '_POST_PRODUCT_DATA_';

            $feed = new AmazonFeed($marketplace);
            $feed->setFeedType('_POST_PRODUCT_DATA_');
            $feed->setFeedContent($xml);

            if ($feed->submitFeed() === false) {
                $platformProductFeed->feed_processing_status = '_SUBMITTED_FAILED';
            } else {
                $pendingSkuGroup->each(function ($pendingSku) {
                    $mapping = MarketplaceSkuMapping::where('marketplace_sku', '=', $pendingSku->marketplace_sku)
                        ->where('mp_control_id', '=', $pendingSku->mp_control_id)
                        ->where('sku', '=', $pendingSku->sku)
                        ->first();

                    $mapping->process_status ^= self::PENDING_PRODUCT;
                    $mapping->process_status |= self::COMPLETE_PRODUCT;
                    $mapping->save();
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
