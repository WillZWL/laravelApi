<?php

namespace App\Services\IwmsApi;

use Config;

class IwmsCoreService
{
    protected $errorResponse = array();
    protected $accessToken;
    protected $wmsPlatform;

    public function __construct($wmsPlatform ="", $debug = 0)
    {
        $this->initIwmsConfig($wmsPlatform, $debug);
    }

    public function curlIwmsApi($action, $requestBody = array(), $merchantId = null)
    {
        $clientBody = null;
        $requestOption["headers"] = array(
            'Content-Type' => 'application/json; charset=UTF8',
            'Authorization' => 'Bearer '.$this->accessToken,
          );
        $client = new \GuzzleHttp\Client();
        if(!empty($requestBody)){
            $requestOption['body'] = json_encode($requestBody);
        }
        $requestUrl = $this->getRequestUrl($action, $merchantId);
        $response = $client->request('POST',$requestUrl,$requestOption);
        $returnContent = $response->getBody()->getContents();
        // if error
        return $this->prepare($returnContent);
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
        return $data;
    }

    public function getRequestUrl($action, $merchantId = null)
    {
        $wmsPlatform = $this->wmsPlatform ? $this->wmsPlatform : "";
        if(!empty($merchantId)){
            return $this->urlbase . $action ."/". $wmsPlatform ."/".$merchantId."?debug=". $this->debug;
        }else{
            return $this->urlbase . $action ."/". $wmsPlatform ."?debug=". $this->debug;
        }
    }

    public function initIwmsConfig($wmsPlatform = "", $debug)
    {
        $iwmsConfig = Config::get('iwms');
        $this->wmsPlatform = $wmsPlatform;
        $this->debug = $debug;
        $this->urlbase = $iwmsConfig["url"];
        $this->accessToken = $iwmsConfig["accessToken"];
    }

    public function getWmsPlatformByWarehouse($warehouse)
    {
        $wmsPlatform = "";
        switch ($warehouse) {
            case 'ES_HK':
                    $wmsPlatform = "CENTRALLINK";
                break;

            default:
                $wmsPlatform = "4PX";
                break;
        }
        return $wmsPlatform;
    }

}
