<?php

return [
    'store' => [
        'BCLAZADAMY' => [
            'name'=>'Brands Connect Lazada',
            'userId' => 'lazadamy@brandsconnect.net', 
            'apiToken' => '8742efca4d071f92cf07e2c4ff53c4cd0d0725b7',
            'lazadaServiceUrl' => 'https://api.sellercenter.lazada.com.my/',
            'currency'=>'MYR',
        ],
        'BCLAZADASG' => [
            'name'=>'NEW API Lazada',
            'userId' => 'it@eservicesgroup.net', 
            'apiToken' => 'NisV_3DZnFmApqSqGhTETZLS3_ZpqwokTwU1RGzOlCsc842Hbi9xMexD',
            'lazadaServiceUrl' => 'https://api.sgsbx.ali-lazada.com/',
            'currency'=>'SGD',
        ],
    ],

    // Default service URL
    'LAZADA_SERVICE_URL' => 'https://api.sgsbx.ali-lazada.com/',
    'muteLog' => true
];
