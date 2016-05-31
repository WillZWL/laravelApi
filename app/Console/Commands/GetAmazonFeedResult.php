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
        $pendingFeeds = PlatformProductFeed::where('feed_processing_status', '=', '_SUBMITTED_')->get();

        foreach ($pendingFeeds as $pendingFeed) {
            $storeName = $pendingFeed->platform;
            $feedResultRequest = new AmazonFeedResult($storeName);
            $feedResultRequest->setFeedId($pendingFeed->feed_submission_id);
            $feedResultRequest->fetchFeedResult();

            $feedResultRequest->saveFeed('/tmp/amazon_feed_result/'.$pendingFeed->feed_submission_id);

            $pendingFeed->feed_processing_status = '_COMPLETE_';
            $pendingFeed->save();
            //$feedResult = simplexml_load_string($feedResultRequest->getRawFeed());

            // TODO::
            // 1, change price status to failure.
            // 2, send alert mail to user.

        }

    }
}
