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
    protected $fnacAction;
    protected $requestXml;
    protected $authKeyWithToken;

    function __construct($storeName)
    {
        $this->setConfig();
        $this->setStore($storeName);
        $this->initFnacAuthToken();
    }

    public function query($requestXml)
    {
        $data = $this->callFnacApi($requestXml);

        if ($data) {

            $responseStatus = $data['@attributes']['status'];

            if ($responseStatus !== 'OK'
                && $responseStatus != "RUNNING"
                && $responseStatus != "ACTIVE"
            ) {
                if (isset($data["error"])) {
                    $this->errorResponse = $data["error"];

                    return false;
                }
            }

            return $this->prepare($data);
        }

        return null;
    }

    public function callFnacApi($requestXml)
    {
        libxml_use_internal_errors(true);
        if ($valid = $this->xmlSchemaValidation($requestXml, $this->getFnacAction())) {
            $xmlResponse  = $this->postXmlFileToApi($requestXml);

            if ($xmlResponse === false) {
                $errorMessage = $this->_xmlErrorToString();

                $this->errorResponse = __LINE__ . " Error in xml response. \r\nResponse: $xmlResponse \r\nXML error: ". $errorMessage;
            } else {
                $data = $this->convert($xmlResponse);
                return $data;
            }
        }
    }

    public function xmlSchemaValidation($requestXml, $fnacAction)
    {
        return true;
        try {
            switch($fnacAction) {
                case 'auth':
                    $schema = "xsd/AuthenticationService.xsd";
                    break;

                case 'offers_update':
                    $schema = "xsd/OffersUpdateService.xsd";
                    break;

                case 'batch_status':
                    $schema = "xsd/BatchStatusService.xsd";
                    break;

                case 'orders_query':
                    $schema = "xsd/OrdersQueryService.xsd";
                    break;

                case 'orders_update':
                    $schema = "xsd/OrdersUpdateService.xsd";
                    break;

                case 'offers_query':
                    $schema = "xsd/OffersQueryService.xsd";
                    break;

                default:
                    return true;
            }

            $dom = new \DOMDocument;
            $dom->loadXML($requestXml);
            libxml_use_internal_errors(true);
            $tplPath = app_path() . '/Repository/FnacMws/';
            $valide = $dom->schemaValidate($tplPath . $schema);
            if( ! $valide)
            {
                $errorMessage = $this->_xmlErrorToString();
                $content = file_get_contents($tplPath . $schema);
                mail('brave.liu@eservicesgroup.com', 'Xml validation failed !', $errorMessage ."\r\n\r\n". serialize($content) . "\r\n\r\n". serialize($requestXml), 'From: admin@eservciesgroup.com');

                return false;
                // throw new \Exception("xml validation failed ! $errorMessage");
            }

            return true;
        } catch(Exception $e) {
            $this->errorResponse .= $e->getMessage();
        }

        return false;
    }

    private function _xmlErrorToString()
    {
        $errorMessage = '';
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            if ($error) {
                switch ($error->level) {
                    case LIBXML_ERR_WARNING:
                        $errorMessage .= "<b>Warning $error->code</b>: ";
                        break;
                    case LIBXML_ERR_ERROR:
                        $errorMessage .= "<b>Error $error->code</b>: ";
                        break;
                    case LIBXML_ERR_FATAL:
                        $errorMessage .= "<b>Fatal Error $error->code</b>: ";
                        break;
                }

                $errorMessage .= trim($error->message) . " on line <b>{$error->line}</b><br>";
            }
        }

        return $errorMessage;
    }

    /**
    * Make request to API url
    * @param $xml string
    * @return string
    */
    private function postXmlFileToApi($xmlFeed)
    {
        $request = $this->urlbase . $this->getFnacAction();
        $error = [];
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('POST', $request, ['body' => $xmlFeed]);
            $responseXml = $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $error[] = $e->getFile(). " ".__LINE__." networking error. ";
            $error[] = "Request: {$request}";
            $error[] = "message: {$e->getMessage()}. ";
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            # 400-level errors
            $error[] = $e->getFile(). " ".__LINE__." client 400-level error. ";
            $error[] = "Request: {$request}";
            if($e->hasResponse()) {
                $error["response"] = $e->getResponse()->getBody()->getContents();
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {

            $error[] = $e->getFile(). " ".__LINE__." server 500-level error. ";
            $error[] = "Request: {$request}";
            if($e->hasResponse()) {
                $error[] = "status code: {$e->getResponse()->getStatusCode()}. Response: {$e->getResponse()->getBody()->getContents()}";
            } else {
                $error[] = "message: {$e->getMessage()}. ";
            }
        } catch (\Exception  $e) {
            $error[] = $e->getFile(). " ".__LINE__." other error. ";
            $error[] = "Request: {$request}";
            $error[] = "message: {$e->getMessage()}. ";
        }
        if ($error) {
            $error['request'] = $request;
            $error['requestBody'] = $xmlFeed;
            $errorMessage = serialize($error);
            mail('brave.liu@eservicesgroup.com', 'Calling Fnac API Failed', $errorMessage, 'From: admin@eservciesgroup.com');
            return false;
        }
        return $responseXml;
    }

    public function getFnacAction()
    {
        return $this->fnacAction;
    }

    public function setFnacAction($fnacAction = '')
    {
        return $this->fnacAction = $fnacAction;
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
        $fnacServiceUrl = Config::get($this->mwsName . '.SERVICE_URL');
        if (isset($fnacServiceUrl)) {
            $this->urlbase = $fnacServiceUrl;
        } else {
            throw new Exception("Config file does not exist or cannot be read!");
        }
    }

    public function setStore($storeName)
    {
        $store = Config::get($this->mwsName . '.store');
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

    public function initFnacAuthToken()
    {
        if (!$this->fnacToken) {
            $this->setFnacAction('auth');
            $this->setAuthRequestXml();

            $data = $this->callFnacApi($this->getRequestXml());

            $responseStatus = $data['@attributes']['status'];
            if ($responseStatus == 'OK') {
                $this->fnacToken = $data['token'];
            }
        }

        if (isset($this->fnacToken)) {
            $this->setAuthKeyWithToken();
        }
    }

    private function setAuthRequestXml()
    {
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<auth xmlns="http://www.fnac.com/schemas/mp-dialog.xsd">';
        $xmlData .=    '<partner_id>'. $this->fnacPartnerId .'</partner_id>';
        $xmlData .=    '<shop_id>'. $this->fnacShopId .'</shop_id>';
        $xmlData .=    '<key>'. $this->fnacKey .'</key>';
        $xmlData .= '</auth>';

        $this->requestXml = $xmlData;
    }

    protected function getRequestXml()
    {
        return $this->requestXml;
    }

    public function getAuthKeyWithToken()
    {
        return $this->authKeyWithToken;
    }

    public function setAuthKeyWithToken()
    {
        $authKeyWithToken = '
            partner_id="'. $this->fnacPartnerId .'"
            shop_id="'. $this->fnacShopId .'"
            key="'. $this->fnacKey .'"
            token="'. $this->fnacToken .'"
            xmlns="http://www.fnac.com/schemas/mp-dialog.xsd"
        ';

        $this->authKeyWithToken = $authKeyWithToken;
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
        } else if (isset($data['offer'])) {
            return $data['offer'];
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
