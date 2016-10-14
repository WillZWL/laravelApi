<?php

namespace App\Repository\LazadaMws;

class LazadaProductCreate extends LazadaProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function createProduct($product)
    {
        $xmlData = $this->getCreateProductXmlData($product);
        return parent::curlPostDataToApi($this->_requestParams, $xmlData);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $this->_requestParams = array_merge($this->_requestParams, $requestParams);
        $this->_requestParams['Action'] = 'CreateProduct';
    }

    public function getCreateProductXmlData($product)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        $xmlData .= '<Product>';
        $xmlData .= '<PrimaryCategory>'.$product->cat.'</PrimaryCategory>';
        $xmlData .= '<SPUId>'.$product->marketplace_sku.'</SPUId>';
        $xmlData .= '<AssociatedSku>'.$product->marketplace_sku.'</AssociatedSku>';
        $xmlData .= '<Attributes>';
        $xmlData .=      '<name>'.$product->marketplace_sku.'</name>';
        $xmlData .=      '<short_description>'.$product->marketplace_sku.'</short_description>';
        $xmlData .=      '<model>'.$product->marketplace_sku.'</model>';
        $xmlData .=      '<kid_years>'.$product->marketplace_sku.'</kid_years>';
        $xmlData .= '</Attributes>';
        $xmlData .= '<Skus>';
        $xmlData .=      '<Sku>';
        $xmlData .=          '<SellerSku>'.$product->marketplace_sku.'</SellerSku>';
        $xmlData .=          '<color_family>'.$product->marketplace_sku.'</color_family>';
        $xmlData .=          '<size>'.$product->marketplace_sku.'</size>';
        $xmlData .=          '<quantity>'.$product->marketplace_sku.'</quantity>';
        $xmlData .=          '<price>'.$product->marketplace_sku.'</price>';
        $xmlData .=          '<package_length>'.$product->marketplace_sku.'</package_length>';
        $xmlData .=          '<package_height>'.$product->marketplace_sku.'</package_height>';
        $xmlData .=          '<package_weight>'.$product->marketplace_sku.'</package_weight>';
        $xmlData .=          '<package_width>'.$product->marketplace_sku.'</package_width>';
        $xmlData .=          '<package_content>'.$product->marketplace_sku.'</package_content>';
        $xmlData .=          '<Images>';
        $xmlData .=              '<Image>'.$product->marketplace_sku.'</Image>';
        $xmlData .=          '</Images>';
        $xmlData .=      '</Sku>';
        $xmlData .= '</Skus>';
        $xmlData .= '</Product>';
        $xmlData .= '</Request>';
        return  $xmlData;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Head']) && isset($data['Head']['RequestId']) && isset($data['Head']['RequestAction'])) {
            return $data['Head'];
        }
        return null;
    }
}
