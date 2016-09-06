<?php

// For Newegg Config
return [
    'store' => [
        'BCNEWEGGUS' => [
            'name' => 'Brand Connect Newegg',
            'userId' => 'portal.sandbox.41@gmail.com',
            'password' => '8YbV6gDu',
            'currency'=>'USD',
            'sellerId' => "ABVF",
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/",
            'apiKey' => "bc3e1dfb4b535de8761427c771ddd37c",
            'secretKey' => "cc4743c4-be1e-466b-9b49-d346524df6fe"
        ],
        'BCNEWEGGAU' => [
            'name' => 'Brand Connect NewEgg',
            'userId' => '',
            'password' => '',
            'currency'=>'AUD',
        ],
        'BCNEWEGGNZ' => [
            'name' => 'Brand Connect NewEgg',
            'userId' => '',
            'password' => '',
            'currency'=>'NZD',
        ]
    ],

    // Default service URL
    'NEWEGG_SERVICE_URL' => 'https://api.newegg.com/marketplace/',
];