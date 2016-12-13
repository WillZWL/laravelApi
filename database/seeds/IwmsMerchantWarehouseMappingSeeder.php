<?php

use Illuminate\Database\Seeder;
use App\Models\IwmsMerchantWarehouseMapping;

class IwmsMerchantWarehouseMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        IwmsMerchantWarehouseMapping::create([
            "iwms_warehouse_code" => "4PXDG_PL",
            "merchant_id" => "ESG",
            "merchant_warehouse_id" => "4PXDG_PL",
            "merchant_warehouse_name" => "4PX Dong Guan PL",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        IwmsMerchantWarehouseMapping::create([
            "iwms_warehouse_code" => "SZ_BA",
            "merchant_id" => "ESG",
            "merchant_warehouse_id" => "SZBA",
            "merchant_warehouse_name" => "ShenZhen BaoAn",
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
    }
}
