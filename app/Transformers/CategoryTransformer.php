<?php

namespace App\Transformers;

use App\Models\Category;
use League\Fractal\TransformerAbstract;

class CategoryTransformer extends TransformerAbstract
{
    public function transform(Category $category)
    {
        return [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'category_parent_cat_id' => $category->parent_cat_id,
        ];
    }
}
