<?php

use Illuminate\Database\Seeder;

class DeliveryTypeMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mapping = [
            [
                'delivery_type' => 'STD',
                'courier_type' => 'POSTAGE',
                'quotation_type' => 'builtin_postage',
            ],
            [
                'delivery_type' => 'STD',
                'courier_type' => 'POSTAGE',
                'quotation_type' => 'external_postage',
            ],
            [
                'delivery_type' => 'EXPED',
                'courier_type' => 'COURIER',
                'quotation_type' => 'courier',
            ],
            [
                'delivery_type' => 'EXP',
                'courier_type' => 'COURIER_EXP',
                'quotation_type' => 'courier_exp',
            ],
            [
                'delivery_type' => 'STD',
                'courier_type' => 'POSTAGE',
                'quotation_type' => 'acc_builtin_postage',
            ],
            [
                'delivery_type' => 'STD',
                'courier_type' => 'POSTAGE',
                'quotation_type' => 'acc_external_postage',
            ],
            [
                'delivery_type' => 'EXPED',
                'courier_type' => 'COURIER',
                'quotation_type' => 'acc_courier',
            ],
            [
                'delivery_type' => 'EXP',
                'courier_type' => 'COURIER_EXP',
                'quotation_type' => 'acc_courier_exp',
            ],
            [
                'delivery_type' => 'FBA',
                'courier_type' => 'FBA',
                'quotation_type' => 'acc_fba',
            ],
        ];

        DB::table('delivery_type_mappings')->insert($mapping);
    }
}
