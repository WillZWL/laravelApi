<?php

use Illuminate\Database\Seeder;

class MarketStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // add amazon stores.
        $amazonStores = Config::get('amazon-mws.store');

        $convertedAmazonStores = [];
        foreach ($amazonStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'merchantId' => $store['merchantId'],
                    'marketplaceId' => $store['marketplaceId'],
                    'keyId' => $store['keyId'],
                    'secretKey' => $store['secretKey'],
                    'amazonServiceUrl' => $store['amazonServiceUrl'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        $lazadaStores = Config::get('lazada-mws.store');

        foreach ($lazadaStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'apiToken' => $store['apiToken'],
                    'lazadaServiceUrl' => $store['lazadaServiceUrl'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        DB::table('market_stores')->insert($convertedAmazonStores);
    }
}
