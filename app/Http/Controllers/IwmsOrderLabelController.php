<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;

class IwmsOrderLabelController extends Controller
{
    //
    private $wmsPlatform = "iwms";
    private $iwmsFactoryWmsService;
    
    public function __construct(Request $request)
    {
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($this->wmsPlatform);
    }

    public function donwloadLabel($documentType, Request $request)
    {
        $soNo = $request->input("so_no");
        if(!empty($soNo)){
            $filePath = $this->iwmsFactoryWmsService->getCourierPickListFilePathByType($soNo, $documentType);
            return response()->download($filePath);
        }
    }
}
