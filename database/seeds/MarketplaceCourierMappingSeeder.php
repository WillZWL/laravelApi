<?php

use Illuminate\Database\Seeder;

class MarketplaceCourierMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mappings = [
            [
                'courier_id' => 5,
                'courier_code' => 'DHL',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 133,
                'courier_code' => 'DHL',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 33,
                'courier_code' => 'UPS',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'UPS',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 144,
                'courier_code' => '4PX US E-Post',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'USPS',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 135,
                'courier_code' => 'PostNL Registered Mail (Allow External Batt & Powerbank)',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'PostNL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 21,
                'courier_code' => 'QUANTIUM',
                'marketplace' => 'TANGA',
                'marketplace_courier_name' => 'QUANTIUM',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 5,
                'courier_code' => 'DHL',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 8,
                'courier_code' => 'Fedex+DPD UK (for Acc use only)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DPD-UK',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 10,
                'courier_code' => 'TNT+DPD_NL',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DPD',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 45,
                'courier_code' => 'TNT - Express',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'TNT',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 46,
                'courier_code' => 'DHL Plus',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 50,
                'courier_code' => 'Globalmail-Parcel direct Expedited Cross Border (PLE)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 51,
                'courier_code' => 'TNT - Economy',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'TNT',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 53,
                'courier_code' => 'Globalmail - Packet Plus (not- allow built in)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 54,
                'courier_code' => 'DHL Courier',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 58,
                'courier_code' => 'TNT courier',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'TNT',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 60,
                'courier_code' => 'Globalmail - Packet Plus (allow built in)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 67,
                'courier_code' => 'DPD NL',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DPD',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 81,
                'courier_code' => 'TNT+DPD UK (for Acc use only)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DPD-UK',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 88,
                'courier_code' => 'Asendia-Courier EU (only for Acc)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'CHRONOPOST',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 91,
                'courier_code' => 'RPX',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'Autre',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 92,
                'courier_code' => 'YFH DHL (For ACC only)',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DHL',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 94,
                'courier_code' => 'A2B',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'DPD',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
            [
                'courier_id' => 98,
                'courier_code' => 'Asendia',
                'marketplace' => 'PRICEMINISTER',
                'marketplace_courier_name' => 'CHRONOPOST',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ],
        ];
        DB::table('marketplace_courier_mappings')->insert($mappings);
    }
}
