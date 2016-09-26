<?php

namespace App\Http\Controllers\Api\Marketplace;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Services\ApiPlatformFactoryService;
use PDF;

class MerchantApiController extends Controller
{
    
    public function __construct()
    {
        $this->apiPlatformFactoryService = \App::make('App\Services\ApiPlatformFactoryService', array('apiName' => 'lazada'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function orderFufillmentAction(Request $request)
    {
        $result = null;
        $orderIds = $request->input("id");
        $action = $request->input("action");
        if($action == "readyToShip"){
            $result = $this->apiPlatformFactoryService->merchantOrderFufillmentReadyToShip($orderIds);
        }else if($action == "cancelOrder"){
            $orderParam["reason"] = $request->input("reason");
            $orderParam["reasonDetail"] = $request->input("reason_detail");
            $result = $this->apiPlatformFactoryService->setMerchantOrderCanceled($orderIds,$orderParam);
        }else if($action == "getDocument"){
            $doucmentType = $request->input("document_type");
            $result = $this->apiPlatformFactoryService->merchantOrderFufillmentGetDocument($orderIds,$doucmentType);
        }
        return \Response::json($result);
    }

    public function getPickingList(Request $request)
    {
        $orderIds = $request->input("id");
        $result = $this->apiPlatformFactoryService->getOrderFufillmentPickingList($orderIds);
        if($result){
            $returnHTML = view('picklist')->with('orderList', $result)->render();
            $filePath = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix();
            $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/label/";
            $file = "picklist-".date("H-s-i").'.pdf';
            PDF::loadHTML($returnHTML)->save($pdfFilePath.$file);
            $pdfFile = url("api/merchant-api/download-label/".$file);
            $result = array("status"=>"success","document"=>$pdfFile);
            return \Response::json($result);
        }
    }

    public function donwloadLabel($doucment)
    {
        $filePath = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix();
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/label/";
        if($doucment){
            return response()->download($pdfFilePath.$doucment);   
        } 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
