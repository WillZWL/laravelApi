<?php

namespace App\Transformers;

use App\Models\Version;
use League\Fractal\TransformerAbstract;

class VersionTransformer extends TransformerAbstract
{
    public function transform(Version $version)
    {
        return [
            'version_id' => $version->id,
            'version_name' => $version->desc,
        ];
    }
}
