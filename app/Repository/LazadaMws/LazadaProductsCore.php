<?php

namespace App\Repository\LazadaMws;

class LazadaProductsCore extends LazadaCore
{
    private $offset = 0;
    private $limit = 100;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setOffset($value)
    {
        $this->offset = $value;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($value)
    {
        $this->limit = $value;
    }
}
