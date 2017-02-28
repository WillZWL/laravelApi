<?php

namespace App\Console\Commands;

use App\Repository\OrderRepository;
use App\Services\CourierFeedService;
use Illuminate\Console\Command;

class GenerateCourierFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:courier_feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate courier feed for logistic team';

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
        $orderRepo = new OrderRepository();
        $courierFeedService = new CourierFeedService($orderRepo);
        try {
            $courierFeedService->getCourierFeed();
        } catch (\Exception $e) {
            mail('handy.hon@eservicesgroup.com', 'Generate Courier Feed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
        }
    }
}
