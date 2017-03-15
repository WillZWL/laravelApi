<?php

namespace App\Transformers;

use App\Models\PaymentGateway;
use League\Fractal\TransformerAbstract;

class PaymentGatewayTransformer extends TransformerAbstract
{
    public function transform(PaymentGateway $paymentGateway)
    {
        return [
            'id' => $paymentGateway->id,
            'name' => $paymentGateway->name
        ];
    }
}
