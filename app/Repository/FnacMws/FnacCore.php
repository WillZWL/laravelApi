<?php

namespace App\Repository\FnacMws;

use Config;

class FnacCore
{
    protected $options;
    protected $mwsName = 'fnac-mws';
    protected $errorResponse = [];
    protected $fnacPartnerId;
    protected $fnacShopId;
    protected $fnacKey;
    protected $fnacToken;
    protected $fnacPath = 'api.php/auth';

    function __construct($storeName)
    {
        $this->setConfig();
        $this->setStore($storeName);
        $this->fnacAuthToken();
    }

    public function query($requestXml)
    {
        $xml  = $this->curl($requestXml);

        $data = $this->convert($xml);

        $responseStatus = $data['@attributes']['status'];
        if(isset($data["error"]) || $responseStatus == 'ERROR' || $responseStatus !== 'OK') {
            $this->ErrorResponse = $data["error"];
            return null;
        }

        return $this->prepare($data);
    }

    /**
    * Make request to API url
    * @param $xml string
    * @param $info array - reference for curl status info
    * @return string
    */
    private function curl($xmlFeed, &$info = array())
    {
        $ch = curl_init();
        // Open Curl connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->urlbase . $this->fnacPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlFeed);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $data;
    }

    /**
    * Convert response XML to associative array
    * @param $xml string
    * @return array
    */
    private function convert($xml)
    {
    if ($xml != "") {
        $obj = simplexml_load_string(trim($xml), null, LIBXML_NOCDATA);

        $array = json_decode(json_encode($obj), true);

        if (is_array($array)) {
            $array = $this->sanitize($array);

        }
            return $array;
    }

    return null;
    }

    /**
    * Clear array after convert. Remove empty arrays and change to string
    * @param $arr array
    * @return array
    */
    private function sanitize($arr)
    {
        foreach($arr AS $k => $v) {
            if (is_array($v)) {
                if (count($v) > 0) {
                    $arr[$k] = $this->sanitize($v);
                } else {
                    $arr[$k] = "";
                }
            }
        }

        return $arr;
    }

    public function setConfig()
    {
        $fnacServiceUrl = Config::get($this->mwsName .'.FNAC_SERVICE_URL');
        if (isset($fnacServiceUrl)) {
            $this->urlbase = $fnacServiceUrl;
        } else {
            throw new Exception("Config file does not exist or cannot be read!");
        }
    }

    public function setStore($storeName)
    {
        $store = Config::get($this->mwsName .'.store');
        if (array_key_exists($storeName, $store)) {
            $this->storeName = $storeName;

            if (array_key_exists('partnerId', $store[$storeName])) {
                $this->fnacPartnerId = $store[$storeName]['partnerId'];
            } else {
                $this->log("Partner ID does not exist!", 'Warning');
            }

            if (array_key_exists('shopId', $store[$storeName])) {
                $this->fnacShopId = $store[$storeName]['shopId'];
            } else {
                $this->log("Shop ID does not exist!", 'Warning');
            }

            if (array_key_exists('key', $store[$storeName])) {
                $this->fnacKey = $store[$storeName]['key'];
            } else {
                $this->log("Key ID does not exist!", 'Warning');
            }

            if (array_key_exists('currency', $store[$storeName])) {
                $this->storeCurrency = $store[$storeName]['currency'];
            }

        } else {
            $this->log("Store $storeName does not exist", "Warning");
        }
    }

    public function  getStoreCurrency()
    {
        return $this->storeCurrency;
    }

    public function fnacAuthToken()
    {
        $authRequestXml = <<<XML
<?xml version='1.0' encoding='utf-8'?>
<auth xmlns='http://www.fnac.com/schemas/mp-dialog.xsd'>
    <partner_id>$this->fnacPartnerId</partner_id>
    <shop_id>$this->fnacShopId</shop_id>
    <key>$this->fnacKey</key>
</auth>
XML;

        $response    = $this->curl($authRequestXml);
        $xmlResponse = simplexml_load_string(trim($response));

        $responseStatus = (string) $xmlResponse->attributes()->status;
        if ($responseStatus == 'OK') {
            $this->fnacToken = $xmlResponse->token;
        }
    }

    /**
    * Extract data from response array
    * @param array $data
    * @return null|array
    */
    protected function prepare($data = array())
    {
        if (isset($data["order"])) {
            return $data["order"];
        } else {
            return null;
        }
    }

    /**
    * Fix issue with single result in response
    * @param array $arr
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
