<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\PlatformProductFeed;
use Config;

//use fnac api package
use Peron\AmazonMws\AmazonFeed;

class ApiAmazonProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    public function __construct()
    {

    }

    public function getPlatformId()
    {
        return 'Amazon';
    }

    public function getProductList($storeName)
    {
    
    }

    protected function submitProductPriceAndInventory($storeName)
    {
        $this->submitProductPrice($storeName);
        $this->submitProductInventory($storeName); 
    }

    public function submitProductPrice($storeName)
    {
        $stores = Config::get('amazon-mws.store');
        $pendingSkuGroup = MarketplaceSkuMapping::ProcessStatusProduct($storeName, self::PENDING_PRICE);
        if($pendingSkuGroup){
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .= '<Header>';
            $xml .= '<DocumentVersion>1.01</DocumentVersion>';
            $xml .= '<MerchantIdentifier>'.$stores[$storeName]['merchantId'].'</MerchantIdentifier>';
            $xml .= '</Header>';
            $xml .= '<MessageType>Price</MessageType>';

            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageDom = '<Message>';
                $messageDom .= '<MessageID>'.++$index.'</MessageID>';
                $messageDom .= '<Price>';
                $messageDom .= '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                $messageDom .= '<StandardPrice currency="DEFAULT">'.$pendingSku->price.'</StandardPrice>';
                $messageDom .= '</Price>';
                $messageDom .= '</Message>';

                $xml .= $messageDom;
            }
            $xml .= '</AmazonEnvelope>';
            $platformProductFeed = new PlatformProductFeed();
            $platformProductFeed->platform = $storeName;
            $platformProductFeed->feed_type = '_POST_PRODUCT_PRICING_DATA_';

            $feed = new AmazonFeed($storeName);
            $feed->setFeedType('_POST_PRODUCT_PRICING_DATA_');
            $feed->setMarketplaceIds($stores[$storeName]['marketplaceId']);
            $feed->setFeedContent($xml);

            if ($feed->submitFeed() === false) {
                $platformProductFeed->feed_processing_status = '_SUBMITTED_FAILED';
            } else {
                $this->updatePendingProductProcessStatus($processStatusProduct,self::PENDING_PRICE);
                $response = $feed->getResponse();
                $platformProductFeed->feed_submission_id = $response['FeedSubmissionId'];
                $platformProductFeed->submitted_date = $response['SubmittedDate'];
                $platformProductFeed->feed_processing_status = $response['FeedProcessingStatus'];
            }
            $platformProductFeed->save();
        }
    }

    public function submitProductInventory($storeName)
    {
        $stores = Config::get('amazon-mws.store');
        $pendingSkuGroup = MarketplaceSkuMapping::ProcessStatusProduct($storeName, self::PENDING_INVENTORY);
        if($pendingSkuGroup){
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .= '<Header>';
            $xml .= '<DocumentVersion>1.01</DocumentVersion>';
            $xml .= '<MerchantIdentifier>'.$stores[$storeName]['merchantId'].'</MerchantIdentifier>';
            $xml .= '</Header>';
            $xml .= '<MessageType>Product</MessageType>';

            foreach ($pendingSkuGroup as $index => $pendingSku) {
                try {
                    $messageNode = '<Message>';
                    $messageNode .= '<MessageID>'.++$index.'</MessageID>';
                    $messageNode .= '<OperationType>Update</OperationType>';
                    if ($pendingSku->fulfillment === 'AFN') {
                        $inventory = '';
                        $inventory .= '<Inventory>';
                        $inventory .= '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                        $inventory .= '<FulfillmentCenterID>'.$pendingSku->fulfillmentCenter('AFN')->first()->name.'</FulfillmentCenterID>';
                        $inventory .= '<Lookup>FulfillmentNetwork</Lookup>';
                        $inventory .= '<SwitchFulfillmentTo>AFN</SwitchFulfillmentTo>';
                        $inventory .= '</Inventory>';
                    } else {
                        $inventory = '';
                        $inventory .= '<Inventory>';
                        $inventory .= '<SKU>'.$pendingSku->marketplace_sku.'</SKU>';
                        $inventory .= '<FulfillmentCenterID>DEFAULT</FulfillmentCenterID>';
                        $inventory .= '<Quantity>'.$pendingSku->inventory.'</Quantity>';
                        $inventory .= '<FulfillmentLatency>'.$pendingSku->fulfillment_latency.'</FulfillmentLatency>';
                        $inventory .= '<SwitchFulfillmentTo>MFN</SwitchFulfillmentTo>';
                        $inventory .= '</Inventory>';
                    }
                    $messageNode .= $inventory;
                    $messageNode .= '</Message>';

                    $xml .= $messageNode;
                } catch (\Exception $e) {
                    mail('jimmy@eservciesgroup.com', 'SOS', 'Invenotry Feed Error');
                }
            }
            $xml .= '</AmazonEnvelope>';
            $platformProductFeed = new PlatformProductFeed();
            $platformProductFeed->platform = $storeName;
            $platformProductFeed->feed_type = '_POST_INVENTORY_AVAILABILITY_DATA_';

            $feed = new AmazonFeed($storeName);
            $feed->setFeedType('_POST_INVENTORY_AVAILABILITY_DATA_');
            $feed->setFeedContent($xml);

            if ($feed->submitFeed() === false) {
                $platformProductFeed->feed_processing_status = '_SUBMITTED_FAILED';
            } else {
                $this->updatePendingProductProcessStatus($processStatusProduct,self::PENDING_INVENTORY);
                $response = $feed->getResponse();
                $platformProductFeed->feed_submission_id = $response['FeedSubmissionId'];
                $platformProductFeed->submitted_date = $response['SubmittedDate'];
                $platformProductFeed->feed_processing_status = $response['FeedProcessingStatus'];
            }
            $platformProductFeed->save();
        }
    }

    public function submitProductCreate($storeName)
    {

    }

    public function submitProductUpdate($storeName)
    {
        $this->runProductUpdate($storeName, 'pendingProduct');
    }
}
