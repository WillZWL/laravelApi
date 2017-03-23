<?php

namespace App\Services;

use App\Models\PaymentGateway;

class PaymentGatewayService
{
    public function getPaymentGateway($request)
    {
        if ($request->get('payment_gateway')) {
            return PaymentGateway::where('id', $request->get('payment_gateway'))
                ->get();
        } else {
            return PaymentGateway::all();
        }
    }
}
