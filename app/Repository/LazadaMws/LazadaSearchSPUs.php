<?php

namespace App\Repository\LazadaMws;

class LazadaSearchSPUs extends LazadaProductsCore
{
    private $categoryId;
    private $search;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function searchSPUs()
    {   
        return parent::query($this->getSearchSPUsRequestParams());
    }

    public function getSearchSPUsRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'SearchSPUs';
        if ($this->getCategoryId()) {
            $requestParams['CategoryId'] = $this->getCategoryId();
        }
        if ($this->getSearch()) {
            $requestParams['Search'] = $this->getSearch();
        }
        $requestParams['Offset'] = $this->getOffset();
        $requestParams['Limit'] = $this->getLimit();
        return  $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['SPUs'])&& isset($data['Body']['SPUs']['SPU'])) {
            return $data['Body']['SPUs']['SPU'];
        }
        return null;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function setCategoryId($value)
    {
        $this->categoryId = $value;
    }

    public function getSearch()
    {
        return $this->search;
    }

    public function setSearch($value)
    {
        $this->search = $value;
    }

}