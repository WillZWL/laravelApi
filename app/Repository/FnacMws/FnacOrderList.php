<?php

namespace App\Repository\FnacMws;

class FnacOrderList extends FnacOrderCore
{
    private $fnacOrderIds;
    private $orderState = 'Created';
    private $dateType = 'CreatedAt';
    private $minAt;
    private $maxAt;


    public function __construct($store)
    {
        parent::__construct($store);
        $this->setFnacAction('orders_query');
    }

    public function fetchOrderList()
    {
        $this->setFnacQueryOrderListRequestXml();

        return parent::query($this->getRequestXml());
    }

    protected function prepare($data = array())
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }

    public function setFnacQueryOrderListRequestXml()
    {
        $this->setMaxAt();

        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<orders_query results_count="10000" '. $this->getAuthKeyWithToken() .'>';
        $xmlData .=     '<paging>1</paging>';
        $xmlData .=     '<date type="'. $this->getDateType() .'">';
        $xmlData .=         '<min>'. $this->getMinAt() .'</min>';
        $xmlData .=         '<max>'. $this->getMaxAt() .'</max>';
        $xmlData .=     '</date>';
        $xmlData .=     '<states>';
        $xmlData .=         '<state>'. $this->getOrderState() .'</state>';
        $xmlData .=     '</states>';
        $xmlData .=     '<sort_by>ASC</sort_by>';
        $xmlData .= '</orders_query>';

        $this->requestXml = $xmlData;
    }

    public function requestFnacPendingPayment()
    {
        $this->setFnacPendingPaymentRequestXml();

        return parent::query($this->getRequestXml());
    }

    public function setFnacPendingPaymentRequestXml()
    {
        if ($orderIds = $this->getFnacOrderIds()) {
            $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
            $xmlData .= '<orders_query '. $this->getAuthKeyWithToken() .'>';
            $xmlData .=     '<orders_fnac_id>';
            foreach ($orderIds as $orderId) {
                $magDom =     '<order_fnac_id>'. $orderId .'</order_fnac_id>';
                $xmlData .= $magDom;
            }
            $xmlData .=     '</orders_fnac_id>';
            $xmlData .= '</orders_query>';

            $this->requestXml = $xmlData;
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