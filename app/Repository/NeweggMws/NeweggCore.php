<?php

namespace App\Repository\NeweggMws;

use Config;

class NeweggCore
{
    private $options;
    protected $mwsName = 'newegg-mws';
    private $currency;
    private $countryCode;
    protected $errorResponse = array();
    private $itAlertEmail = "it@eservicesgroup.net";

    public function __construct($storeName)
    {
        $this->initMwsName();
        $this->setConfig();
        $this->setStore($storeName);
    }

    public function query($resourceUrl="", $resourceMethod="GET", $requestParams=array(), $requestBody=array())
    {
        $requestInfo = $error = array();
        if($resourceUrl) {
            
            $curlResponse = $this->curl($resourceUrl, strtoupper($resourceMethod), $requestParams, $requestBody);

            if($curlResponse["status"]) {
                $xml = $curlResponse["xml"];
                $data = $this->convert($xml);
            } else {
// HANDLE ERROR HERE
                $requestInfo = $curlResponse["requestInfo"];
                $error = $curlResponse["error"];
            }
        }
        // if (isset($data['Head']) && isset($data['Head']['ErrorCode'])) {
        //     $this->ErrorResponse = $data['Head'];

        //     return null;
        // }

        if($error) {
var_dump(__LINE__. ' neweggcore');
dd($error);
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
        dd($data);
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
     *
     * http://docs.guzzlephp.org/en/latest/quickstart.html#using-responses
     *
     * https://docs.aws.amazon.com/aws-sdk-php/v2/api/namespace-Guzzle.Http.html
     */
    private function curl($resourceUrl, $resourceMethod, $requestParams=array(), $requestBody="")
    {
        $response = $xml = "";
        $error = array();
        $status = FALSE;
        $requiredParam["sellerid"] = ''; //$this->options['sellerId'];
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
            $xml = $response->getBody()->getContents();
            $status = TRUE;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $error[] = "NeweggCore.php ".__LINE__." networking error. ";
            $error[] = "message: {$e->getMessage()}. ";
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            # 400-level errors
            $error[] = "NeweggCore.php ".__LINE__." client 400-level error. ";
            if($e->hasResponse()) {
                $error[] = "status code: {$e->getResponse()->getStatusCode()}. Response: {$e->getResponse()->getBody()->getContents()}";
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {

            $error[] = "NeweggCore.php ".__LINE__." server 500-level error. ";
            if($e->hasResponse()) {
                $error[] = "status code: {$e->getResponse()->getStatusCode()}. Response: {$e->getResponse()->getBody()->getContents()}";
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\Exception  $e) {
            $error[] = "NeweggCore.php ".__LINE__." other error. ";
            $error[] = "message: {$e->getMessage()}. ";
        } 

        $data = ["status"=>$status, "xml"=>$xml, "requestInfo"=>$requestInfo, "error"=>$error];
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
            if (array_key_exists('currency', $store[$s])) {
                $this->currency = $store[$s]['currency'];
            }
            if (array_key_exists('country', $store[$s])) {
                $this->countryCode = $store[$s]['country'];
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

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function getItAlertEmail()
    {
        return $this->itAlertEmail;
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
