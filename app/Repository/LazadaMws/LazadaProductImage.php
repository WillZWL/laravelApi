<?php

namespace App\Repository\LazadaMws;

class LazadaProductImage extends LazadaProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->_requestParams = $this->getRequestParams();
    }

    public function migrateImage($xmlData)
    {
        $this->_requestParams["Action"] = 'MigrateImage';
        $xmlData = $this->getMigrateImageXmlData();
        return parent::curlPostDataToApi($this->_requestParams, $xmlData);
    }

    public function uploadImage($image)
    {
        $this->_requestParams["Action"] = 'UploadImage';
        return parent::curlPostDataToApi($this->_requestParams, $image);
    }

    public function setImage($product)
    {
        $this->_requestParams["Action"] = 'SetImage';
        $xmlData = $this->getSetImageXmlData($product);
        return parent::curlPostDataToApi($this->_requestParams, $xmlData);
    }

    public function getRequestParams()
    {
        return parent::initRequestParams();
    }

    public function getMigrateImageXmlData()
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        $xmlData .= '<Image>';
        $xmlData .=     '<Url>'.$product->cat.'</Url>';
        $xmlData .= '</Image>';
        $xmlData .= '</Request>';
        return  $xmlData;
    }

    public function getSetImageXmlData($product)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        $xmlData .= '<Product>';
        $xmlData .=    '<Skus>';
        $xmlData .=       '<Sku>';
        $xmlData .=           '<SellerSku>'.$product->sku.'</SellerSku>';
        $xmlData .=           '<Images>';
        foreach ($product as $value) {
            $xmlData .= '<Image>'.$value->Image.'</Image>';
        }
        $xmlData .=           '</Images>';
        $xmlData .=       '</Sku>';
        $xmlData .=    '</Skus>';
        $xmlData .= '</Product>';
        $xmlData .= '</Request>';
        return  $xmlData;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Head']) && isset($data['Body'])) {
            return $data['Body'];
        }
        return null;
    }
}
