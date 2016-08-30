<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class PlatformMarketUpdatePendingStatus extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:updatePendingPayment  {--api= : amazon or lazada}';

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
        $platfromMakert=array("fnac");

        $apiOption = $this->option('api');
        if ($apiOption == 'all') {
            foreach ($platfromMakert as $apiName) {
                $this->updatePendingPaymentStatus($this->getStores($apiName), $apiName);
            }
        } else {
            $this->updatePendingPaymentStatus($this->getStores($apiOption), $apiOption);
        }
    }

    public function updatePendingPaymentStatus($stores, $apiName)
    {
        if ($stores) {
            foreach ($stores as $storeName => $store) {
                //print_r($this->getApiPlatformFactoryService($apiName));exit();
                $this->getApiPlatformFactoryService($apiName)->updatePendingPaymentStatus($storeName);
            }
        }
    }
}
