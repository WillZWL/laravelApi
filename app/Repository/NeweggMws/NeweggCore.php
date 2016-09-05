<?php

namespace App\Repository\NeweggMws;

use Config;

class NeweggCore
{
    private $options;
    protected $mwsName = 'newegg-mws';
    private $storeCurrency;
    protected $errorResponse = array();

    public function __construct($storeName)
    {
        $this->initMwsName();
        $this->setConfig();
        $this->setStore($storeName);
    }

    public function query($resourceUrl="", $resourceMethod="GET", $requestParams=array(), $requestBody=array())
    {
        if($resourceUrl) {
                $params["sellerid"] = $this->options['sellerId'];
            if($requestParams)
                $params = array_merge($params, $requestParams);
            // $requestParams["version"] = '304';

            // $signRequestParams = $this->signature($requestParams);
            $xml = $this->curl($resourceUrl, strtoupper($resourceMethod), $params, $requestBody);
            $data = $this->convert($xml);
    echo "<pre>";print($data);die();
        }
        if (isset($data['Head']) && isset($data['Head']['ErrorCode'])) {
            $this->ErrorResponse = $data['Head'];

            return null;
        }

        return $this->prepare($data);
    }

    // public function curlPostDataToApi($requestParams, $xmlData)
    // {
    //     $apiHeader = array(
    //       'headers' => array(
    //         'Content-Type' => 'text/xml; charset=UTF8',
    //       ),
    //     );
    //     // $signRequestParams = $this->signature($requestParams);
    //     $queryString = http_build_query($signRequestParams, '', '&', PHP_QUERY_RFC3986);
    //     $client = new \GuzzleHttp\Client($apiHeader);
    //     $response = $client->request('POST', $this->urlbase.'?'.$queryString, ['body' => $xmlData]);
    //     $returnContent = $response->getBody()->getContents();

    //     return $returnContent;
    // }

    // /**
    //  * Return error message for last API call.
    //  *
    //  * @return string
    //  */
    // public function errorMessage()
    // {
    //     if (isset($this->ErrorResponse) && is_array($this->ErrorResponse) && isset($this->ErrorResponse['ErrorCode'])) {
    //         return $this->ErrorResponse['ErrorMessage'];
    //     }

    //     return '';
    // }

    /**
     * Return error code for last API call.
     * Return integer number.
     *
     * @return string
     */
    public function errorCode()
    {
        if (isset($this->ErrorResponse) && is_array($this->ErrorResponse) && isset($this->ErrorResponse['ErrorCode'])) {
            return $this->ErrorResponse['ErrorCode'];
        }

        return '';
    }

    /**
     * Extract data from response array.
     *
     * @param array $data
     *
     * @return null|array
     */
    protected function prepare($data = array())
    {
        if (isset($data['Body'])) {
            return $data['Body'];
        } else {
            return null;
        }
    }

    /**
     * Fix issue with single result in response.
     *
     * @param array $arr
     *
     * @return array
     */
    protected function fix($arr = array())
    {
        if (isset($arr[0])) {
            return $arr;
        }

        return array(0 => $arr);
    }
    /**
     * Init common params.
     *
     * @return array
     */
    protected function initAuthParams()
    {
        $authParams = array(
          'Authorization' => $this->options["apiKey"],
          'SecretKey' => $this->options["secretKey"],
          'Content-Type' => 'application/xml',
          'Accept' => 'application/xml'
        );

        return $authParams;
    }


    /**
     * Make request to API url.
     *
     * @param $params array
     * @param $info array - reference for curl status info
     *
     * @return string
     */
    private function curl($resourceUrl, $resourceMethod, $requestParams, $requestBody)
    {
        $response = "";
        $queryString = http_build_query($requestParams, '', '&', PHP_QUERY_RFC3986);
        $request = "https://api.newegg.com/marketplac/{$resourceUrl}?".$queryString;
        
        $headerArray = $this->initAuthParams();

        $client = new \GuzzleHttp\Client();
        $requestOption["headers"] = $this->initAuthParams();
        $requestOption["body"] = $requestBody;
        $requestOption["http_errors"] = true;
        try {
            $response = $client->request('PUT', $request, $requestOption);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {

            echo 'Uh oh! ' . $e->getMessage();
            echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . "\n";
            echo 'HTTP request: ' . $e->getRequest() . "\n";
            echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . "\n";
            echo 'HTTP response: ' . $e->getResponse() . "\n";

        } catch (\GuzzleHttp\Exception\CurlException $e) {

            echo 'Uh oh 2! ' . $e->getMessage();
            // echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . "\n";
            // echo 'HTTP request: ' . $e->getRequest() . "\n";
            // echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . "\n";
            // echo 'HTTP response: ' . $e->getResponse() . "\n";
        }
echo "<pre>";
var_dump($response);
die();
        return $data;


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
        if ($xml != '') {
            var_dump($xml);die();
            $obj = simplexml_load_string($xml);
            $array = json_decode(json_encode($obj), true);
            echo "<pre>";var_dump($array);die();
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

    public function setConfig()
    {
        $neweggServiceUrl = Config::get($this->mwsName.'.NEWEGG_SERVICE_URL');
        if (isset($neweggServiceUrl)) {
            $this->urlbase = $neweggServiceUrl;
        } else {
            throw new \Exception('Config file does not exist or cannot be read!');
        }
    }

    public function setStore($s)
    {
        $store = Config::get($this->mwsName.'.store');

        if (array_key_exists($s, $store)) {
            $this->storeName = $s;
            if (array_key_exists('sellerId', $store[$s])) {
                $this->options['sellerId'] = $store[$s]['sellerId'];
            } else {
                $this->log('Seller ID is missing!', 'Warning');
            }
            if (array_key_exists('apiKey', $store[$s])) {
                $this->options['apiKey'] = $store[$s]['apiKey'];
            } else {
                $this->log('Access API Key is missing!', 'Warning');
            }
            if (array_key_exists('secretKey', $store[$s])) {
                $this->options['secretKey'] = $store[$s]['secretKey'];
            } else {
                $this->log('Access Secret Key is missing!', 'Warning');
            }
            if (array_key_exists('currency', $store[$s])) {
                $this->storeCurrency = $store[$s]['currency'];
            }
          // Overwrite Newegg service url if specified
            if (array_key_exists('neweggServiceUrl', $store[$s])) {
                $this->urlbase = $store[$s]['neweggServiceUrl'];
            }
        } else {
            throw new \Exception("Store $s does not exist!");
            $this->log("Store $s does not exist!", 'Warning');
        }
    }

    public function getStoreCurrency()
    {
        return $this->storeCurrency;
    }

    // //ADD SANDBOX FUNCTION
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
