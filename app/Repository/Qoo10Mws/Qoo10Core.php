<?php

namespace App\Repository\Qoo10Mws;

use Config;

class Qoo10Core
{
    protected $options;
    protected $mwsName = 'qoo10-mws';
    protected $errorResponse = [];
    protected $storeCurrency;
    // protected $requestXml;
    protected $sellerAuthKey;

    function __construct($storeName)
    {
        $this->initMwsName();
        $this->setConfig();
        $this->setStore($storeName);

        $this->initSellerAuthKey();
    }

    public function query($action, $resourceMethod = "GET", $requestParams = [], $requestBody = [])
    {
        $resourceUrl = $this->qoo10ActionUrl($action);

        $responseDataXml = $this->curl($resourceUrl, strtoupper($resourceMethod), $requestParams, $requestBody);
        $response = $this->convert($responseDataXml);

        // Response ResultCode = 0 is Success
        if ($response['ResultCode'] == 0) {
            return $response;
        }

        return false;
    }

    public function qoo10ActionUrl($action)
    {
        $qoo10ActionUrl = [
            'getSellerAuthKey'                  => 'Certification.api/CreateCertificationKey',
            'getShippingInfo'                   => 'ShippingBasicService.api/GetShippingInfo',
            'getShippingAndClaimInfoByOrderNo'  => 'ShippingBasicService.api/GetShippingAndClaimInfoByOrderNo',
            'getItemDetailInfo'                 => 'GoodsBasicService.api/GetItemDetailInfo',
            'setGoodsPrice'                     => 'GoodsOrderService.api/SetGoodsPrice',
            'setGoodsInventory'                 => 'GoodsOrderService.api/SetGoodsInventory',
        ];

        if (isset($qoo10ActionUrl[$action])) {
            return "/GMKT.INC.Front.OpenApiService/". $qoo10ActionUrl[$action];
        }

        return false;
    }

    public function initSellerAuthKey()
    {
        if (! isset($this->options['sellerAuthKey'])) {
            $requestParams = array(
                'user_id' => $this->options['userId'],
                'pwd' => $this->options['password'],
                'key' => $this->options['key'],
            );

            $response = $this->query('getSellerAuthKey', "GET", $requestParams);

            if (isset($response['ResultObject'])
            ) {
                $this->sellerAuthKey = $response['ResultObject'];
            }

        } else {
            $this->sellerAuthKey = $this->options['sellerAuthKey'];
        }
    }

    private function curl($resourceUrl, $resourceMethod, $requestParams= [], $requestBody = "")
    {
        if ($this->sellerAuthKey) {
            $requiredParam["key"] = $this->sellerAuthKey;
            $requestParams = array_merge($requestParams, $requiredParam);
        }

        $queryString = http_build_query($requestParams, '', '&', PHP_QUERY_RFC3986);
        $request = "{$this->urlbase}{$resourceUrl}?".$queryString;

        $requestOption = [];

        if ($requestBody) {
          $requestOption["body"] = $requestBody;
        }

        $requestOption["http_errors"] = TRUE;

        $client = new \GuzzleHttp\Client();
        $response = $client->request($resourceMethod, $request, $requestOption);
        $responseDataXml = $response->getBody()->getContents();

        $requestData['url'] = $request;
        $requestData['body'] = $requestBody;

        return $responseDataXml;
    }

    public function setStore($storeName)
    {
        $store = Config::get($this->mwsName . '.store');
        if (array_key_exists($storeName, $store)) {
            $this->storeName = $storeName;

            if (array_key_exists('userId', $store[$storeName])) {
                $this->options['userId'] = $store[$storeName]['userId'];
            } else {
                $this->log("User ID does not exist!", 'Warning');
            }

            if (array_key_exists('password', $store[$storeName])) {
                $this->options['password'] = $store[$storeName]['password'];
            } else {
                $this->log("Password does not exist!", 'Warning');
            }

            if (array_key_exists('key', $store[$storeName])) {
                $this->options['key'] = $store[$storeName]['key'];
            } else {
                $this->log("Key does not exist!", 'Warning');
            }

            if (array_key_exists('sellerAuthKey', $store[$storeName])) {
                $this->options['sellerAuthKey'] = $store[$storeName]['sellerAuthKey'];
            }
            //  else {
            //     $this->log("Key does not exist!", 'Warning');
            // }

            if (array_key_exists('currency', $store[$storeName])) {
                $this->storeCurrency = $store[$storeName]['currency'];
            }

        } else {
            $this->log("Store $storeName does not exist", "Warning");
        }
    }

    public function setConfig()
    {
        $qoo10ServiceUrl = Config::get($this->mwsName . '.SERVICE_URL');
        if (isset($qoo10ServiceUrl)) {
            $this->urlbase = $qoo10ServiceUrl;
        } else {
            throw new Exception("Config file does not exist or cannot be read!");
        }
    }

    public function  getStoreCurrency()
    {
        return $this->storeCurrency;
    }

    /**
     * Convert response XML to associative array.
     *
     * @param $xml string
     *
     * @return array
     */
    private function convert($xml)
    {
       $xmlParser = xml_parser_create();
       if(xml_parse($xmlParser,$xml,true)){
            $obj = simplexml_load_string(trim($xml), null, LIBXML_NOCDATA);
            $array = json_decode(json_encode($obj), true);
            if (is_array($array)) {
                $array = $this->sanitize($array);

                return $array;
            }
        }
        return null;
    }

    /**
     * Clear array after convert. Remove empty arrays and change to string.
     *
     * @param $arr array
     *
     * @return array
     */
    private function sanitize($arr)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if (count($v) > 0) {
                    $arr[$k] = $this->sanitize($v);
                } else {
                    $arr[$k] = '';
                }
            }
        }

        return $arr;
    }

    //ADD SANDBOX FUNCTION
    private function initMwsName()
    {
        $sandbox = 'sandbox.'.$this->mwsName;
        if (empty(Config::get($sandbox))) {
            return;
        }
        if (\App::environment('local') && env('APP_DEBUG')) {
            $this->mwsName = $sandbox;
        }
    }
}
