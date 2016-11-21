<?php

namespace App\Services;

use App\Models\SellingPlatform;
use App\Models\So;
use App\Models\SoPriorityScore;

class SoPriorityScoreService
{
    public function setSoScore($options)
    {
        $orderList = $this->getOrderList($options);
        $score = $options['score'];
        if ($orderList) {
            foreach ($orderList as $order) {
                SoPriorityScore::updateOrCreate(['so_no' => $order],
                    ['so_no' => $order, 'score' => $score, 'status' => '1']);
            }
        }
    }

    public function getOrderList($options = [])
    {
        if ($options['platform'] == 'AMAZON' && $options['merchant'] == '3DOODLER') {
            $platformList = SellingPlatform::where('marketplace', '=', '3DAMAZON')->where('merchant_id', '=', '3DOODLER')->pluck('id')->all();
            $platformOrderList = So::whereIn('platform_id', $platformList)->pluck('platform_order_id')->all();
            $soList = So::whereIn('platform_order_id', $platformOrderList)->pluck('so_no')->all();
            return $soList;
        }
        return false;
    }
}
?>