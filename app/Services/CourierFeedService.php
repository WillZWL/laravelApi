<?php

namespace App\Services;

use App\Models\CourierInfo;
use App\Repository\OrderRepository;
use GuzzleHttp\Client;

class CourierFeedService
{
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getCourierFeed()
    {
        $orders = $this->orderRepository->noCourierFeedOrders();
        $ordersGroupByPickListNo = $orders->groupBy('pick_list_no');

        $client = new Client();
        foreach ($ordersGroupByPickListNo as $pickListNo => $orderList) {
            $ordersGroupByCourier = $orderList->groupBy('esg_quotation_courier_id');
            foreach ($ordersGroupByCourier as $courier => $orders) {
                $orderNumberCollection = $orders->pluck('so_no')->toArray();
                $response = $client->request(
                    'GET',
                    'http://admincentre.eservicesgroup.com/simpleintegration/courier_feed/generate_courier_feed',
                    [
                        'auth' => ['courier_feed', '2swE8uRj7v'],
                        'query' => [
                            'token' => '2CgKkoqoTbVaH69LCq}FLnN',
                            'courier' => $courier,
                            'so_no_list' => $orderNumberCollection,
                        ],
                    ]
                );

                $courierFileName = (string)$response->getBody();
                $this->moveCourierFeedToVanguard($courierFileName, $pickListNo, $courier);

                \DB::connection('mysql_esg')->table('so')
                    ->whereIn('so_no', $orderNumberCollection)
                    ->update(['courier_feed' => 1]);
            }
        }
    }

    private function moveCourierFeedToVanguard($filename, $pickListNo, $courier)
    {
        $source = '/var/data/shop.eservicesgroup.com/courier/' . $filename;

        $courier = CourierInfo::find($courier);
        $dateTime = new \DateTime();
        $destFolder = 'pick-list/' . $dateTime->format('Y-m-d') . '/'.$pickListNo . '/'.$courier->courier_name;
        \Storage::makeDirectory($destFolder);
        copy($source, storage_path().'/app/'.$destFolder.'/'.$filename);

        return true;
    }
}