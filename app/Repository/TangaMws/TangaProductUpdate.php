<?php

namespace App\Repository\TangaMws;

class TangaProductUpdate extends TangaProductCore
{

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function updateInventory($csvData)
    {
        return $this->postDataToAPI($csvData, 'csv');
    }

    public function setTangaPath()
    {
        $this->tangaPath = 'api/v1/drop_shippers/'. $this->vendorAppId .'/inventory/file';
    }

}
