<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiFnacService;
use Config;

class PlatformMarketFnacPendingPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:updatePendingPayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pending payment platform order status to atomesg database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiFnacService $apiFnacService)
    {
        parent::__construct();
        $this->apiFnacService = $apiFnacService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fnacStores = Config::get('fnac-mws.store');
        if (isset($fnacStores)) {
            foreach ($fnacStores as $storeName => $store) {
                $this->apiFnacService->updatePendingPaymentStatus($storeName);
            }
        }
    }
}
