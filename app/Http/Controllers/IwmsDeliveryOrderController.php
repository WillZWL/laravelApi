<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;
use Config;

class IwmsDeliveryOrderController extends Controller
{
    //
    private $iwmsFactoryWmsService;
    private $courierMappingList;
    private $merchantCourierList;
    
    public function __construct(Request $request)
    {
        $this->initIwmsService = $this->initIwmsService($request);
    }

    public function index(Request $request)
    {   
        $data["courierList"] = $this->courierMappingList;
        $data["currentWmsPlatform"]= $this->wmsPlatform;
        if(!empty($this->iwmsFactoryWmsService)){
            $courier = $request->input("courier");
            $data["esgOrderList"] = $this->iwmsFactoryWmsService->getReadyToDispatchOrder($courier,$this->merchantCourierList);
        }
        return response()->view('iwms-order.index', $data);
    }

    public function createOrUpdateIwmsOrder(Request $request)
    {
        $data = array();
        if(!empty($this->iwmsFactoryWmsService)){
            $esgOrderNoList = $request->input("so_no");
            $data["batchOrderList"] = $this->iwmsFactoryWmsService->updateIwmsOrderCreateLog($esgOrderNoList);
        }
        return response()->view('iwms-order.create', $data);
    }

    public function editIwmsDeliveryOrder(Request $request)
    {
        $data["currentWmsPlatform"] = $this->wmsPlatform;
        if(!empty($this->iwmsFactoryWmsService)){
            $data["deliveryOrderList"] = $this->iwmsFactoryWmsService->getIwmsDeliveryOrderLogList();
        }
        return response()->view('iwms-order.edit', $data);
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

    public function getDeliveryOrderDocument($documentType, Request $request)
    {
        $result = null;
        if(!empty($this->iwmsFactoryWmsService)){
            $esgOrderNoList = $request->input("so_no");
            $result = $this->iwmsFactoryWmsService->getDeliveryOrderDocument($esgOrderNoList, $documentType); 
        }
        return \Response::json($result);
    }

    public function donwloadLabel($doucment)
    {
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix()."label/";
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/";
        if($doucment){
            return response()->download($pdfFilePath.$doucment);   
        } 
    }

    private function initIwmsService(Request $request)
    {
        $this->wmsPlatform = $request->input("wms-platform");
        if(!empty($this->wmsPlatform)){
            $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($this->wmsPlatform);
            $this->courierMappingList = $this->iwmsFactoryWmsService->getCourierMappingList($this->wmsPlatform);
            foreach ($this->courierMappingList as $courier) {
               $this->merchantCourierList[] = $courier->merchant_courier_id;
           }
        }
    }
}
