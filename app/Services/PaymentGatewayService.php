<?php

namespace App\Services;

use App\Models\PaymentGateway;

class PaymentGatewayService
{
    public function all()
    {
        return PaymentGateway::all();
    }
}
