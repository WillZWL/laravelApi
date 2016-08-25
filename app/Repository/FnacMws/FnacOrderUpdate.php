<?php

namespace App\Repository\FnacMws;

class FnacOrderUpdate extends FnacOrderCore
{
    private $action;
    private $itemIds;
    private $trackingNumber;
    private $shipping_method = '21';

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
        $this->orderId = $this->getOrderId();
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_update
    partner_id="$this->fnacPartnerId"
    shop_id="$this->fnacShopId"
    key="$this->fnacKey"
    token="$this->fnacToken"
    xmlns="http://www.fnac.com/schemas/mp-dialog.xsd"
    >
    <order order_id="$this->orderId" action="update">
XML;
    foreach ($this->itemIds as $itemId) {
        $xml .= <<<XML
        <order_detail>
            <order_detail_id>$itemId</order_detail_id>
            <action><![CDATA[Shipped]]></action>
            </shipping_method>$this->shipping_method</shipping_method>
            <tracking_number><![CDATA[$this->trackingNumber]]></tracking_number>
        </order_detail>
XML;
    }

        $xml .= <<<XML
    </order>
</orders_update>
XML;

        $this->requestXml = $xml;
    }

    public function setOrderItemIds($itemIds)
    {
        $this->itemIds = $itemIds;
    }

    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    protected function prepare($data = [])
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }
}
