<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SkuCreatedAlertService;

class SkuCreatedAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sku:SendNewSkuAlert {--sku_type= :1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email Alert For New SKU Created';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SkuCreatedAlertService $skuCreatedAlertService)
    {
        parent::__construct();
        $this->skuCreatedAlertService = $skuCreatedAlertService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Send Email Alert For New SKU Created. '.\Carbon\Carbon::now());
        $options = $this->option();
        if (isset($options['sku_type'])) {
            $this->skuCreatedAlertService->sendSkuCreatedAlertEmail($options);
        }
    }
}
