<?php

namespace App\Services;
use App\Models\BatchRequest;

class BatchRequestService
{
    public function __construct()
    {
    }

    public function getNewBatch($name, $requestLog = null)
    {
        $batch = new BatchRequest();
        $batch->name = $name;
        $batch->status = 'N';
        if ($requestLog)
            $batch->request_log = $requestLog;
        $batch->save();
        return $batch;
    }
}
