<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\OrderPackListService;

class OrderPackListJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $packListNo;
    protected $soNo;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($packListNo, $soNo)
    {
        $this->packListNo = $packListNo;
        $this->soNo = $soNo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $soNoList = [];
        if (is_array($this->soNo)) {
            $soNoList = $this->soNo;
        } else if(is_string($this->soNo)){
            $soNoList[] = $this->soNo;
        }
        $orderPackListService = new OrderPackListService();
        $orderPackListService->moveSuccessOrder($this->packListNo, $soNoList);
    }
}
