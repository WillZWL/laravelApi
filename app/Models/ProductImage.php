<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_image';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

    public function scopeProdImages($query, $sku)
    {
        $productImages = [];

        $prodImgs = $query->whereSku($sku)->whereStatus(1)->select('id', 'sku', 'image', 'priority', 'alt_text', 'status')->get();
        if ($prodImgs) {
          foreach ($prodImgs as $prodImg) {
              $img = $prodImg->sku ."_". $prodImg->id .".". $prodImg->image;
              $pathFile = '/product-images/'. $img;
              $prodImg->url = url($pathFile);

              $productImages[] = $prodImg;
          }
        }

        return $productImages;
    }
}