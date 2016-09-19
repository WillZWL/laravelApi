<?php

namespace App\Repository\NeweggMws;

use Config;

class NeweggCore
{
    private $options;
    protected $mwsName = 'newegg-mws';
    private $orderCurrency;
    private $storeCurrency;
    private $countryCode;
    private $storeName = "";
    protected $errorResponse = array();
    private $itAlertEmail = "it@eservicesgroup.net";
    private $userAlertEmail = array();

    public function __construct($storeName)
    {
        $this->initMwsName();
        $this->setConfig();
        $this->setStore($storeName);
    }

    public function query($resourceUrl="", $resourceMethod="GET", $requestParams=array(), $requestBody=array())
    {
        $dataResponse = null;
        $requestInfo = $error = array();
        if($resourceUrl) {
            
            $curlResponse = $this->curl($resourceUrl, strtoupper($resourceMethod), $requestParams, $requestBody);


            if($curlResponse["status"]) {
                $json = $curlResponse["json"];
                $data = $this->convertJsonToArr($json);
            } else {
                $requestInfo = $curlResponse["requestInfo"];
                $error = $curlResponse["error"];
            }
        }

        if(!$error) {
            $dataResponse = $this->prepare($data);
        }

        $returnArr = ["data"=>$dataResponse, "requestInfo"=>$requestInfo, "error"=>$error];
        return $returnArr;
    }

    /**
     * Make request to API url.
     *
     * @param [array] $requestParams query to build URL
     * @param [string] $requestBody request XML to send
     *
     * @return [bool] $status returns TRUE if no error
     * @return [object] $json returns json from Newegg if no curl error
     * @return [array] $requestInfo cURL information of the connection for debugging
     * @return [array] $error cURL/Newegg error messages
     *
     * @link http://docs.guzzlephp.org/en/latest/quickstart.html#using-responses Documentation of GuzzleHttp
     *
     */
    private function curl($resourceUrl, $resourceMethod, $requestParams=array(), $requestBody="")
    {
        $response = $json = "";
        $error = array();
        $status = FALSE;
        $requiredParam["sellerid"] = $this->options['sellerId'];
        if($requestParams)
            $requestParams = array_merge($requestParams, $requiredParam);
        $queryString = http_build_query($requestParams, '', '&', PHP_QUERY_RFC3986);
        $requestInfo["request"] = $request = "{$this->urlbase}{$resourceUrl}?".$queryString;
        
        $requestOption["headers"] = $this->initAuthParams();
        $requestOption["body"] = $requestBody;
        $requestOption["http_errors"] = TRUE;
        $requestInfo["request"] = $requestOption;

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request($resourceMethod, $request, $requestOption);
            $json = $response->getBody()->getContents();
            $status = TRUE;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $error[] = "NeweggCore.php ".__LINE__." networking error. ";
            $error[] = "Request: {$request}";
            $error[] = "message: {$e->getMessage()}. ";
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            # 400-level errors
            $error[] = "NeweggCore.php ".__LINE__." client 400-level error. ";
            $error[] = "Request: {$request}";
            if($e->hasResponse()) {
                $error[] = "status code: {$e->getResponse()->getStatusCode()}. Response: {$e->getResponse()->getBody()->getContents()}";
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {

            $error[] = "NeweggCore.php ".__LINE__." server 500-level error. ";
            $error[] = "Request: {$request}";
            if($e->hasResponse()) {
                $error[] = "status code: {$e->getResponse()->getStatusCode()}. Response: {$e->getResponse()->getBody()->getContents()}";
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\Exception  $e) {
            $error[] = "NeweggCore.php ".__LINE__." other error. ";
            $error[] = "Request: {$request}";
            $error[] = "message: {$e->getMessage()}. ";
        } 

        # use requestInfo and error for debugging
        $data = ["status"=>$status, "json"=>$json, "requestInfo"=>$requestInfo, "error"=>$error];
        return $data;
    }

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
        if (isset($data['ResponseBody'])) {
            return $data['ResponseBody'];
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
          'Accept' => 'application/json'
        );

        return $authParams;
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
            $obj = simplexml_load_string($xml);
            $array = json_decode(json_encode($obj), true);
            if (is_array($array)) {
                $array = $this->sanitize($array);
                return $array;
            }
        }

        return null;
    }

    /**
     * Convert response JSON to associative array.
     *
     * @param $json string
     *
     * @return array
     */
    private function convertJsonToArr($json)
    {
        if (trim($json)) {
           return json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true);
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
            } elseif ($v !== "") {
                $arr[$k] = $v;
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
            if (array_key_exists('orderCurrency', $store[$s])) {
                $this->orderCurrency = $store[$s]['orderCurrency'];
            }
            if (array_key_exists('storeCurrency', $store[$s])) {
                $this->storeCurrency = $store[$s]['storeCurrency'];
            }

            if (array_key_exists('countryCode', $store[$s])) {
                $this->countryCode = $store[$s]['countryCode'];
            }
            if (array_key_exists('userAlertEmail', $store[$s])) {
                $this->userAlertEmail = $store[$s]['userAlertEmail'];
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

    public function sendMail($to, $subject, $message, $from="admin@eservicesgroup.com")
    {
        $headers = "From: {$from}\r\n";
        mail($to, $subject, $message, $headers);
    }

    public function getOrderCurrency()
    {
        return $this->orderCurrency;
    }

    public function getStoreCurrency()
    {
        return $this->storeCurrency;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function getStoreName()
    {
        return $this->storeName;
    }

    public function getItAlertEmail()
    {
        return $this->itAlertEmail;
    }

    public function getUserAlertEmail()
    {
        return $this->userAlertEmail;
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
