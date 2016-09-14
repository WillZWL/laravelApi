<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaProductUpdate;

class ApiWishProductService extends ApiWishAuthService implements ApiPlatformProductInterface
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getPlatformId()
    {
        return 'Wish';
    }

    public function getProductList($storeName)
    {
        $wishClient = $this->initWishClient($storeName);
        $orginProductList = $wishClient->getAllProducts();
        $productVariations = $wishClient->getAllProductVariations();
        print_r($orginProductList);exit();
        return $orginProductList;
    }

    public function submitProductPrice($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_PRICE);
        if(!$processStatusProduct->isEmpty()){
            $wishClient = $this->initWishClient($storeName);
            foreach ($processStatusProduct as $index => $pendingSku) {
                $variant = $wishClient->getProductVariationBySKU($pendingSku->marketplace_sku);
                $variant->price = $pendingSku->price;
                $result = $wishClient->updateProductVariation($variant);
            }
            if($result){
                $this->updatePendingProductProcessStatus($processStatusProduct,self::PENDING_PRICE);
                return $result;
            }
        }
    }

    public function submitProductInventory($storeName)
    {
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,self::PENDING_INVENTORY);
        if(!$processStatusProduct->isEmpty()){
            $wishClient = $this->initWishClient($storeName);
            foreach ($processStatusProduct as $index => $pendingSku) {
                $wishClient->updateInventoryBySKU($pendingSku->marketplace_sku,$pendingSku->inventory);
            }
            if($result){
                $this->updatePendingProductProcessStatus($processStatusProduct,self::PENDING_INVENTORY);
                return $result;
            }
        }
    }

    public function submitProductCreate($storeName)
    {   
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
        $wishClient = $this->initWishClient($storeName);
        foreach ($pendingSkuGroup as $index => $pendingSku) {
            $product = array(
              'name' => $pendingSku->prod_name,
              'main_image' => $pendingSku->marketplace_sku,
              'extra_images' => $pendingSku->marketplace_sku,
              'sku' => $pendingSku->marketplace_sku,
              'parent_sku' => $pendingSku->marketplace_sku,
              'shipping' => $pendingSku->marketplace_sku,
              'tags' => $pendingSku->product,
              'description' => $pendingSku->product,
              'price' => $pendingSku->price,
              'inventory' => $pendingSku->inventory,
              );
            $result = $wishClient->createProduct($product);
            print_r($result);
        }
        $this->saveDataToFile(serialize($result), 'updateProduct');
        //return $result;

    }

    public function submitProductUpdate($storeName)
    {
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
    }
}
