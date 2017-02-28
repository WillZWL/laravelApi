<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IwmsApi\Order\IwmsFulfillmentOrderService;

class IwmsFulfillmentOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Iwms:PushFulfillmentOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push ESG Fulfillment Order to Iwms';

    private $orderService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(IwmsFulfillmentOrderService $orderService)
    {
        $this->orderService = $orderService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->orderService->pushFulfillmentOrder();
    }
}
