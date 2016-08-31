<?php

namespace App\Repository;

use Config;

class CommonMws
{
    protected $storeCurrency;
    protected $mwsName;
    protected $urlbase;

    public function __construct()
    {
        $this->setConfig();
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
        // Open Curl connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urlbase.'?'.$queryString);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
    protected function convert($xml)
    {
        if ($xml != '') {
            $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
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
    protected function sanitize($arr)
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
            $this->urlbase = $serviceUrl;
        } else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function curlPostDataToApi($requestParams, $xmlData)
    {
        $apiHeader = array(
            'headers' => array(
            'Content-Type' => 'text/xml; charset=UTF8',
            ),
        );
        $signRequestParams = $this->signature($requestParams);
        $queryString = http_build_query($signRequestParams, '', '&', PHP_QUERY_RFC3986);
        $client = new \GuzzleHttp\Client($apiHeader);
        $response = $client->request('POST', $this->urlbase.'?'.$queryString, ['body' => $xmlData]);
        $returnContent = $response->getBody()->getContents();

        return $returnContent;
    }

    public function curlPostXmlFileToApi($requestParams, $xmlData)
    {
        $signRequestParams = $this->signature($requestParams);
        $queryString = http_build_query($signRequestParams, '', '&', PHP_QUERY_RFC3986);
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $this->urlbase.'?'.$queryString, [
            'multipart' =>[
                [
                    'name'     => 'file',
                    'contents' => $xmlData
                ]
            ]
        ]);
        $returnContent = $response->getBody()->getContents();
        return $returnContent;
    }

    public function getStoreCurrency()
    {
        return $this->storeCurrency;
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
}
