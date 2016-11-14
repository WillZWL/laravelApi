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
        $convertedAmazonStores = [];

        // add amazon stores.
        $amazonStores = Config::get('amazon-mws.store');
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

        // add allegro stores
        $allegroStores = Config::get('allegro-mws.store');
        foreach ($allegroStores as $platformId => $store) {
            $convertedAllegroStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        //add linio stores
        $linioStores = Config::get('linio-mws.store');
        foreach ($linioStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add mercadolibre stores
        $mercadolibreStores = Config::get('mercadolibre-mws.store');
        foreach ($mercadolibreStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add new egg stores
        $neweggStores = Config::get('newegg-mws.store');
        foreach ($neweggStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['storeCurrency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add wish stores
        $wishStores = Config::get('wish-mws.store');
        foreach ($wishStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'email' => $store['email'],
                    'password' => $store['password'],
                    'client_id' => $store['client_id'],
                    'client_secret' => $store['client_secret'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add ebay stores
        $ebayStores = Config::get('ebay-mws.store');
        foreach ($ebayStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }


        // add lazada stores.
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

        // add fnac stores.
        $fnacStores = Config::get('fnac-mws.store');
        foreach ($fnacStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'partnerId' => $store['partnerId'],
                    'shopId' => $store['shopId'],
                    'key' => $store['key'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add priceminister stores.
        $priceministerStores = Config::get('priceminister-mws.store');
        foreach ($priceministerStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add tanga stores.
        $tangaStores = Config::get('tanga-mws.store');
        foreach ($tangaStores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'name' => $store['name'],
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                    'vendorAppId' => $store['vendorAppId'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        // add qoo10 stores.
        $qoo10Stores = Config::get('qoo10-mws.store');
        foreach ($qoo10Stores as $platformId => $store) {
            $convertedAmazonStores[] = [
                'store_name' => substr($platformId, 0, 2),
                'store_code' => substr($platformId, 0, 2),
                'marketplace' => substr($platformId, 2, -2),
                'country' => substr($platformId, -2),
                'currency' => $store['currency'],
                'credentials' => json_encode([
                    'userId' => $store['userId'],
                    'password' => $store['password'],
                    'key' => $store['key'],
                    'sellerAuthKey' => $store['sellerAuthKey'],
                ]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }

        foreach ($convertedAmazonStores as $convertedAmazonStore) {
            DB::table('stores')->updateOrInsert(
                [
                    'store_code' => $convertedAmazonStore['store_code'],
                    'marketplace' => $convertedAmazonStore['marketplace'],
                    'country' => $convertedAmazonStore['country'],
                ],
                $convertedAmazonStore
            );
        }
    }
}
