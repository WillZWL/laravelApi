<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSkuMapping;
use App\Models\Store;
use Illuminate\Console\Command;
use Peron\AmazonMws\AmazonFeed;
use Peron\AmazonMws\AmazonFeedResult;

class FbaFeesEstimate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:fbafees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get FBA fees estimate from amazon';

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
        $amazonStores = Store::where('marketplace', 'AMAZON')->get();
        foreach ($amazonStores as $store) {
            $this->getFbaFeesEstimate($store);
        }
    }

    public function getFbaFeesEstimate(Store $store)
    {
        $credientials = json_decode($store->credentials);

        $mappings = MarketplaceSkuMapping::where('marketplace_id', $store->store_code.$store->marketplace)
            ->where('country_id', $store->country)
            ->get();

        // TODO
        // no api implemented


    }
}
