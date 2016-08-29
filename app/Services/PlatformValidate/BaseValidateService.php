<?php

namespace App\Services\PlatformValidate;

use App\Models\MarketplaceSkuMapping;
use App\Models\MerchantProductMapping;
use App\Models\PlatformBizVar;

abstract class BaseValidateService
{
    private $order;
    private $platformAccount;
    private $countryCode;
    private $platformShortName;

    abstract public function validateOrder();
    abstract public function getPlatformAccountInfo($order);

    public function __construct($order, $accountInfo, $platformShortName)
    {
        $this->order = $order;
        $this->accountInfo = $accountInfo;
        $this->platformShortName = $platformShortName;
    }

    public function validate()
    {
        $this->countryCode = strtoupper(substr($this->order->platform, -2));
        //$countryCode = $order->amazonShippingAddress->country_code;
        $this->platformAccount = strtoupper(substr($this->order->platform, 0, 2));
        $marketplaceId = strtoupper(substr($this->order->platform, 0, -2));
        // 1 check marketplace sku mapping
        $marketplaceSkuList = $this->order->platformMarketOrderItem->pluck('seller_sku')->toArray();
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereIn('marketplace_sku', $marketplaceSkuList)
            ->whereMarketplaceId($marketplaceId)
            ->whereCountryId($this->countryCode)
            ->get();
        $valid = $this->checkMarketplaceSkuMapping($marketplaceSkuList, $marketplaceSkuMapping);
        if ($valid == false) {
            return false;
        }
        $esgSkuList = $marketplaceSkuMapping->pluck('sku')->toArray();
        $merchantProductMapping = MerchantProductMapping::join('merchant', 'id', '=', 'merchant_id')
            ->whereIn('sku', $esgSkuList)
            ->get();
         // 2check  Merchant Sku Mapping
        $valid = $this->checkSkuMerchant($esgSkuList, $merchantProductMapping);
        if ($valid == false) {
            return false;
        }
        //3 check selling platform is exist or not.
        $valid = $this->checkSellingPlatform($merchantProductMapping);
        if ($valid == false) {
            return false;
        }
        // 4 check sku delivery type.
        $valid = $this->checkSkuDeliveryType($marketplaceSkuMapping);
        if ($valid == false) {
            return false;
        }

        return true;
    }

    public function checkMarketplaceSkuMapping($marketplaceSkuList, $marketplaceSkuMapping)
    {
        // check marketplace sku mapping
        $mappedMarketplaceSkuList = $marketplaceSkuMapping->pluck('marketplace_sku')->toArray();
        $notMappedMarketplaceSkuList = array_diff($marketplaceSkuList, $mappedMarketplaceSkuList);
        if ($notMappedMarketplaceSkuList) {
            $missingSku = implode(',', $notMappedMarketplaceSkuList);
            $subject = "[{$this->accountInfo['accountName']}] {$this->order->biz_type} Order Import Failed!";
            $message = "MarketPlace: {$this->order->platform}.\r\n {$this->order->biz_type} Order NO: {$this->order->platform_order_no}\r\n";
            $message .= "Marketplace SKU <{$missingSku}> not exist in esg admin. please add listing sku mapping first. Thanks";
            $this->addMailMessage($this->accountInfo['alertEmail'], $subject, $message);

            return false;
        }

        return true;
    }

    public function checkSkuMerchant($esgSkuList, $merchantProductMapping)
    {
        // Does sku have a merchant?
        $mappedEsgSkuList = $merchantProductMapping->pluck('sku')->toArray();
        $notMappedEsgSkuList = array_diff($esgSkuList, $mappedEsgSkuList);
        if ($notMappedEsgSkuList) {
            $missingSku = implode(',', $notMappedEsgSkuList);
            $subject = "[{$this->accountInfo['accountName']}] {$this->order->biz_type} Order Import Failed!";
            $message = "MarketPlace: {$this->order->platform}.\r\n {$this->order->biz_type} Order NO: {$this->order->platform_order_no}\r\n";
            $message .= "ESG SKU <{$missingSku}> not belong to any merchant. please add merchant sku mapping first. Thanks";
            $this->addMailMessage($this->accountInfo['alertEmail'], $subject, $message);

            return false;
        }

        return true;
    }

    public function checkSellingPlatform($merchantProductMapping)
    {
        // check selling platform is exist or not.
        $sellingPlatformId = $merchantProductMapping->pluck('short_id')->map(function ($item) {
            return 'AC-'.$this->platformAccount.$this->platformShortName.'-'.$item.$this->countryCode;
        })->toArray();

        $platformIdFromDB = PlatformBizVar::whereIn('selling_platform_id', $sellingPlatformId)
            ->get()
            ->pluck('selling_platform_id')
            ->toArray();
        $notExistPlatform = array_diff($sellingPlatformId, $platformIdFromDB);
        if ($notExistPlatform) {
            $missingSellingPlatform = implode(',', $notExistPlatform);
            $subject = "[{$this->accountInfo['accountName']}] {$this->order->biz_type} Order Import Failed!";
            $message = "MarketPlace: {$this->order->platform}.\r\n {$this->order->biz_type} Order NO: {$this->order->platform_order_no}\r\n";
            $message .= "Selling Platform Id <{$missingSellingPlatform}> not exists in esg system, please add it. Thanks";
            $this->addMailMessage($this->accountInfo['alertEmail'], $subject, $message);

            return false;
        }

        return true;
    }

    public function checkSkuDeliveryType($marketplaceSkuMapping)
    {
        // check sku delivery type.
        $notHaveDeliveryTypeSku = $marketplaceSkuMapping->where('delivery_type', '');
        if (!$notHaveDeliveryTypeSku->isEmpty()) {
            $notHaveDeliveryTypeSku->load('product');
            $subject = "[{$this->accountInfo['accountName']}] Delivery Type Missing - {$this->order->biz_type} Order Import Failed!";
            $message = "MarketPlace: {$this->order->platform}.\r\n {$this->order->biz_type} Order Id: {$this->order->platform_order_no}\r\n";

            $message = $notHaveDeliveryTypeSku->reduce(function ($message, $marketplaceProduct) {
                return $message .= "Marketplace SKU <{$marketplaceProduct->marketplace_sku}>, product title <{$marketplaceProduct->product->name}>\r\n";
            }, $message);
            $message .= 'Please set delivery type in pricing tool, Thanks.';
            $this->addMailMessage($this->accountInfo['alertEmail'], $subject, $message);

            return false;
        }

        return true;
    }

    public function addMailMessage($alertEmail, $subject, $message)
    {
        mail("{$alertEmail}, jimmy.gao@eservicesgroup.com", $subject, $message, $headers = 'From: admin@shop.eservciesgroup.com');
        /*Mail::queue("emails.transfer-order",$data,function($message){
            $message->from('admin@shop.eservciesgroup.com', 'Accelerator');
            $message->to($alertEmail);
            $message->cc('jimmy.gao@eservicesgroup.com');
            $message->subject($subject);
        });*/
    }

    public function addMarketplaceFailOrder($marketplaceSku)
    {
        $failOrder = array(
            'order_id' => $this->order->platform_order_no,
            'biz_type' => $this->order->biz_type,
            'platform' => $this->order->platform,
            'marketplace_sku' => $marketplaceSku,
        );

        return $failOrder;
    }
}
