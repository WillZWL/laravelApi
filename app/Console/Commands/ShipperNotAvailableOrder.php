<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShipperNotAvailableOrderService;

class ShipperNotAvailableOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:ShipperNotAvailable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email Alert For New SKU Created';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ShipperNotAvailableOrderService $shipperNotAvailableOrderService)
    {
        parent::__construct();
        $this->shipperNotAvailableOrderService = $shipperNotAvailableOrderService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->shipperNotAvailableOrderService->processOrder();
    }
}
