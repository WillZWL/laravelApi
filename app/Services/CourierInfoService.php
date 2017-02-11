<?php

namespace App\Services;

use App\Models\CourierInfo;

class CourierInfoService
{
    public function all()
    {
        return CourierInfo::where('status', 1)->get();
    }
}
