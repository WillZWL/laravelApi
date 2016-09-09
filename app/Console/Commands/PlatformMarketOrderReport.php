<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Config;

class PlatformMarketOrderReport extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:orderReport {action} {--api= : amazon or lazada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get marketplace order report';

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
            $action = $this->argument('action');
            foreach ($stores as $storeName => $store) {
                if($action == "alertOrder"){
                    //\Log::info('Report inventory. '.\Carbon\Carbon::now());exit();
                    $this->getApiPlatformFactoryService($apiName)->alertSetOrderReadyToShip($storeName);  
                }
            }
        }
    }
}
