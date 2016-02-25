<?php

return [
	'store' => [
		'AMZUS' => [
			'merchantId' => 'A31GWB3ZYX0LWJ',
			'marketplaceId' => 'ATVPDKIKX0DER',
			'keyId' => 'AKIAJV4DKBKNNPD4EXEA', // Access Key ID.
			'secretKey' => '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',
			'amazonServiceUrl' => 'https://mws.amazonservices.com/',
            'platform' => 'US',
		],
        'AMZCA' => [
            'merchantId' => 'A31GWB3ZYX0LWJ',
            'marketplaceId' => 'A2EUQ1WTGCTBG2',
            'keyId' => 'AKIAJV4DKBKNNPD4EXEA',
            'secretKey' => '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',
            'amazonServiceUrl' => 'https://mws.amazonservices.ca/',
            'platform' =>'CA',
        ]
	],

	// Default service URL
	'AMAZON_SERVICE_URL' => 'https://mws.amazonservices.com/',

	'muteLog' => true
];
