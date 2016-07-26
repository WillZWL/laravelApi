<?php

namespace App\Repository\LazadaMws;

use Config;

class LazadaCore
{
  private $options;
  private $storeCurrency;
  protected $errorResponse = array();

  public function __construct($storeName)
  {
      $this->setConfig();
      $this->setStore($storeName);
  }

  public function query($requestParams)
  {
      $signRequestParams = $this->signature($requestParams);
      $xml = $this->curl($signRequestParams);
      $data = $this->convert($xml);

      if(isset($data["Head"]) && isset($data["Head"]["ErrorCode"])) {
        $this->ErrorResponse = $data["Head"];
        return null;
      }
      return $this->prepare($data);
  }

    /**
     * Return error message for last API call
     * @return string
     */
  public function errorMessage()
  {
      if (isset($this->ErrorResponse) && is_array($this->ErrorResponse) && isset($this->ErrorResponse["ErrorCode"])) {
        return $this->ErrorResponse["ErrorMessage"];
      }
      return "";
  }

  /**
   * Return error code for last API call.
   * Return integer number
   * @return string
   */
  public function errorCode()
  {
      if (isset($this->ErrorResponse) && is_array($this->ErrorResponse) && isset($this->ErrorResponse["ErrorCode"])) {
        return $this->ErrorResponse["ErrorCode"];
      }
      return "";
  }
  /**
   * Extract data from response array
   * @param array $data
   * @return null|array
   */
  protected function prepare($data = array())
  {
      if (isset($data["Body"])) {
        return $data["Body"];
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
  /**
   * Init common params
   * @return array
   */
  protected function initRequestParams()
  {
      $now = new \DateTime();
      $requestParams=array(
          "UserID"=> $this->options["userId"],
          "Version"=> "1.0",
          "Format"=> "XML",
          "Timestamp"=> $now->format(\DateTime::ISO8601)
      );
      return $requestParams;
  }

  /**
   * Sign request parameters
   * @param $params array
   * @return array
   */
  private function signature($params)
  {
      ksort($params);
      $strToSign = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
      $signature = rawurlencode(hash_hmac('sha256', $strToSign, $this->options['apiToken'], false));
      $params['Signature'] = $signature;
      return $params;
  }

  /**
   * Make request to API url
   * @param $params array
   * @param $info array - reference for curl status info
   * @return string
   */
  private function curl($params, &$info = array())
  {
      $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
      // Open Curl connection
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->urlbase . "?" . $queryString);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
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
      $lazadaServiceUrl = Config::get('lazada-mws.LAZADA_SERVICE_URL');
      if (isset($lazadaServiceUrl)) {
          $this->urlbase = $lazadaServiceUrl;
      } else {
          throw new Exception("Config file does not exist or cannot be read!");
      }
  }

  public function setStore($s)
  {
      $store = Config::get('lazada-mws.store');
      if (array_key_exists($s, $store)) {
          $this->storeName = $s;
          if (array_key_exists('userId', $store[$s])) {
              $this->options['userId'] = $store[$s]['userId'];
          } else {
              $this->log("User ID is missing!", 'Warning');
          }
          if (array_key_exists('apiToken', $store[$s])) {
              $this->options['apiToken'] = $store[$s]['apiToken'];
          } else {
              $this->log("Access API Key is missing!", 'Warning');
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

  public function  getStoreCurrency()
  {
      return $this->storeCurrency;
  }
}