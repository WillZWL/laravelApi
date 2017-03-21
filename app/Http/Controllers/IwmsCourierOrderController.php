<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;
use Config;
use File;
use Zipper;

class IwmsCourierOrderController extends Controller
{
    //
    private $wmsPlatform = "iwms";
    private $iwmsFactoryWmsService;
    private $courierMappingList;
    private $merchantCourierList;
    
    public function __construct(Request $request)
    {
        $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($this->wmsPlatform);
    }

    public function index(Request $request)
    {   
        $data["courierList"] = $this->iwmsFactoryWmsService->getCourierMappingList($this->wmsPlatform);
        $data["currentCourier"] = $request->input("courier");
        $data["esgOrderList"] = $this->iwmsFactoryWmsService->getReadyToIwmsCourierOrder($data["currentCourier"], 20);
        
        return response()->view('iwms.courier.index', $data);
    }

    public function createIwmsCourierOrder(Request $request)
    {
        $data = array();
        $data["courierList"] = $this->iwmsFactoryWmsService->getCourierMappingList($this->wmsPlatform);
        $data["currentCourier"] = $request->input("courier");
        $data["courierOrderList"] = $this->iwmsFactoryWmsService->getFailedIwmsCourierOrderLogList(20);
        return response()->view('iwms.courier.create', $data);
    }

    public function editIwmsCourierOrder(Request $request)
    {
        $data["courierList"] = $this->iwmsFactoryWmsService->getCourierMappingList($this->wmsPlatform);
        $data["currentCourier"] = $request->input("courier");
        $data["courierOrderList"] = $this->iwmsFactoryWmsService->getIwmsCourierOrderLogList(20);
        
        return response()->view('iwms.courier.edit', $data);
    }

    public function donwloadLabel($documentType, Request $request)
    {
        $soNo = $request->input("so_no");
        if(!empty($soNo)){
            $filePath = $this->iwmsFactoryWmsService->getCourierPickListFilePathByType($soNo, $documentType);
            return response()->download($filePath);
        }
    }

    /*public function donwloadPickListLabel($pickListNo, $documentType, Request $request)
    {
        return null;
        $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo."/".$documentType."/";
        $zipFileName = $filePath.$pickListNo.".zip";
        if(!file_exists($zipFileName)) {
            $files = File::files($filePath);
            Zipper::make($zipFileName)->add($files)->close();     
        }
        return response()->download($zipFileName); 
    }*/

}
