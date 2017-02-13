<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller;
use App\Services\CourierInfoService;
use App\Transformers\CourierInfoTransformer;

class CourierInfoController extends Controller
{
    use Helpers;

    private $courierInfoService;

    public function __construct(CourierInfoService $courierInfoService)
    {
        $this->courierInfoService = $courierInfoService;
    }

    public function index()
    {
        $courierInfos = $this->courierInfoService->all();

        return $this->response()->collection($courierInfos, new CourierInfoTransformer());
    }
}
