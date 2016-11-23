<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class LazadaSGOrderRetrieve extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lazadaSg:orderRetrieve';

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
        $stores = array("BCLAZADASG","CFLAZADASG");
        $this->runApiPlatformServiceFunction($stores, "lazada");
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores) {
            foreach ($stores as $storeName) {
                $previousSchedule = Schedule::where('store_name', '=', $storeName)
                                    ->where('status', '=', 'C')
                                    ->orderBy('last_access_time', 'desc')
                                    ->first();
                $currentSchedule = Schedule::create([
                        'store_name' => $storeName,
                        'status' => 'N',
                        'last_access_time' => Carbon::now()->subMinutes(2),
                    ]);
                if (!$previousSchedule) {
                    $previousSchedule = $currentSchedule;
                }
                $result = $this->getApiPlatformFactoryService($apiName)->retrieveOrder($storeName, $previousSchedule);
                if ($result) {
                    $currentSchedule->status = 'C';
                } else {
                    $currentSchedule->status = 'F';
                }
                $currentSchedule->save();
            }
        }
    }
}
