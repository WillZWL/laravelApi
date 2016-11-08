<?php

namespace App\Repository\Qoo10Mws;

class Qoo10OrderCore extends Qoo10Core
{
    protected $orderId;
    protected $shippingStat;
    protected $startDate;
    protected $endDate;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId($value)
    {
        $this->orderId = $value;
    }

    public function getShippingStat($status)
    {
        switch ($status) {
            case 'WaitingForShipping':
                $state = 1;
                break;

            case 'RequestShipping':
                $state = 2;
                break;

            case 'CheckOrder':
                $state = 3;
                break;

            case 'OnDelivery':
                $state = 4;
                break;

            case 'Delivered':
                $state = 5;
                break;
        }

        $this->shippingStat = $state;

        return $this->shippingStat;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getEndDate()
    {
        $this->endDate = date("Ymd");

        return $this->endDate;
    }

}