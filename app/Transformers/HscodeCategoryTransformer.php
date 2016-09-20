<?php

namespace App\Transformers;

use App\Models\HscodeCategory;
use League\Fractal\TransformerAbstract;

class HscodeCategoryTransformer extends TransformerAbstract
{
    public function transform(HscodeCategory $hscodeCategory)
    {
        return [
            'hscode_category_id' => $hscodeCategory->id,
            'hscode_category_name' => $hscodeCategory->name,
        ];
    }
}
