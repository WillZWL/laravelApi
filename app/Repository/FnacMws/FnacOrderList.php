<?php

namespace App\Repository\FnacMws;

class FnacOrderList extends FnacOrderCore
{
    private $orderState = 'Accepted';
    private $dateType = 'AcceptedAt';
    private $minAt;
    private $maxAt;


    public function __construct($store)
    {
      parent::__construct($store);
      $this->setMaxAt();
    }

    public function fetchOrderList()
    {
        $this->setOrdersQueryPath();
        $this->setRequestXml();

        return parent::query($this->getRequestXml());
    }

    protected function prepare($data = array())
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }

    public function setRequestXml()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_query
    results_count="100000"
    partner_id="$this->fnacPartnerId"
    shop_id="$this->fnacShopId"
    key="$this->fnacKey"
    token="$this->fnacToken"
    xmlns="http://www.fnac.com/schemas/mp-dialog.xsd"
    >
    <paging>1</paging>
    <date type="$this->dateType">
        <min>$this->minAt</min>
        <max>$this->maxAt</max>
    </date>
    <states>
        <state>$this->orderState</state>
    </states>
</orders_query>
XML;

        $this->requestXml = $xml;
    }

    public function setMinAt($minAt)
    {
        $this->minAt = $minAt;
    }

    protected function setMaxAt()
    {
        $now = new \DateTime();
        $this->maxAt = date('c', strtotime($now->format(\DateTime::ISO8601)));
    }
}