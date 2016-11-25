<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\PlatformMarketOrderTransfer;
use Config;

class PlatformMarketReadyOrderTransfer extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:orderTransfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert platform order to atomesg database';

    /**
     * Create a new command instance.
     */
    public function __construct(PlatformMarketOrderTransfer $platformMarketOrderTransfer)
    {
        parent::__construct();
        $this->platformMarketOrderTransfer = $platformMarketOrderTransfer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //update order if order updated
        $this->platfromMakert = array("priceminister","fnac");
        foreach ($this->platfromMakert as $apiName) {
            $this->runApiPlatformServiceFunction($this->getStores($apiName), $apiName);
        }
        //\Log::info('Transfer orders at . '.\Carbon\Carbon::now());
        $this->platformMarketOrderTransfer->transferReadyOrder();
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores){
            foreach ($stores as $storeName => $store) {
                $this->getApiPlatformFactoryService($apiName)->updatePlatMarketOrderStatus($storeName);
            }
        }
    }
}
