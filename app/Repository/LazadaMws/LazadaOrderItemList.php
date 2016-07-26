<?php

namespace App\Repository\LazadaMws;

class LazadaOrderItemList extends LazadaOrderCore
{

  public function __construct($store)
  {
    parent::__construct($store);
  }

  public function fetchOrderItemList()
  {
      $this->requestParams=$this->getRequestParams();
      return parent::query($this->requestParams);
  }

  protected  function getRequestParams()
  {
      $requestParams = parent::initRequestParams();
      $requestParams["Action"] = "GetOrderItems";
      if($this->getOrderId() && intval($this->getOrderId()) > 0) {
         $requestParams["OrderId"] = $this->getOrderId();
      }
      return $requestParams;
  }

  protected function prepare($data = array())
  {
    if (isset($data["Body"]) && isset($data["Body"]["OrderItems"]) && isset($data["Body"]["OrderItems"]["OrderItem"])) {
      return parent::fix($data["Body"]["OrderItems"]["OrderItem"]);
    }
    return null;
  }

}