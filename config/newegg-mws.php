<?php

// For NewEgg Config
return [
    'store' => [
        'BCNEWEGGUS' => [
            'name' => 'Brand Connect NewEgg',
            'userId' => 'portalsandbox06+A2A6@gmail.com',
            'password' => 'aCC21salES5k',
            'currency'=>'USD',
            'sellerId' => "A2A6",
            'neweggServiceUrl' => "https://api.newegg.com/marketplace/contentmgmt/",
            'apiKey' => "7172b5061a6e3085e01e665681b1fba2",
            'secretKey' => "88d82cb8-3c7e-49b4-b921-abf1d1f9f9a8"
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
    'NEWEGG_SERVICE_URL' => 'https://api.newegg.com/marketplace/contentmgmt/',
    'muteLog' => true
];