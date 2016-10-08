<?php

namespace App\Services;

/**
*
*/
class ApiBaseOrderService extends PlatformMarketConstService
{
    private $schedule;

    public function getSchedule()
    {
        return $this->schedule;
    }

    public function setSchedule($value)
    {
        $this->schedule = $value;
    }
}
