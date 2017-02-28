<?php

namespace App\Repository;


use App\Models\So;

class OrderRepository
{
    public function noCourierFeedOrders()
    {
        return So::whereCourierFeed(0)->whereNotNull('pick_list_no')->get();
    }
}