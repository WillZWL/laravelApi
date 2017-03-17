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
        $this->middleware("auth");
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($this->wmsPlatform);
    }

    public function donwloadLabel($pickListNo, $documentType, Request $request)
    {
        $soNo = $request->input("so_no");
        $documentSuffix = array(
            "AWB" => "_awb",
            "invoice" => "_invoice",
            );
        if(!empty($soNo)){
            $filePath = $this->iwmsFactoryWmsService->getCourierPickListFilePathByType($soNo, $documentType);
            return response()->download($filePath.$soNo.$documentSuffix[$documentType].".pdf");
        }
    }

}
