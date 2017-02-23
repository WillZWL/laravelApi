<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Laravel CORS
     |--------------------------------------------------------------------------
     |

     | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
     | to accept any value.
     |
     */
    //"url" => "http://iwms.dev/api/wms/",
    "url" => env('IWMS_API_URL', "http://iwms.eservicesgroup.com/api/"),
    "accessToken" => env('IWMS_ACCESS_TOKEN', "bf7238837236f822c88126e571a730a2ad733dddf718f8c148786096915516864515191dcfdd0ce3"),
];

