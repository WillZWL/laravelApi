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
        $xmlData .= '<PrimaryCategory>'.$product->platformMarketProductAttributes->cat_id.'</PrimaryCategory>';
        $xmlData .= '<SPUId>'.$product->platformMarketProductAttributes->spu_id.'</SPUId>';
        $xmlData .= '<AssociatedSku>'.$product->marketplace_sku.'</AssociatedSku>';
        $xmlData .= '<Attributes>';
        $xmlData .=      '<name>'.$product->prod_name.'</name>';
        $xmlData .=      '<short_description>'.$product->short_desc.'</short_description>';
        $xmlData .=      '<model>'.$product->platformMarketProductAttributes->model.'</model>';
        $xmlData .=      '<kid_years></kid_years>';
        $xmlData .= '</Attributes>';
        $xmlData .= '<Skus>';
        $xmlData .=      '<Sku>';
        $xmlData .=          '<SellerSku>'.$product->marketplace_sku.'</SellerSku>';
        //$xmlData .=          '<color_family>'.$product.'</color_family>';
        //$xmlData .=          '<size>'.$product.'</size>';
        $xmlData .=          '<quantity>'.$product->inventory.'</quantity>';
        $xmlData .=          '<price>'.$product->price.'</price>';
        $xmlData .=          '<package_length>'.$product->length.'</package_length>';
        $xmlData .=          '<package_height>'.$product->height.'</package_height>';
        $xmlData .=          '<package_weight>'.$product->weight.'</package_weight>';
        $xmlData .=          '<package_width>'.$product->width.'</package_width>';
        $xmlData .=          '<package_content>'.$product->contents.'</package_content>';
        if($product->imageUrl){
            $xmlData .=          '<Images>';
            $xmlData .=              '<Image>'.$product->imageUrl.'</Image>';
            $xmlData .=          '</Images>';
        }
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
