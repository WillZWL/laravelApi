<?php

use Illuminate\Database\Seeder;
use App\Models\IwmsMerchantCourierMapping;

class IwmsMerchantCourierMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "FBA-US",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "150",
            "merchant_courier_name" => "FBA US Ex DG",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "4PX-DHL",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "133",
            "merchant_courier_name" => "DHL",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "4PX-PL-LGS",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "131",
            "merchant_courier_name" => "4pxPL-LGS",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "4PX-US-E-POST",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "144",
            "merchant_courier_name" => "4PX US E-Post",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "SF-EXPRESS",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "157",
            "merchant_courier_name" => "S.F CN Internal",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "SGEMS",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "140",
            "merchant_courier_name" => "SingPost-4PX",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantCourierMapping::create([
            "iwms_courier_code" => "SELF-PICKUP",
            "merchant_id" => "ESG",
            "merchant_courier_id" => "96",
            "merchant_courier_name" => "JZ Logistics Battery",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
    }
}