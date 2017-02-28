<?php

namespace App\Console\Commands;

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

    }
}
