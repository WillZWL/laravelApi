<?php

namespace App\Services;

use App\Models\MarketplaceCourierMapping;
use App\Models\CourierInfo;

class MarketplaceCourierMappingService
{

    public function __construct()
    {

    }

    public function store($data)
    {
        $nums = MarketplaceCourierMapping::where('courier_id', $data['courier_id'])
                                           ->where('marketplace', $data['marketplace'])
                                           ->where('marketplace_courier_name', trim($data['marketplace_courier_name']))
                                           ->count();
        if ($nums > 0) {
            return ['status' => false, 'message' => 'This Mapping Exist, Cannot Duplication Add'];
        } else {
            $courierInfo = CourierInfo::find($data['courier_id']);
            if ($courierInfo) {
                $mapping = New MarketplaceCourierMapping;
                $mapping->courier_id = $data['courier_id'];
                $mapping->courier_code = $courierInfo->courier_name;
                $mapping->marketplace = $data['marketplace'];
                $mapping->marketplace_courier_name = trim($data['marketplace_courier_name']);
                $result = $mapping->save();
                if ($result) {
                    return ['status' => true, 'message' => 'Add Successfully'];
                } else {
                    return ['status' => false, 'message' => 'Create Failed'];
                }

            } else {
                return ['status'=>false, 'message' => 'Invalid Courier ID'];
            }
        }
    }

    public function update($data)
    {
        try {
            $id = $data['id'];
            $mapping = MarketplaceCourierMapping::updateOrCreate(['id' => $id], $data);
            return ['status' => true, 'message' => ''];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Update Error'];
        }
    }

    public function getAllMappings($requestData = [])
    {
        $query = MarketplaceCourierMapping::where('status', 1);
        if (isset($requestData['marketplace'])) {
            $query->where('marketplace', $requestData['marketplace']);
        }
        if (isset($requestData['courier_id'])) {
            $query->where('courier_id', $requestData['courier_id']);
        }
        return $query->get();
    }
}
