<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;
use Config;

class IwmsOrderController extends Controller
{
    //
    private $iwmsFactoryWmsService;

    public function __construct(IwmsFactoryWmsService $iwmsFactoryWmsService)
    {
        $this->iwmsFactoryWmsService = $iwmsFactoryWmsService;
    }

    public function index(Request $request)
    {   
        $data = "";
        return response()->view('iwms-order.index', $data);
    }

    public function cancelDeliveryOrder(Request $request)
    {
        $esgOrderNoList = $request->input("so_no");
        $this->iwmsFactoryWmsService->cancelDeliveryOrder($esgOrderNoList);
    }

    public function cancelCourierOrder(Request $request)
    {   
        $esgOrderNoList = $request->input("so_no");
        $this->iwmsFactoryWmsService->cancelCourierOrder($esgOrderNoList);
    }


}
