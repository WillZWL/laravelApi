<?php

namespace App\Services\IwmsApi;
use App;

trait IwmsBaseService
{
    private $iwmStatus = array("Success" => "1","Failed" => "2");

    public function getBatchId($name, $requestLog)
    {
        $batchRequestService = App::make("App\Services\BatchRequestService");
        return $batchRequestService->getNewBatch($name, $requestLog);
    }

}