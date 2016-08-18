<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Services\PlatformMarketSkuMappingService;

use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class PlatformMarketplaceSkuMapping extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:skuMapping  {--api= : amazon or lazada} ';

   /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve orders from platfrom market like(amazon,lazada,etc)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PlatformMarketSkuMappingService $platformMarketSkuMappingService)
    {
        $this->platformMarketSkuMappingService=$platformMarketSkuMappingService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $apiOption = $this->option('api');
        if($apiOption=="all"){
            foreach($this->platfromMakert as $apiName){
                $this->runSkuMapping($this->getStores($apiName),$apiName);
            }
        }else{
            $this->runSkuMapping($this->getStores($apiOption),$apiOption);
        }
    }

    public function runSkuMapping($stores,$apiName)
    {
        if($stores){
            //$this->platformMarketSkuMappingService->initMarketplaceSkuMapping($stores);
            foreach ($stores as $storeName => $store){
                $this->platformMarketSkuMappingService->updateOrCreateSellingPlatform($storeName,$store);
                $this->platformMarketSkuMappingService->updateOrCreatePlatformBizVar($storeName,$store);
            }
        }
    }

    public function getStores($apiName)
    {
        if($apiName=="lazada"){
            $stores = Config::get('lazada-mws.store');
        }else if($apiName=="amazon"){
            $stores = Config::get('amazon-mws.store');
        }
        return $stores;
    }
}
