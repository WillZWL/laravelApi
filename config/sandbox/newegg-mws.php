<?php

// For Newegg Config
return [
    'store' => [
        // 'BCNEWEGGUS' => [
        //     'name' => 'Brand Connect Newegg',
        //     'userId' => 'portal.sandbox.41@gmail.com',
        //     'password' => '8YbV6gDu',
        //     'currency'=>'USD',
        //     'country' => 'USA',
        //     'sellerId' => "ABVF",
        //     'userAlertEmail' => array(),
        //     'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
        //     'apiKey' => "bc3e1dfb4b535de8761427c771ddd37c",
        //     'secretKey' => "cc4743c4-be1e-466b-9b49-d346524df6fe"
        // ],

// PING NOTE THIS IS LIVE, DO NOT CHANGE ORDRS
        'BCNEWEGGUS' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'newegg@eservicesgroup.com',
            'password' => '8YbV6gDu',
            'orderCurrency'=>'USD',
            'storeCurrency'=>'USD',
            'sellerId' => "AAJM",
            'userAlertEmail' => array(),
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "4863bc9e0421f7591b8c17999eb07bf1",
            'secretKey' => "ce43dfdf-a480-4037-ac67-ef12e64cebf7"
        ],
    ],

    // Default service URL test
    'NEWEGG_SERVICE_URL' => 'https://api.newegg.com/marketplace/',
    'muteLog' => true
];