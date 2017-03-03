<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SkuFirstTimeStockAlertService;

class SkuFirstTimeStockAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sku:SendSkuFirstTimeStockAlert {--sku_type= :1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email Alert For SKU First Time Stocks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SkuFirstTimeStockAlertService $skuFirstTimeStockAlertService)
    {
        parent::__construct();
        $this->skuFirstTimeStockAlertService = $skuFirstTimeStockAlertService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Send Email Alert For SKU First Time Stocks. '.\Carbon\Carbon::now());
        $options = $this->option();
        if (isset($options['sku_type'])) {
            $this->skuFirstTimeStockAlertService->sendSkuFirstTimeStockAlertEmail($options);
        }
    }
}
