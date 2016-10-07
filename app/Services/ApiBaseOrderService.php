<?php 

namespace App\Services;

/**
* 
*/
class ApiBaseOrderService extends PlatformMarketConstService
{   
    use ApiBaseService;
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