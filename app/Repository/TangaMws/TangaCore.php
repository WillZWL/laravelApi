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
        $this->setConfig();
        $this->setStore($storeName);
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
    private function curl($params, &$info = array())
    {
        $user_email = $params['email'];
        $user_password = $params['password'];
        unset($params['email']);
        unset($params['password']);

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $ch = curl_init();
        // Open Curl connection

        $header = [
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url.$this->tangaPath.'?'.$queryString);
        curl_setopt($ch, CURLOPT_USERPWD, "{$user_email}:{$user_password}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * Convert response jsonData to associative array.
     *
     * @param $jsonData string
     *
     * @return array
     */
    private function convert($jsonData)
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
        $tangaServiceUrl = Config::get($this->mwsName.'.TANGA_SERVICE_URL');
        if (isset($tangaServiceUrl)) {
            $this->url = $tangaServiceUrl;
        } else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function setStore($storeName)
    {
        $store = Config::get($this->mwsName.'.store');
        if (array_key_exists($storeName, $store)) {
            $this->storeName = $storeName;

            if (array_key_exists('email', $store[$storeName])) {
                $this->options['email'] = $store[$storeName]['email'];
            } else {
                $this->log('Email Address does not exist!', 'Warning');
            }

            if (array_key_exists('password', $store[$storeName])) {
                $this->options['password'] = $store[$storeName]['password'];
            } else {
                $this->log('password  does not exist!', 'Warning');
            }

            if (array_key_exists('vendorAppId', $store[$storeName])) {
                $this->options['vendorAppId'] = $store[$storeName]['vendorAppId'];
            } else {
                $this->log('Vendor APP ID does not exist!', 'Warning');
            }
        } else {
            $this->log("Store $storeName does not exist", 'Warning');
        }
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
            print_r($data);

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
     * Init common params.
     *
     * @return array
     */
    protected function initRequestParams()
    {
        $requestParams = [
            'email' => $this->options['email'],
            'password' => $this->options['password'],
        ];

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

        return $params;
    }
}
