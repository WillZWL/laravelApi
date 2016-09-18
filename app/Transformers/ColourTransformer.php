<?php

namespace App\Transformers;

use App\Models\Colour;
use League\Fractal\TransformerAbstract;

class ColourTransformer extends TransformerAbstract
{
    public function transform(Colour $colour)
    {
        return [
            'colour_id' => $colour->id,
            'colour_name' => $colour->name,
        ];
    }
}
