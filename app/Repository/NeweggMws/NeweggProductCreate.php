<?php

namespace App\Repository\NeweggMws;

class NeweggProductCreate extends NeweggOrderCore
{
    private $resourceMethod;
    private $xsdFile = "\DataFeedMgmt\SubmitFeed\SubmitFeedResponse.xsd";
    public function __construct($store)
    {
        parent::__construct($store);
        $this->setResourceMethod("POST");
    }

    /**
     * update product inventory
     */
    public function createProduct($products)
    {
        $resourceUrl = 'datafeedmgmt/feeds/submitfeed';
        $requestXml = $this->getCreateProductRequestXml($products);
        $requestParams = parent::initRequestParams();
        $result = parent::query($resourceUrl, $this->getResourceMethod(), $requestParams, $requestXml);
        return  $result;
    }

    /**
     * Get update price request xml data
     */
    private function getCreateProductRequestXml($products)
    {
        $requestXml = array();
        $requestXml[] = '<NeweggEnvelope>';
        $requestXml[] =   '<Header><DocumentVersion>1.0</DocumentVersion></Header>';
        $requestXml[] =   '<MessageType>BatchItemCreation</MessageType>';
        $requestXml[] =   '<Message>';
        $requestXml[] =     '<Itemfeed>';
        $requestXml[] =        '<SummaryInfo>';
        $requestXml[] =        '<SubCategoryID>'.$product->name.'</SubCategoryID>';
        $requestXml[] =        '</SummaryInfo>';
        foreach($products as $product){
            $requestXml[] =        '<Item>';
            $requestXml[] =           '<Action>'.$product->name.'</Action>';
            $requestXml[] =           '<BasicInfo>';
            $requestXml[] =             '<SellerPartNumber>'.$product->name.'</SellerPartNumber>';
            $requestXml[] =             '<Manufacturer>'.$product->name.'</Manufacturer>';
            $requestXml[] =             '<ManufacturerPartNumberOrISBN>'.$product->name.'</ManufacturerPartNumberOrISBN>';
            $requestXml[] =             '<UPC>'.$product->name.'</UPC>';
            $requestXml[] =             '<ManufacturerItemURL>'.$product->name.'</ManufacturerItemURL>';
            $requestXml[] =             '<RelatedSellerPartNumber>'.$product->name.'</RelatedSellerPartNumber>';
            $requestXml[] =             '<WebsiteShortTitle>'.$product->name.'</WebsiteShortTitle>';
            $requestXml[] =             '<BulletDescription>'.$product->name.'</BulletDescription>';
            $requestXml[] =             '<ProductDescription>'.$product->name.'</ProductDescription>';
            $requestXml[] =             '<ItemDimension>';
            $requestXml[] =              '<ItemLength>'.$product->name.'</ItemLength>';
            $requestXml[] =              '<ItemWidth>'.$product->name.'</ItemWidth>';
            $requestXml[] =              '<ItemHeight>'.$product->name.'</ItemHeight>';
            $requestXml[] =             '<ItemDimension>';
            $requestXml[] =             '<ItemWeight>'.$product->name.'</ItemWeight>';
            $requestXml[] =             '<PacksOrSets>'.$product->name.'</PacksOrSets>';
            $requestXml[] =             '<ItemPackage>'.$product->name.'</ItemPackage>';
            $requestXml[] =             '<ShippingRestriction>'.$product->name.'</ShippingRestriction>';
            $requestXml[] =             '<Currency>'.$product->name.'</Currency>';
            $requestXml[] =             '<MSRP>'.$product->name.'</MSRP>';
            $requestXml[] =             '<SellingPrice>'.$product->name.'</SellingPrice>';
            $requestXml[] =             '<Shipping>'.$product->name.'</Shipping>';
            $requestXml[] =             '<Inventory>'.$product->name.'</Inventory>';
            $requestXml[] =             '<ActivationMark>'.$product->name.'</ActivationMark>';
            $requestXml[] =             '<ItemImages><Image><ImageUrl>'.$product->name.'</Image></ImageUrl></ItemImages>';
            $requestXml[] =             '<Warning>';
            $requestXml[] =                 '<Prop65>'.$product->name.'</Prop65>';
            $requestXml[] =                 '<Prop65Motherboard>'.$product->name.'</Prop65Motherboard>';
            $requestXml[] =                 '<OverAge18Verification>'.$product->name.'</OverAge18Verification>';
            $requestXml[] =                 '<ChokingHazard>';
            $requestXml[] =                     '<SmallParts>'.$product->name.'</SmallParts>';
            $requestXml[] =                     '<SmallBall>'.$product->name.'</SmallBall>';
            $requestXml[] =                     '<Balloons>'.$product->name.'</Balloons>';
            $requestXml[] =                     '<Marble>'.$product->name.'</Marble>';
            $requestXml[] =                 '</ChokingHazard>';
            $requestXml[] =             '</Warning>';
            $requestXml[] =           '</BasicInfo>';
            $requestXml[] =           '<SubCategoryProperty>';
            $requestXml[] =             '<CostumeAccessories>';
            $requestXml[] =                 '<CostumeAccBrand>'.$product->name.'</CostumeAccBrand>';
            $requestXml[] =                 '<CostumeAccModel>'.$product->name.'</CostumeAccModel>';
            $requestXml[] =                 '<CostumeAccGender>'.$product->name.'</CostumeAccGender>';
            $requestXml[] =                 '<CostumeAccAge>'.$product->name.'</CostumeAccAge>';
            $requestXml[] =                 '<CostumeAccTheme>'.$product->name.'</CostumeAccTheme>';
            $requestXml[] =                 '<CostumeAccOccasion>'.$product->name.'</CostumeAccOccasion>';
            $requestXml[] =                 '<CostumeAccColor>'.$product->name.'</CostumeAccColor>';
            $requestXml[] =                 '<CostumeAccMaterial>'.$product->name.'</CostumeAccMaterial>';
            $requestXml[] =                 '<CostumeAccCareInstructions>'.$product->name.'</CostumeAccMaterial>';
            $requestXml[] =             '<CostumeAccessories>';
            $requestXml[] =           '</SubCategoryProperty>';      
            $requestXml[] =        '</Item>';
        }
        $requestXml[] =     '</Itemfeed>';
        $requestXml[] =   '</Message>';
        $requestXml[] = '</NeweggEnvelope>';
        return implode("\n", $requestXml);
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }
}
