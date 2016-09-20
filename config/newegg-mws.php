<?php

// For Newegg Config, SBF #10337
return [
    'store' => [
        'BCNEWEGGUS' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'newegg@eservicesgroup.com',
            'password' => 'aCC21salES5k',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'USD', // for product listing
            'countryCode' => 'USA', // Newegg's country code
            'sellerId' => "AAJM",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "4863bc9e0421f7591b8c17999eb07bf1",
            'secretKey' => "ce43dfdf-a480-4037-ac67-ef12e64cebf7"
        ],
        'BCNEWEGGNZ' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'newegg@eservicesgroup.com',
            'password' => 'aCC21salES5k',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'NZD', // for product listing
            'countryCode' => 'NZL', // Newegg's country code
            'sellerId' => "AAJM",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "4863bc9e0421f7591b8c17999eb07bf1",
            'secretKey' => "ce43dfdf-a480-4037-ac67-ef12e64cebf7"
        ],
        'BCNEWEGGAU' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'newegg@eservicesgroup.com',
            'password' => 'aCC21salES5k',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'AUD', // for product listing
            'countryCode' => 'AUS', // Newegg's country code
            'sellerId' => "AAJM",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "4863bc9e0421f7591b8c17999eb07bf1",
            'secretKey' => "ce43dfdf-a480-4037-ac67-ef12e64cebf7"
        ],
    ],

    // Default service URL test
    'NEWEGG_SERVICE_URL' => 'https://api.newegg.com/marketplace/',
    'muteLog' => true
];