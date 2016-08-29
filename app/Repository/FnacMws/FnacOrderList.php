<?php

namespace App\Repository\FnacMws;

class FnacOrderList extends FnacOrderCore
{
    private $fnacOrderIds;
    private $orderState = 'Created';
    private $dateType = 'CreatedAt';
    private $resultsCount = 1000;
    private $paging = 1;
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
        $this->setFnacQueryOrderRequestXml();

        return parent::query($this->getRequestXml());
    }

    protected function prepare($data = array())
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }

    public function setFnacQueryOrderRequestXml()
    {
        if ($this->fnacToken) {
            $AuthKeyWithToken = $this->getAuthKeyWithToken();
            $paging = $this->getPaging();
            $dateType = $this->getDateType();
            $minAt = $this->getMinAt();
            $maxAt = $this->getMaxAt();
            $orderState = $this->getOrderState();

            $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_query results_count="$this->resultsCount" $AuthKeyWithToken>
    <paging>$paging</paging>
    <date type="$dateType">
        <min>$minAt</min>
        <max>$maxAt</max>
    </date>
    <states>
        <state>$orderState</state>
    </states>
    <sort_by>ASC</sort_by>
</orders_query>
XML;

            $this->requestXml = $xml;
        }
    }

    public function requestFnacPendingPayment()
    {
        $this->setOrdersQueryPath();
        $this->setFnacPendingPaymentRequestXml();

        return parent::query($this->getRequestXml());
    }

    public function setFnacPendingPaymentRequestXml()
    {
        if ($this->fnacToken) {

            $orderIds = $this->getFnacOrderIds();
            $AuthKeyWithToken = $this->getAuthKeyWithToken();
            $paging = $this->getPaging();

            if (! $orderIds) {
                return false;
            }

            $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<orders_query results_count='$this->resultsCount' $AuthKeyWithToken>
    <paging>$paging</paging>
    <orders_fnac_id>
XML;
        foreach ($orderIds as $orderId) {
            $xml .= <<<XML
    <order_fnac_id>$orderId</order_fnac_id>
XML;
        }

            $xml .= <<<XML
  </orders_fnac_id>
</orders_query>
XML;

            $this->requestXml = $xml;
        }
    }

    public function getFnacOrderIds()
    {
        return $this->fnacOrderIds;
    }

    public function setFnacOrderIds($fnacOrderIds)
    {
        $this->fnacOrderIds = $fnacOrderIds;
    }

    public function getOrderState()
    {
        return $this->orderState;
    }

    public function getDateType()
    {
        return $this->dateType;
    }

    public function getPaging()
    {
        return $this->paging;
    }

    public function getMinAt()
    {
        return $this->minAt;
    }

    public function setMinAt($minAt)
    {
        $this->minAt = $minAt;
    }

    public function getMaxAt()
    {
        return $this->maxAt;
    }

    protected function setMaxAt()
    {
        $now = new \DateTime();
        $this->maxAt = date('c', strtotime($now->format(\DateTime::ISO8601)));
    }
}