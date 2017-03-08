<?php

namespace App\Repository\TangaMws;

class TangaProductUpdate extends TangaProductCore
{

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function updatePriceAndInventory($requestData = [])
    {
        return $this->curlPostData($requestData, 'json');
    }

    public function setTangaPath()
    {
        $this->tangaPath = 'api/v1/drop_shippers/'. $this->vendorAppId .'/products';
    }
}
