<?php

namespace App\Console\Commands;

use App\Models\PlatformProductFeed;
use Illuminate\Console\Command;
use Peron\AmazonMws\AmazonFeedResult;

class GetAmazonFeedResult extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get amazon feed result';

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
        $pendingFeeds = PlatformProductFeed::where('feed_processing_status', '=', '_SUBMITTED_')->get();

        foreach ($pendingFeeds as $pendingFeed) {
            $storeName = $pendingFeed->platform;
            $feedResultRequest = new AmazonFeedResult($storeName);
            $feedResultRequest->setFeedId($pendingFeed->feed_submission_id);

            if ($feedResultRequest->fetchFeedResult() !== false) {
                $feedResultRequest->saveFeed('/tmp/amazon_feed_result/'.$pendingFeed->feed_submission_id);

                $feedResult = simplexml_load_string($feedResultRequest->getRawFeed());
                if ((int) $feedResult->Message->ProcessingReport->ProcessingSummary->MessagesWithError > 0) {
                    $pendingFeed->feed_processing_status = '_COMPLETE_WITH_ERROR_';

                    $amazonAccountName = '';
                    switch (substr($pendingFeed->platform, 0, 2)) {
                        case 'BC':
                            $amazonAccountName = 'BrandsConnect';
                            $alertEmail = 'amazon_us@brandsconnect.net';
                            break;

                        case 'PX':
                            $amazonAccountName = 'ProductXpress';
                            $alertEmail = 'amazoneu@productxpress.com';
                            break;

                        case 'IG':
                            $amazonAccountName = 'Innovation Gadget';
                            $alertEmail = 'amazoneu@buholoco.com';
                            break;

                        case 'CV':
                            $amazonAccountName = 'ChatAndVision';
                            $alertEmail = 'amazonus@chatandvision.com';
                            break;

                        case '3D':
                            $amazonAccountName = '3Doodler';
                            $alertEmail = 'amazon_us@the3Doodler.com';
                            break;

                        default:
                            $alertEmail = 'handy.hon@eservicesgroup.com';
                    }

                    //$alertEmail = "handy.hon@eservicesgroup.com";

                    foreach ($feedResult->Message->ProcessingReport->Result as $itemResult) {
                        if ((string) $itemResult->ResultCode === 'Error') {
                            $subject = "[{$amazonAccountName}] Product Feed ".(string) $itemResult->ResultCode;
                            $message = 'marketplace SKU : <'.(string) $itemResult->AdditionalInfo->SKU.">\r\n";
                            $message .= 'MessageCode : '.(string) $itemResult->ResultMessageCode."\r\n";
                            $message .= 'Description : '.(string) $itemResult->ResultDescription."\r\n";

                            mail("{$alertEmail}, handy.hon@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
                        }
                    }
                } elseif ((int) $feedResult->Message->ProcessingReport->ProcessingSummary->MessagesWithWarning > 0) {
                    $pendingFeed->feed_processing_status = '_COMPLETE_WITH_WARNING_';
                } else {
                    $pendingFeed->feed_processing_status = '_COMPLETE_';
                }
                $pendingFeed->save();
            }
        }
    }
}
