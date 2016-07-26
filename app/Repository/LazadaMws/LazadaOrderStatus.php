<?php

namespace App\Repository\LazadaMws;

class LazadaOrderStatus extends LazadaOrderCore
{
    private $orderItemId;
    private $orderItemIds;
    private $reason;
    private $reasonDetail;
    private $deliveryType;
    private $shippingProvider;
    private $trackingNumber;
    private $_requestParams;

    public function __construct($store) 
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function setStatusToCanceled()
    {
        $this->_requestParams["Action"] = "SetStatusToCanceled";
        if($this->getReason()) 
        $this->_requestParams["Reason"] = $this->getReason();
        if($this->getReasonDetail()) 
        $this->_requestParams["ReasonDetail"] = $this->getReasonDetail();
        return parent::query($this->_requestParams);
    }

    public function setStatusToPackedByMarketplace()
    {
        $this->_requestParams["Action"] = "SetStatusToPackedByMarketplace";
        if($this->getDeliveryType()) 
        $this->_requestParams["DeliveryType"] = $this->getDeliveryType();
        if($this->getShippingProvider()) 
        $this->_requestParams["ShippingProvider"] = $this->getShippingProvider();
        return parent::query($this->_requestParams);   
    }

    public function setStatusToReadyToShip()
    {
        $this->_requestParams["Action"] = "SetStatusToReadyToShip";
        if($this->getDeliveryType()) 
        $this->_requestParams["DeliveryType"] = $this->getDeliveryType();
        if($this->getShippingProvider()) 
        $this->_requestParams["ShippingProvider"] = $this->getShippingProvider();
        if($this->getTrackingNumber()) 
        $this->_requestParams["TrackingNumber"] = $this->getTrackingNumber();
        return parent::query($this->_requestParams);
    }

    public function setStatusToShipped()
    {
        $this->_requestParams["Action"] = "SetStatusToShipped";
        return parent::query($this->_requestParams);
    }

    public function setStatusToFailedDelivery($value)
    {
        $this->_requestParams["Action"] = "SetStatusToFailedDelivery";
        if($this->getReason()) 
        $this->_requestParams["Reason"] = $this->getReason();
        if($this->getReasonDetail()) 
        $this->_requestParams["ReasonDetail"] = $this->getReasonDetail();
        return parent::query($this->_requestParams);
    }

    public function setStatusToDelivered($value)
    {
        $this->_requestParams["Action"] = "SetStatusToDelivered";
        return parent::query($this->_requestParams);
    }

    protected  function getRequestParams()
    {
        $this->_requestParams = parent::initRequestParams();
        if($this->getOrderItemId() && intval($this->getOrderItemId()) > 0) {
          $this->_requestParams["OrderItemId"] = $this->getOrderItemId();
        }
        if($this->getOrderItemIds() && !empty($this->getOrderItemIds())) {
          $this->_requestParams["OrderItemIds"] = $this->OrderItemIds();
        }
    }

    protected function prepare($data = array())
    {
        if (isset($data["Body"]) && isset($data["Body"]["OrderItems"]) && isset($data["Body"]["OrderItems"]["OrderItem"])) {
          return $data["Body"]["OrderItems"]["OrderItem"];
        }
        return null;
    }

    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    public function setOrderItemId($value)
    {
        $this->orderItemId=$value;
    }

    public function getOrderItemIds()
    {
        return $this->orderItemIds;
    }

    public function setOrderItemIds($value)
    {
        $this->orderItemIds=$value;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($value)
    {
        $this->reason=$value;
    }

    public function getReasonDetail()
    {
        return $this->reasonDetail;
    }

    public function setReasonDetail($value)
    {
        $this->reasonDetail=$value;
    }

    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    public function setDeliveryType($value)
    {
        $this->deliveryType=$value;
    }

    public function getShippingProvider()
    {
        return $this->shippingProvider;
    }

    public function setShippingProvider($value)
    {
        $this->shippingProvider=$value;
    }

    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber($value)
    {
        $this->trackingNumber=$value;
    }
}