<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductCreate extends PriceMinisterProductsCore
{
    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
    }

    public function submitXmlFile($xmlFile)
    {   
        $xmlData = fopen($xmlFile, "r");
        return parent::curlPostXmlFileToApi($this->getRequestParams(), $xmlData);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['action'] = 'genericimportfile';
        $requestParams['version'] = '2015-02-02';
        return  $requestParams;
    }

    public function getRequestXmlData($pendingSkuGroup)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<items>';
        foreach ($pendingSkuGroup as $index => $pendingSku) {
            $messageDom = '<item>';
            $messageDom .= '<alias>'.$pendingSku->marketplace_sku.'</alias>';
            $messageDom .= '<attributes>';
            $messageDom .= '<product>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>submitterreference</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>titre</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>fabricant</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>referencefabricant</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>modeledetelephone</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>codebarres</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .= '</product>';
            $messageDom .= '<advert>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>size</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>color</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>state</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>sellingPrice</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>qty</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>sellerReference</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .= '</advert>';
            $messageDom .= '<media>';
            $messageDom .=      '<attribute>';
            $messageDom .=          '<key>image_url</key>';
            $messageDom .=          '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
            $messageDom .=      '</attribute>';
            $messageDom .= '</media>';
            $messageDom .= '</attributes>';
            $messageDom .= '</item>';
            $xmlData .= $messageDom;
        }
        $xmlData .= '</items>';
        return $xmlData;
    }

    /*public function getRequestXmlData($pendingSkuGroup,$productTemplate)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<items>';
        foreach ($pendingSkuGroup as $index => $pendingSku) {
            $messageDom = '<item>';
            foreach ($productTemplate as $attributeType => $attribute) {
                $messageDom .= '<'.$attributeType.'>';
                foreach ($attribute["attribute"] as $value) {
                    $messageDom .= '<attribute>';
                    $messageDom .=    '<key>'.$value["key"].'</key>';
                    $messageDom .=    '<value><![CDATA['.$pendingSku->detail_desc.']]</value>';
                    $messageDom .= '</attribute>';
                }
                $messageDom .= '</'.$attributeType.'>';
            }
        $xmlData .= '</items>';
        return $xmlData;
    }*/

    public function setUrlBase()
    {
        $url = $this->urlbase.'stock_ws';
        $this->urlbase = $url;
    }
}
