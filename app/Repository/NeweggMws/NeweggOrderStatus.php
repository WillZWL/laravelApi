<?php

namespace App\Repository\NeweggMws;

class NeweggOrderStatus extends NeweggOrderCore
{
    private $orderItemId;
    private $orderItemIds;
    private $reason;
    private $reasonDetail;
    private $shipService;
    private $shipCarrier;
    private $trackingNumber;
    private $_requestParams;
    private $xsdFile = "\OrderMgmt\UpdateOrder\ShipmentRequest.xsd";
    private $resourceUrl = "ordermgmt/orderstatus/orders";

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
         # set the method for curl
        $this->setResourceMethod("PUT");
    }

    public function getOrderStatus()
    {
        $resourceurl = $this->getResourceUrl().'/'.$this->getOrderNumber();
        $result = parent::query($resourceurl, 'GET', $this->getRequestParams());
        return  $result;
    }

    public function setStatusToCanceled()
    {
        $this->_requestParams['Action'] = '1'; //Cancel Order';
        if ($this->getOrderItemId() && intval($this->getOrderItemId()) > 0) {
            $this->_requestParams['OrderItemId'] = $this->getOrderItemId();
        }
        if ($this->getReason()) {
            $this->_requestParams['Reason'] = $this->getReason();
        }
        if ($this->getReasonDetail()) {
            $this->_requestParams['ReasonDetail'] = $this->getReasonDetail();
        }
        // return parent::query($this->_requestParams);

        $result = $this->sendStatus('cancel');
        return $result;
    }

    public function setStatusToShipped()
    {
        $this->_requestParams['Action'] = '2'; //Ship Order;
        if ($this->getShipService()) {
            $this->_requestParams['ShipService'] = $this->getShipService();
        }
        if ($this->getShipCarrier()) {
            $this->_requestParams['ShipCarrier'] = $this->getShipCarrier();
        }
        if ($this->getTrackingNumber()) {
            $this->_requestParams['TrackingNumber'] = $this->getTrackingNumber();
        }
        if ($this->getOrderItemIds() && !empty($this->getOrderItemIds())) {
            $this->_requestParams['OrderItemIds'] = json_encode($this->getOrderItemIds());
        }
        //return parent::query($this->_requestParams);

        $result = $this->sendStatus('shipped');
        return $result;
    }

    public function sendStatus($stat)
    {
        $resourceurl = $this->getResourceUrl().'/'.$this->getOrderNumber();
        $url = $this->getResourceUrl().'/'.$this->getOrderNumber() . '?sellerid='. $this->sellerId;
        $requestBody = $this->getRequestBody($stat);
        $requestParams = $this->getRequestParams();
        
        $sendBody = $this->getResourceMethod() ."\n";
        $sendBody .= $this->urlbase . $url ."\n";
           foreach($this->_requestParams as $key=>$value)
           {
                $sendBody .= $key. ': '.$value ."\n";
           }

        $sendBody .= "\n" . $requestBody;
        $result = parent::query($resourceurl, $this->getResourceMethod(), $requestParams, $requestBody);
        return $result;
    }

    protected function getRequestBody($status)
    {
        $requestXml[] = "<UpdateOrderStatus>";
        if($status == 'shipped'){

            $requestXml[] = "<Action>2</Action>";
            $requestXml[] = "<Value>";
            $requestXml[] = "<![CDATA[";
            $requestXml[] = "<Shipment>";
            $requestXml[] = "<Header>";
        
            $requestXml[] = "<SellerID>" . $this->sellerId . "</SellerID>";
            $requestXml[] = "<SONumber>" . $this->getOrderNumber() . "</SONumber>";
            $requestXml[] = "</Header>";

            $requestXml[] = "<PackageList>";
            $requestXml[] = "<Package>";
            $requestXml[] = "<TrackingNumber>" . $this->getTrackingNumber() . "</TrackingNumber>";
            $requestXml[] = "<ShipCarrier>" . $this->getShipCarrier() . "</ShipCarrier>";
            $requestXml[] = "<ShipService>" . $this->getShipService() . "</ShipService>";

            $requestXml[] = "<ItemList>";
            $itemlist = $this->getOrderItemIds();
            foreach($itemlist as $item){
                $requestXml[] = "<Item>";

                $requestXml[] = "<SellerPartNumber>" . $item['sellersku'] . "</SellerPartNumber>";
                $requestXml[] = "<ShippedQty>" . $item['qty'] . "</ShippedQty>";

                $requestXml[] = "</Item>";               
            }
            $requestXml[] = "</ItemList>";
// $requestXml[] = "<Package>";
            $requestXml[] = "</Package>";
            $requestXml[] = "</PackageList>";
            $requestXml[] = "</Shipment>";
            $requestXml[] = "]]>";
            $requestXml[] = "</Value>";

        }else{ //cancel

        }
            $requestXml[] = "</UpdateOrderStatus>";
    
        return implode("\n", $requestXml);
    }

    // public function setStatusToReadyToShip()
    // {
    //     $this->_requestParams['Action'] = '2' //Ship Order;
    //     if ($this->getDeliveryType()) {
    //         $this->_requestParams['DeliveryType'] = $this->getDeliveryType();
    //     }
    //     if ($this->getShippingProvider()) {
    //         $this->_requestParams['ShippingProvider'] = $this->getShippingProvider();
    //     }
    //     if ($this->getTrackingNumber()) {
    //         $this->_requestParams['TrackingNumber'] = $this->getTrackingNumber();
    //     }
    //     if ($this->getOrderItemIds() && !empty($this->getOrderItemIds())) {
    //         $this->_requestParams['OrderItemIds'] = json_encode($this->getOrderItemIds());
    //     }

    //     return parent::query($this->_requestParams);
    // }

    protected function getRequestParams()
    {
        $this->_requestParams = parent::initRequestParams();
        $requestParams = ["version"=>"304"];
        return $requestParams;
    }

    // protected function getRequestParams()
    // {
    // }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['OrderItems']) && isset($data['Body']['OrderItems']['OrderItem'])) {
            return $data['Body']['OrderItems']['OrderItem'];
        }

        return null;
    }

    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    public function setOrderItemId($value)
    {
        $this->orderItemId = $value;
    }

    public function getOrderItemIds()
    {
        return $this->orderItemIds;
    }

    public function setOrderItemIds($value)
    {
        $this->orderItemIds = $value;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($value)
    {
        $this->reason = $value;
    }

    public function getReasonDetail()
    {
        return $this->reasonDetail;
    }

    public function setReasonDetail($value)
    {
        $this->reasonDetail = $value;
    }

    public function getShipService()
    {
        return $this->shipService;
    }

    public function setShipService($value)
    {
        $this->shipService = $value;
    }

    public function getShipCarrier()
    {
        return $this->shipCarrier;
    }

    public function setShipCarrier($value)
    {
        $this->shipCarrier = $value;
    }

    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber($value)
    {
        $this->trackingNumber = $value;
    }

    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    public function setOrderNumber($value)
    {
        $this->orderNumber = $value;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl($value)
    {
        $this->resourceUrl = $value;
    }

//     public function getOptions()
//     {
//         return $this->options;
//     }

//     public function setOptions($value)
//     {
//         $this->options = $value;
//     }
}
