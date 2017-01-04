<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSkuMapping extends Model
{
    const PENDING_PRICE = 2; //10
    const COMPLETE_PRICE = 8; //1000
    const PENDING_INVENTORY = 4;//100
    const COMPLETE_INVENTORY = 16;//10000
    const PENDING_PRODUCT = 32;//100000
    const COMPLETE_PRODUCT = 64;//1000000

    public $connection = 'mysql_esg';

    protected $table = 'marketplace_sku_mapping';

    public $timestamps = false;

    public $incrementing = false;

    //protected $fillable = array('marketplace_sku','sku','mp_control_id');
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

    public function merchant()
    {
        return $this->merchantProductMapping->merchant();
    }

    public function amazonFbaFee()
    {
        return $this->hasOne('App\Models\AmazonFbaFee');
    }

    public function amazonProductSizeTier()
    {
        return $this->hasOne('App\Models\AmazonProductSizeTier');
    }

    public function inventory()
    {
        return $this->hasMany('App\Models\Inventory', 'prod_sku', 'sku');
    }

    public function supplierProduct()
    {
        return $this->hasOne('App\Models\SupplierProd', 'prod_sku', 'sku')->where('order_default', '=', 1);
    }

    public function skuMapping()
    {
        return $this->belongsTo('App\Models\SkuMapping', 'sku', 'sku');
    }

    public function merchantProductMapping()
    {
        return $this->belongsTo('App\Models\MerchantProductMapping', 'sku', 'sku');
    }

    public function fulfillmentCenter($fulfillment = null)
    {
        $relation = $this->hasMany('App\Models\FulfillmentCenter', 'mp_control_id', 'mp_control_id');
        if ($fulfillment) {
            $relation->where('fulfillment_method', '=', $fulfillment);
        }

        return $relation;
    }

    public function mpControl()
    {
        return $this->belongsTo('App\Models\MpControl', 'mp_control_id', 'control_id');
    }

    public function mpCategoryCommission()
    {
        return $this->hasOne('App\Models\MpCategoryCommission', 'mp_id', 'mp_sub_category_id');
    }

    public function operatorInfo()
    {
        return $this->hasOne('App\Models\User', 'id', 'operator');
    }

    public function platformMarketOrderItem()
    {
        return $this->belongsTo('App\Models\PlatformMarketOrderItem');
    }

    public function amazonOrderItem()
    {
        return $this->belongsTo('App\Models\AmazonOrderItem');
    }

    public function scopePendingProductSkuGroups($query, $marketplaceId)
    {
        return $query->join('product', 'marketplace_sku_mapping.sku', '=', 'product.sku')
                    ->join('product_content', function ($q) {
                        $q->on('product.sku', '=', 'product_content.prod_sku')
                            ->on('marketplace_sku_mapping.lang_id', '=', 'product_content.lang_id');
                    })->join('product_content_extend', function ($q) {
                        $q->on('product.sku', '=', 'product_content_extend.prod_sku')
                            ->on('marketplace_sku_mapping.lang_id', '=', 'product_content_extend.lang_id');
                    })
                    ->join('brand', 'brand.id', '=', 'product.brand_id')
                    ->where('marketplace_sku_mapping.marketplace_id', 'like', $marketplaceId)
                    ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
                    ->where('marketplace_sku_mapping.process_status', '&', self::PENDING_PRODUCT)  // bit 1, PRODUCT_UPDATED
                    ->get()
                    ->groupBy('mp_control_id');
    }

    public function scopePendingProductSkuGroup($query, $storeName)
    {
        $marketplaceId = strtoupper(substr($storeName, 0, -2));
        $countryCode = strtoupper(substr($storeName, -2));
        return $query->join('product', 'marketplace_sku_mapping.sku', '=', 'product.sku')
                    ->join('product_content', function ($q) {
                        $q->on('product.sku', '=', 'product_content.prod_sku')
                            ->on('marketplace_sku_mapping.lang_id', '=', 'product_content.lang_id');
                    })->join('product_content_extend', function ($q) {
                        $q->on('product.sku', '=', 'product_content_extend.prod_sku')
                            ->on('marketplace_sku_mapping.lang_id', '=', 'product_content_extend.lang_id');
                    })
                    ->join('brand', 'brand.id', '=', 'product.brand_id')
                    ->where('marketplace_sku_mapping.marketplace_id', '=', $marketplaceId)
                    ->where('marketplace_sku_mapping.country_id', '=', $countryCode)
                    ->where('marketplace_sku_mapping.listing_status', '=', 'Y')
                    ->where('marketplace_sku_mapping.process_status', '&', self::PENDING_PRODUCT)  // bit 1, PRODUCT_UPDATED
                    ->get();
    }

    public function scopeProcessStatusProduct($query, $storeName, $processStatus)
    {
        $marketplaceId = strtoupper(substr($storeName, 0, -2));
        $countryCode = strtoupper(substr($storeName, -2));
        return $pendingSkuGroup = $query->where('process_status', '&', $processStatus)
            ->join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
            ->join('country', 'country.id', '=', 'marketplace_sku_mapping.country_id')
            ->where('listing_status', '=', 'Y')
            ->where('marketplace_id', '=', $marketplaceId)
            ->where('country_id', '=', $countryCode)
            ->select('marketplace_sku_mapping.*', 'country.id_3_digit', 'product.name')
            ->get();
    }
}
