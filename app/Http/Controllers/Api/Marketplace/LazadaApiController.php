<?php

namespace App\Http\Controllers\Api\Marketplace;

use Dingo\Api\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\ApiLazadaService;
use Dingo\Api\Routing\Helpers;
use App\Transformers\LazadaAllocatedTransformer;
use Illuminate\Support\Collection;
use PDF;

class LazadaApiController extends Controller
{
    use Helpers;
    private $apiLazadaService;
    
    public function __construct(ApiLazadaService $apiLazadaService)
    {
        $this->apiLazadaService = $apiLazadaService;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getShipmentProviders(Request $request)
    {
        
        //$storeName = $request->input("storeName");
        /*$storeNameArr =["BCLAZADAMY","BCLAZADASG","BCLAZADATH","BCLAZADAID","BCLAZADAPH"];
        foreach( $storeNameArr as  $storeName){
            $shipmentProviders[]= $this->apiLazadaService->getShipmentProviders($storeName);
        }
        return  $shipmentProviders;*/
        $storeName="BCLAZADASG";
        return $this->apiLazadaService->getShipmentProviders($storeName);
      
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDoucment(Request $request)
    {   
        $storeName = $request->input("storeName");
        $orderItemIds = $request->input("orderItemIds");
        $documentType = $request->input("documentType");
        $filePdf = $this->apiLazadaService->getDocument($storeName,$orderItemIds,$documentType);
        return PDF::loadHTML($filePdf)->inline($documentType.'.pdf');
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function esgOrderReadyToShip(Request $request)
    {  
        $soNoList = $request->input("so_no");
        $result = $this->apiLazadaService->esgOrderReadyToShip($soNoList);
        return \Response::json($result);
    }

    public function donwloadLazadaLabelFile($doucment)
    {
        $pdfFilePath = "/var/data/shop.eservicesgroup.com/marketplace/".date("Y")."/".date("m")."/".date("d")."/lazada/label";
        if($doucment){
            return response()->download($pdfFilePath.$doucment);   
        }  
    }

    public function index(Request $request)
    {

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
        //
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
