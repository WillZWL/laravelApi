<?php

namespace App\Repository\FnacMws;

class FnacOrder extends FnacOrderCore
{
    private $action;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setFnacAction('orders_query');
    }

    public function fetchOrder()
    {
        $this->setRequestXml();

        return parent::query($this->getRequestXml());
    }

    protected function setRequestXml()
    {
        if ($orderId = $this->getOrderId()) {
            $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
            $xmlData .= '<orders_query '. $this->getAuthKeyWithToken() .'>';
            $xmlData .=     '<orders_fnac_id>';
            $xmlData .=          '<order_fnac_id>'. $orderId .'</order_fnac_id>';
            $xmlData .=     '</orders_fnac_id>';
            $xmlData .= '</orders_query>';

            $this->requestXml = $xmlData;
        }
    }

    protected function prepare($data = [])
    {
        if (isset($data['order'])) {
            return parent::fix($data['order']);
        }

        return null;
    }
}