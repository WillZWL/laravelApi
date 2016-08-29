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
            $AuthKeyWithToken = $this->getAuthKeyWithToken();

            $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_query $AuthKeyWithToken>
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