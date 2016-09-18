<?php

namespace App\Transformers;

use App\Models\Supplier;
use League\Fractal\TransformerAbstract;

class SupplierTransformer extends TransformerAbstract
{
    public function transform(Supplier $supplier)
    {
        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'supplier_currency_id' => $supplier->currency_id,
        ];
    }
}
