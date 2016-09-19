<?php

// For Newegg Config
return [
    'store' => [
        'BCNEWEGGUS' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'portal.sandbox.41@gmail.com',
            'password' => '8YbV6gDu',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'USD', // for product listing
            'countryCode' => 'USA', // Newegg's country code
            'sellerId' => "ABVF",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "bc3e1dfb4b535de8761427c771ddd37c",
            'secretKey' => "cc4743c4-be1e-466b-9b49-d346524df6fe"
        ],
        'BCNEWEGGNZ' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'portal.sandbox.41@gmail.com',
            'password' => '8YbV6gDu',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'NZD', // for product listing
            'countryCode' => 'NZL', // Newegg's country code
            'sellerId' => "ABVF",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "bc3e1dfb4b535de8761427c771ddd37c",
            'secretKey' => "cc4743c4-be1e-466b-9b49-d346524df6fe"
        ],
        'BCNEWEGGAU' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'portal.sandbox.41@gmail.com',
            'password' => '8YbV6gDu',
            'orderCurrency'=>'USD',  // currently all Newegg orders in USD
            'storeCurrency'=>'AUD', // for product listing
            'countryCode' => 'AUS', // Newegg's country code
            'sellerId' => "ABVF",
            'userAlertEmail' => ["newegg@brandsconnect.net"],
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "bc3e1dfb4b535de8761427c771ddd37c",
            'secretKey' => "cc4743c4-be1e-466b-9b49-d346524df6fe"
        ],
    ],

    // Default service URL test
    'NEWEGG_SERVICE_URL' => 'https://api.newegg.com/marketplace/',
    'muteLog' => true
];