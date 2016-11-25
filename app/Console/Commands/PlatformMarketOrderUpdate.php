<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class PlatformMarketOrderUpdate extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:orderUpdate  {--api= : amazon or lazada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve orders from platfrom market like(amazon,lazada,etc)';

    /**
     * Create a new command instance.
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
        $this->platfromMakert = array("priceminister","fnac");
        $this->runPlatformMarketConsoleFunction();
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
