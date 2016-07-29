<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;

use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class SubmitPlatformOrderFufillment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'platformMaket:updateShipment  {--api= : amazon or lazada}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the shipment status and tracking number into Platform Seller Central once the order has been shipped';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        parent::__construct();
        $this->apiPlatformFactoryService=$apiPlatformFactoryService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         $bizType=$this->option('api');
         $this->apiPlatformFactoryService->submitOrderFufillment($bizType);
    }

}
