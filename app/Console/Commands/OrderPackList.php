<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderPackListService;

class OrderPackList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:packList';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate order invoice and delivery note';

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
        $orderPackListService = new OrderPackListService();
        $orderPackListService->processPackList();
    }
}

