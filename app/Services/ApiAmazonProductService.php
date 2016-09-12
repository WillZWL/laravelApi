<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\PlatformProductFeed;
use Config;
use Excel;

//use fnac api package
use Peron\AmazonMws\AmazonFeed;
use Peron\AmazonMws\AmazonReportRequest;
use Peron\AmazonMws\AmazonReportScheduleManager;
use Peron\AmazonMws\AmazonReportScheduleList;
use Peron\AmazonMws\AmazonReportList;
use Peron\AmazonMws\AmazonReport;
use Peron\AmazonMws\AmazonReportAcknowledger;


class ApiAmazonProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    public function __construct()
    {
        $this->stores =  Config::get('amazon-mws.store');
    }

    public function getPlatformId()
    {
        return 'Amazon';
    }

    public function getProductList($storeName)
    {
    
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $this->submitProductPrice($storeName);
        $this->submitProductInventory($storeName); 
    }

    public function submitProductPrice($storeName)
    {
        $pendingSkuGroup = MarketplaceSkuMapping::ProcessStatusProduct($storeName, self::PENDING_PRICE);
        if($pendingSkuGroup){
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .= '<Header>';
            $xml .= '<DocumentVersion>1.01</DocumentVersion>';
            $xml .= '<MerchantIdentifier>'.$this->stores[$storeName]['merchantId'].'</MerchantIdentifier>';
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
            $feed->setMarketplaceIds($this->stores[$storeName]['marketplaceId']);
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
        $pendingSkuGroup = MarketplaceSkuMapping::ProcessStatusProduct($storeName, self::PENDING_INVENTORY);
        if($pendingSkuGroup){
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .= '<Header>';
            $xml .= '<DocumentVersion>1.01</DocumentVersion>';
            $xml .= '<MerchantIdentifier>'.$this->stores[$storeName]['merchantId'].'</MerchantIdentifier>';
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

    public function fulfilledInventoryReport($storeName)
    { 
        $this->readReport();
        exit();
        $reportTypeList = $this->getReportType();
        $reportScheduleList = $this->getReportScheduleList($storeName,$reportTypeList);
        if(!empty($reportScheduleList)){
            foreach($reportScheduleList as $reportSchedule){
                if(($key = array_search($reportSchedule["ReportType"], $reportTypeList)) !== false) {
                    unset($reportTypeList[$key]);
                }
            }
        }
        if(!empty($reportTypeList)){
            foreach($reportTypeList as $reportType){
                $reportSchedule = $this->setManageReportSchedule($storeName,$reportType);
            }
        }
        $reportList = $this->getReportList($storeName);
        foreach($reportList as $report){
            if(in_array($report["ReportType"],$this->getReportType())){
                $reportFile = $this->getReport($storeName,$report["ReportId"]);
            }
        }
        
    }

    public function getReportRequest($storeName,$reportType)
    {   
        $amazonReport = new AmazonReportRequest($storeName);
        $amazonReport->setReportType($reportType);
        $amazonReport->setMarketplaces($this->stores[$storeName]['marketplaceId']);
        $amazonReport->requestReport();
        $response = $amazonReport->getResponse();
    }

    public function setManageReportSchedule($storeName,$reportType)
    {
        //$scheduledDate = strtotime(date("Y-m-d 00:30:00",strtotime("+1 day")));
        $amazonReportScheduleManager = new AmazonReportScheduleManager($storeName);
        $amazonReportScheduleManager->setReportType($reportType);
        $amazonReportScheduleManager->setSchedule("_NEVER_");
        //$amazonReportScheduleManager->setScheduledDate($scheduledDate);
        $amazonReportScheduleManager->manageReportSchedule();
        return $amazonReportScheduleManager->getList();
    }

    public function getReportScheduleList($storeName,$reportTypeList)
    {
        $amazonReportScheduleList = new AmazonReportScheduleList($storeName);
        $amazonReportScheduleList->setReportTypes($reportTypeList);
        $amazonReportScheduleList->fetchReportList();
        return $amazonReportScheduleList->getList();
    }

    public function getReportList($storeName,$reportTypeList="")
    {
        $amazonRepoatList = new AmazonReportList($storeName);
        $amazonRepoatList->setAcknowledgedFilter("false");
        if($reportTypeList){
            $amazonRepoatList->setReportTypes($reportTypeList);
        }
        $amazonRepoatList->fetchReportList();
        return $amazonRepoatList->getList();
    }

    public function getReport($storeName,$reportId)
    {   
        $path = $this->getUnSuppressedReportPath();
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $pathFile = $path.'/'.$storeName.".txt";
        $amazonReport = new AmazonReport($storeName);
        $amazonReport->setReportId($reportId);
        $amazonReport->fetchReport();
        $result = $amazonReport->saveReport($pathFile);
        return $result ? $pathFile : null;
    }

    public function updateReportAcknowledgements($storeName,$reportIds,$acknowledger)
    {
        $amazonReportAcknowledger = new AmazonReportAcknowledger($storeName);
        $amazonReportAcknowledger->setReportIds($reportIds);
        $amazonReportAcknowledger->setAcknowledgedFilter($acknowledger);
        $amazonReportAcknowledger->acknowledgeReports();
        return $amazonReportAcknowledger->getList();
    }   

    public function getReportType()
    {
        return $reportTypeList = array(
            "_GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA_",
        );
    }

    public function getEsgUnSuppressedReportPath()
    {
        $reportPath = $this->getUnSuppressedReportPath();
        $cellData = null;
        $reportFiles = \File::allFiles($reportPath);
        foreach($reportFiles as $reportFile){
        	$fileName=basename($reportFile,'.txt');
        	$row = 1;
			if (($handle = fopen($reportFile, "r")) !== FALSE) {
			    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
			        $num = count($data);
			        //echo "<p> $num fields in line $row: <br /></p>\n";
			       if($row > 1){
			        	$cellData[]=array(
			        	"fileName" => $fileName,
			        	"sku" => $data['0'],
			        	"inventory" => isset($data['10']) ? $data['10'] : "",
			        	);
			        }
			        $row++;
			    }
			    fclose($handle);
			}
        }
        if($cellData){
        	$excel = \App::make('excel');
    		Excel::create("amazonReport", function ($excel) use ($cellData) {
        		$excel->sheet("amazonReport", function ($sheet) use ($cellData) {
	                	$sheet->rows($cellData);
	            	});
	    	})->store("csv",$this->getUnSuppressedReportPath());
	    }
    }

    public function getUnSuppressedReportPath()
    {
        return \Storage::disk('xml')->getDriver()->getAdapter()->getPathPrefix().date('Y').'/'.date("m").'/'.date("d")."/UNSUPPRESSED";
    }

}
