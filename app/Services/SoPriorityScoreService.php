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
            $date = date('Y-m-d 00:00:00');
            $platformOrderList = So::whereIn('platform_id', $platformList)->where('create_on', '>=', $date)->pluck('platform_order_id')->all();
            $soList = So::whereIn('platform_order_id', $platformOrderList)->pluck('so_no')->all();
            return $soList;
        }

        elseif ($options['platform'] == 'DISPATCH' && $options['merchant'] == 'RING') {
            $platformList = SellingPlatform::where('type', '=', 'DISPATCH')->where('merchant_id', '=', 'RING')->pluck('id')->all();
            $date = date('Y-m-d 00:00:00');
            //$platformOrderList = So::whereIn('platform_id', $platformList)->where('create_on', '>=', $date)->pluck('platform_order_id')->all();
            $soList = So::whereIn('platform_id', $platformList)->where('create_on', '>=', $date)->pluck('so_no')->all();
            $message = "[Total Score updated: ".count($soList)."] ".implode(',',$soList);
            mail("willy.dharman@eservicesgroup.com", "Total priority score updated/inserted log (SBF #10757)", $message, $headers = 'From: willy.dharman@eservicesgroup.com');
            //dd($message);
            return $soList;
        }
        return false;
    }
}
?>