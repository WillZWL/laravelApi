<?php

namespace App\Repository\LazadaMws;

use Config;

class LazadaCore
{
    private $options;
    protected $mwsName = 'lazada-mws';
    private $storeCurrency;
    protected $errorResponse = array();

    public function __construct($storeName)
    {
        $this->initMwsName();
        $this->setConfig();
        $this->setStore($storeName);
    }

    public function query($requestParams)
    {
        $signRequestParams = $this->signature($requestParams);
        $xml = $this->curl($signRequestParams);
        $data = $this->convert($xml);

        if (isset($data['Head']) && isset($data['Head']['ErrorCode'])) {
            $this->ErrorResponse = $data['Head'];
            $message = $this->storeName." ErrorCode ".$data['Head']['ErrorCode']." message ".$data["Head"]["ErrorMessage"];
            mail("jimmy.gao@eservicesgroup.com", "lazada error message", $message, $headers = 'From: admin@shop.eservciesgroup.com');
            return null;
        }

        return $this->prepare($data);
    }

    public function curlPostDataToApi($requestParams, $xmlData = null)
    {
        $clientBody = null;
        $requestOption["headers"] = array(
            'Content-Type' => 'text/xml; charset=UTF8',
          );
        $signRequestParams = $this->signature($requestParams);
        $queryString = http_build_query($signRequestParams, '', '&', PHP_QUERY_RFC3986);
        $client = new \GuzzleHttp\Client();
        if($xmlData){
            $requestOption['body'] = $xmlData;
        }
        $response = $client->request('POST', $this->urlbase.'?'.$queryString,$requestOption);
        $returnContent = $response->getBody()->getContents();

        return $returnContent;
    }

    /**
     * Return error message for last API call.
     *
     * @return string
     */
    public function errorMessage()
    {
        if (isset($this->ErrorResponse) && is_array($this->ErrorResponse) && isset($this->ErrorResponse['ErrorCode'])) {
            return $this->ErrorResponse['ErrorMessage'];
        }

        return '';
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
    protected function initRequestParams()
    {
        date_default_timezone_set("UTC");
        $now = new \DateTime();
        $requestParams = array(
          'UserID' => $this->options['userId'],
          'Version' => '1.0',
          'Format' => 'XML',
          'Timestamp' => $now->format(\DateTime::ISO8601),
        );

        return $requestParams;
    }

    /**
     * Sign request parameters.
     *
     * @param $params array
     *
     * @return array
     */
    private function signature($params)
    {
        ksort($params);
        // URL encode the params.
        $encoded = array();
        foreach ($params as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        // Concatenate the sorted and URL encoded params into a string.
        $concatenated = implode('&', $encoded);
        $params['Signature'] = rawurlencode(hash_hmac('sha256', $concatenated, $this->options['apiToken'], false));

        return $params;
    }

    /**
     * Make request to API url.
     *
     * @param $params array
     * @param $info array - reference for curl status info
     *
     * @return string
     */
    private function curl($params, &$info = array())
    {
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
      // Open Curl connection
        $ch = curl_init();
        //print_r($this->urlbase.'?'.$queryString);
        curl_setopt($ch, CURLOPT_URL, $this->urlbase.'?'.$queryString);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

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
       $xmlParser = xml_parser_create();   
       if(xml_parse($xmlParser,$xml,true)){   
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
            }
        }

        return $arr;
    }

    public function setConfig()
    {
        $lazadaServiceUrl = Config::get($this->mwsName.'.LAZADA_SERVICE_URL');
        if (isset($lazadaServiceUrl)) {
            $this->urlbase = $lazadaServiceUrl;
        } else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function setStore($s)
    {
        $store = Config::get($this->mwsName.'.store');
        if (array_key_exists($s, $store)) {
            $this->storeName = $s;
            if (array_key_exists('userId', $store[$s])) {
                $this->options['userId'] = $store[$s]['userId'];
            } else {
                $this->log('User ID is missing!', 'Warning');
            }
            if (array_key_exists('apiToken', $store[$s])) {
                $this->options['apiToken'] = $store[$s]['apiToken'];
            } else {
                $this->log('Access API Key is missing!', 'Warning');
            }
            if (array_key_exists('currency', $store[$s])) {
                $this->storeCurrency = $store[$s]['currency'];
            }
          // Overwrite Lazada service url if specified
            if (array_key_exists('lazadaServiceUrl', $store[$s])) {
                $this->urlbase = $store[$s]['lazadaServiceUrl'];
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
