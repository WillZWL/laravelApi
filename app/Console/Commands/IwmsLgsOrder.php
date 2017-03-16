<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IwmsApi\IwmsFactoryWmsService;

class IwmsLgsOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Iwms:LgsOrder {action}  {--wms= : iwms}';

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
        //
        $merchantArr = array("ESG", "ESG-HK-TEST");
        $wmsPlatform = $this->option('wms');
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($wmsPlatform);
        $action = $this->argument('action');
        if($action == "setLgsStatus"){
            foreach ($merchantArr as $merchantId) {
                $this->iwmsFactoryWmsService->cronSetLgsOrderStatus($merchantId);
            }
        }else if($action == "getLgsDocument"){
            $this->iwmsFactoryWmsService->cronGetLgsOrderDocument();
        }
    }
}
