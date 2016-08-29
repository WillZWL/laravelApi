<?php

namespace App\Repository\FnacMws;

class FnacOrderUpdate extends FnacOrderCore
{
    private $fnacOrderIds;
    private $orderAction;
    private $orderDetailAction;
    private $trackingNumber;
    private $courierName;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function updateTrackingNumber()
    {
        $this->setOrdersUpdatePath();
        $this->setRequestTrackingNumberXml();

        return parent::query($this->getRequestXml());
    }

    protected function setRequestTrackingNumberXml()
    {
        if ($this->fnacToken) {

            $AuthKeyWithToken = $this->getAuthKeyWithToken();
            $shippingMethod = '21';
            $orderId = $this->getOrderId();
            $trackingNumber = $this->getTrackingNumber();
            $courierName = $this->getCourierName();
            $orderAction = $this->getOrderAction();
            $orderDetailAction = $this->getOrderDetailAction();

            $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_update $AuthKeyWithToken>
    <order order_id="$orderId" action="$orderAction">
        <order_detail>
            <action>$orderDetailAction</action>
            </shipping_method>$shippingMethod</shipping_method>
            <tracking_number>$trackingNumber</tracking_number>
            <tracking_company>$courierName</tracking_company>
        </order_detail>
    </order>
</orders_update>
XML;

            $this->requestXml = $xml;
        }
    }

    public function updateFnacOrdersStatus()
    {
        $this->setOrdersUpdatePath();
        $this->setRequestUpdateOrdersStatusXml();

        return parent::query($this->getRequestXml());
    }

    protected function setRequestUpdateOrdersStatusXml()
    {
        if ($this->fnacToken) {
            $AuthKeyWithToken = $this->getAuthKeyWithToken();
            $orderIds = $this->getFnacOrderIds();
            $orderAction = $this->getOrderAction();
            $orderDetailAction = $this->getOrderDetailAction();

            $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_update $AuthKeyWithToken>
XML;
        foreach ($orderIds as $orderId) {
            $xml .= <<<XML
    <order order_id="$orderId" action="$orderAction">
        <order_detail>
            <action>$orderDetailAction</action>
        </order_detail>
    </order>
XML;
        }

            $xml .= <<<XML
</orders_update>
XML;

            $this->requestXml = $xml;
        }
    }

    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }

    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    public function getCourierName()
    {
        return $this->courierName;
    }

    public function setCourierName($courierName)
    {
        $this->courierName = $courierName;
    }

    public function getFnacOrderIds()
    {
        return $this->fnacOrderIds;
    }

    public function setFnacOrderIds($fnacOrderIds)
    {
        $this->fnacOrderIds = $fnacOrderIds;
    }

    public function getOrderAction()
    {
        return $this->orderAction;
    }

    public function setOrderAction($orderAction)
    {
        $this->orderAction = $orderAction;
    }

    public function getOrderDetailAction()
    {
        return $this->orderDetailAction;
    }

    public function setOrderDetailAction($orderDetailAction)
    {
        $this->orderDetailAction = $orderDetailAction;
    }

    protected function prepare($data = [])
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }
}
