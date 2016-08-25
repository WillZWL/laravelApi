<?php

namespace App\Repository\FnacMws;

class FnacOrder extends FnacOrderCore
{
    private $action;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchOrder()
    {
        $this->setOrdersQueryPath();
        $this->setRequestXml();

        return parent::query($this->getRequestXml());
    }

    protected function setRequestXml()
    {
        $this->orderId = $this->getOrderId();
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_query
    partner_id="$this->fnacPartnerId"
    shop_id="$this->fnacShopId"
    key="$this->fnacKey"
    token="$this->fnacToken"
    xmlns="http://www.fnac.com/schemas/mp-dialog.xsd"
    >
    <orders_fnac_id>
        <order_fnac_id><![CDATA[$this->orderId]]></order_fnac_id>
    </orders_fnac_id>
</orders_query>
XML;

        $this->requestXml = $xml;
    }

    protected function prepare($data = [])
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }
}