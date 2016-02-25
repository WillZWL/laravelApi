<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrderItem extends Model
{
    protected $fillable = [
        'amazon_order_id',
        'asin',
        'seller_sku',
        'order_item_id',
        'title',
        'quantity_ordered',
        'quantity_shipped',
        'item_price',
        'shipping_price',
        'gift_wrap_price',
        'item_tax',
        'shipping_tax',
        'gift_wrap_tax',
        'shipping_discount',
        'promotion_discount'
    ];
}
