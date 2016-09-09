<?php

namespace App\Repository\TangaMws;

class TangaProductUpdate extends TangaOrderCore
{
    private $vendorSkuCode;
    private $inStock;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function updateInventory($csvData)
    {
        return $this->postDataToAPI($csvData, 'csv');
    }

    protected function getRequestParams()
    {
        if ($this->getVendorSkuCode()) {
            $requestParams['vendor_sku_code'] = $this->getVendorSkuCode();
        }

        if ($this->getInStock() >= 0) {
            $requestParams['in_stock'] = $this->getInStock();
        }

        return $requestParams;
    }

    public function setTangaPath()
    {
        $this->tangaPath = 'api/v1/drop_shippers/'. $this->vendorAppId .'/inventory/file';
    }


    public function getVendorSkuCode()
    {
        return $this->vendorSkuCode;
    }

    public function setVendorSkuCode($vendorSkuCode)
    {
        $this->vendorSkuCode = $vendorSkuCode;
    }

    public function getInStock()
    {
        return $this->inStock;
    }

    public function setInStock($inStock)
    {
        $this->inStock = $inStock;
    }
}
