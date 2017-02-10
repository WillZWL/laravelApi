<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SkuListingAlertService;

class SkuListingAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sku:SendSkuListingAlert {--sku_type= :1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email Alert For SKU Listing';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SkuListingAlertService $skuListingAlertService)
    {
        parent::__construct();
        $this->skuListingAlertService = $skuListingAlertService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Send Email Alert For SKU Listing. '.\Carbon\Carbon::now());
        $this->skuListingAlertService->sendSkuListingAlertEmail();

    }
}
