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

    public function createOrUpdateIwmsOrder(Request $request)
    {
        $data = array();
        $esgOrderNoList = $request->input("so_no");
        $data["batchOrderList"] = $this->iwmsFactoryWmsService->updateIwmsOrderCreateLog($esgOrderNoList);
        
        return response()->view('iwms.courier.create', $data);
    }

    public function editIwmsDeliveryOrder(Request $request)
    {
        $data["courierList"] = $this->iwmsFactoryWmsService->getCourierMappingList($this->wmsPlatform);
        $data["currentCourier"] = $request->input("courier");
        $data["courierOrderList"] = $this->iwmsFactoryWmsService->getIwmsCourierOrderLogList(20);
        return response()->view('iwms.courier.edit', $data);
    }

    public function cancelIwmsOrder(Request $request)
    {
        $data = array();
        if(!empty($this->iwmsFactoryWmsService)){
            $esgOrderNoList = $request->input("so_no");
            $result = $this->iwmsFactoryWmsService->cancelDeliveryOrder($esgOrderNoList);
        }
        return \Response::json($result);
    }

    /*public function getDeliveryOrderDocument($documentType, Request $request)
    {
        $result = null;
        if(!empty($this->iwmsFactoryWmsService)){
            $esgOrderNoList = $request->input("so_no");
            $result = $this->iwmsFactoryWmsService->getDeliveryOrderDocument($esgOrderNoList, $documentType); 
        }
        return \Response::json($result);
    }*/

    public function donwloadLabel($pickListNo, $documentType, Request $request)
    {
        $soNo = $request->input("so_no");
        if(!empty($soNo)){
            //$pickListNo = $this->iwmsFactoryWmsService->getSoAllocatePickListNo($soNo);
            $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo."/".$documentType."/"; 
            return response()->download($filePath.$soNo.".pdf"); 
        }
    }

    public function donwloadPickListLabel($pickListNo, $documentType, Request $request)
    {
        $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo."/".$documentType."/";
        $zipFileName = $filePath.$pickListNo.".zip";
        if(!file_exists($zipFileName)) {
            $files = File::files($filePath);
            Zipper::make($zipFileName)->add($files)->close();     
        }
        return response()->download($zipFileName); 
    }

}
