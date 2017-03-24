<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IwmsApi\IwmsFactoryWmsService;

class IwmsDeliveryOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Iwms:deliveryOrder {action}  {--wms= : 4px} {--merchant= : ESG} {--debug= : 0 || 1} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create iwms delivery order to wms platform';

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
        $merchantArr = array("ESG", "ESG-HK-WMS");
        $wmsPlatform = $this->option('wms');
        $debugOption = $this->option('debug');
        $merchantId = $this->option('merchant');
        $debug = $debugOption ? 1 :0;
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($wmsPlatform,$debug);
        $action = $this->argument('action');
        if($merchantId == "all"){
            foreach ($merchantArr as $value) {
                $this->runAction($action, $value);
            }
        }else {
            $this->runAction($action, $merchantId);
        }
    }

    public function runAction($action, $merchantId)
    {
        if($action == "create"){
            $this->iwmsFactoryWmsService->createDeliveryOrder($merchantId);
        }else if($action == "report"){
            $this->iwmsFactoryWmsService->sendCreateDeliveryOrderReport($merchantId);
        }
    }
}
