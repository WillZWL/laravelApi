<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PlatformMarketOrderFufillment extends BaseApiPlatformCommand
{
    /**
      * The name and signature of the console command.
      *
      * @var string
      */
    protected $signature = 'platformMarket:updateShipment  {--api= : amazon or lazada}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the shipment status and tracking number into Platform Seller Central once the order has been shipped';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed error
     */
    public function handle()
    {
        $this->platfromMakert = array("priceminister","newegg","fnac","tanga", "qoo10");
        $this->runPlatformMarketConsoleFunction();
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores){
            try {
                $this->getApiPlatformFactoryService($apiName)->submitOrderFufillment();
            } catch (Exception $e) {
                $header = "From: admin@eservicesgroup.com\r\n";
                $to = "jimmy.gao@eservicesgroup.com, brave.liu@eservicesgroup.com";
                $subject = "Alert, Submit Order Fufillment Shipment Tracking Failed";
                $message = "message: {$e->getMessage()}, Line: {$e->getLine()}";
                mail($to, $subject, $message, $header);
            }
        }
    }
}
