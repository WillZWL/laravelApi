<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Schedule;
use Config;


class PlatformMarketReasons extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:Reasons  {--api= : amazon or lazada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->platfromMakert = array("lazada");
        parent::runPlatformMarketConsoleFunction();
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores){
            foreach ($stores as $storeName => $store) {
                $this->getApiPlatformFactoryService($apiName)->updateOrCreatePlatformMarketReasons($storeName); 
            }
        }
    }
}
