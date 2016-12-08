<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IwmsApi\IwmsFactoryWmsService;

class IwmscreateDeliveryOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Iwms:createDeliveryOrder  {--wms= : fourpx}';

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
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService();
        $this->iwmsFactoryWmsService->createDeliveryOrder();
    }
}
