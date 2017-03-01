<?php

namespace App\Services;

use App\Models\CourierInfo;
use Cache;

class CourierInfoService
{
    public function all()
    {
        return CourierInfo::where('status', 1)->get();
    }

    public function getCouriers()
    {
        return Cache::store('file')->get('couriers', function() {
            $couriers = CourierInfo::all();
            foreach ($couriers as $courier) {
                $couriersArr[$courier->courier_id] = $courier->courier_name;
            }
            Cache::store('file')->add('couriers', $couriersArr, 60*24);
        });
    }

    public function getCourierNameById($id = '')
    {
        $courierName = '';
        $couriersArr = $this->getCouriers();
        if ($couriersArr) {
            if (array_key_exists($id, $couriersArr)) {
                $courierName = $couriersArr[$id];
            }
        }
        return $courierName;
    }
}
