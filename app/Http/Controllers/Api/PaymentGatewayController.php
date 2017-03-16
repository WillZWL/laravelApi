<?php

namespace App\Http\Controllers\Api;

use App\Services\PaymentGatewayService;
use App\Transformers\PaymentGatewayTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class PaymentGatewayController extends Controller
{
    use Helpers;

    private $paymentGateway;

    public function __construct(PaymentGatewayService $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paymentGateways = $this->paymentGateway->all();

        return $this->response->collection($paymentGateways, new PaymentGatewayTransformer());
    }
}
