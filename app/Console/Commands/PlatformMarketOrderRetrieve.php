<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;

use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class PlatformMarketOrderRetrieve extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMaket:orderRetrieve  {--api= : amazon or lazada}';

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
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        parent::__construct();
        $this->apiPlatformFactoryService=$apiPlatformFactoryService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        if($stores=$this->getStores()){
            foreach ($stores as $storeName => $store) {
                $previousSchedule = Schedule::where('store_name', '=', $storeName)
                                            ->where('status', '=', 'C')
                                            ->orderBy('last_access_time', 'desc')
                                            ->first();
                $currentSchedule = Schedule::create([
                        'store_name' => $storeName,
                        'status' => 'N',
                        // MWS API requested: Must be no later than two minutes before the time that the request was submitted.
                        'last_access_time' => Carbon::now()->subMinutes(2)
                    ]);
                if (!$previousSchedule) {
                    $previousSchedule = $currentSchedule;
                }
                $result = $this->apiPlatformFactoryService->retrieveOrder($storeName,$previousSchedule);
                if ($result) {
                    $currentSchedule->status = 'C';
                } else {
                    $currentSchedule->status = 'F';
                    //$currentSchedule->remark = json_encode($amazonOrderList->getLastResponse());
                }
                $currentSchedule->save();
            }
        }
    }

    public function getStores()
    {
        $apiName = $this->option('api');
        if($apiName=="lazada"){
            $stores = Config::get('lazada-mws.store');
        }else if($apiName=="amazon"){
            $stores = Config::get('amazon-mws.store');
        }
        return $stores;
    }
}
