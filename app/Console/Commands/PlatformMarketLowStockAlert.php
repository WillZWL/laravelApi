<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use  App\Services\PlatformMarketInventoryService;

class PlatformMarketLowStockAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:sendLowStockAlert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'System throw alert when SKU reached threshold settings';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $platformMarketInventoryService;

    public function __construct(PlatformMarketInventoryService $platformMarketInventoryService)
    {
        $this->platformMarketInventoryService = $platformMarketInventoryService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->platformMarketInventoryService->sendLowStockAlert();
    }
}
