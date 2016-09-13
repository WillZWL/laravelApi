<?php

namespace App\Repository\TangaMws;

use Config;

class TangaCore
{
    protected $options;
    protected $mwsName = 'tanga-mws';
    protected $errorResponse = [];

    public function __construct($storeName)
    {
        $this->setStore($storeName);
        $this->setConfig();
    }

    public function query($requestParams)
    {
        $signRequestParams = $this->signature($requestParams);
        $json = $this->curl($signRequestParams);

        $data = $this->convert($json);

        if (isset($data['error'])) {
            $this->ErrorResponse = $data['error'];

            return null;
        }

        return $this->prepare($data);
    }

    /**
     * Make request to API url.
     *
     * @param $params array
     * @param $info array - reference for curl status info
     *
     * @return string
     */
    protected function curl($params, &$info = array())
    {
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $header[] = 'Accept: application/json';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $this->url . $this->tangaPath . '?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERPWD, $this->userId .":". $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $data;
    }

    public function postDataToAPI($postData, $type = '')
    {

        $header[] = 'Accept: application/json';

        if ($type == 'csv') {
            $header[] = 'Content-Type: text/csv';
        }

        $apiHeader = array(
            'headers' => $header,
        );

        $client = new \GuzzleHttp\Client($apiHeader);
        $response = $client->request('POST', $this->url . $this->tangaPath, ['body' => $postData, 'auth'=>[$this->userId, $this->password]]);
        $responseData = $response->getBody()->getContents();

        $data = $this->convert($responseData);

        return $data;
    }

    /**
     * Convert response jsonData to associative array.
     *
     * @param $jsonData string
     *
     * @return array
     */
    protected function convert($jsonData)
    {
        if ($jsonData != '') {
            $array = json_decode($jsonData, true);
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
        $serviceUrl = Config::get($this->mwsName.'.SERVICE_URL');
        if (isset($serviceUrl)) {
            $this->url = $serviceUrl;
        } else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function setStore($storeName)
    {
        $store = Config::get($this->mwsName.'.store');
        if (array_key_exists($storeName, $store)) {
            $this->storeName = $storeName;

            if (array_key_exists('userId', $store[$storeName])) {
                $this->userId = $store[$storeName]['userId'];
            } else {
                $this->log('User ID does not exist!', 'Warning');
            }

            if (array_key_exists('password', $store[$storeName])) {
                $this->password = $store[$storeName]['password'];
            } else {
                $this->log('password  does not exist!', 'Warning');
            }

            if (array_key_exists('vendorAppId', $store[$storeName])) {
                $this->vendorAppId = $store[$storeName]['vendorAppId'];
            } else {
                $this->log('Vendor APP ID does not exist!', 'Warning');
            }

            if (array_key_exists('currency', $store[$storeName])) {
                $this->storeCurrency = $store[$storeName]['currency'];
            }
        } else {
            $this->log("Store $storeName does not exist", 'Warning');
        }
    }

    public function  getStoreCurrency()
    {
        return $this->storeCurrency;
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
        if (isset($data)) {
            return $data;
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
     * Sign request parameters.
     *
     * @param $params array
     *
     * @return array
     */
    private function signature($params)
    {
        ksort($params);

        return $params;
    }
}
