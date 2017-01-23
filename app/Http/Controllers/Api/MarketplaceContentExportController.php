<?php

namespace App\Http\Controllers\Api;

use App\Services\MarketplaceContentExportService;
use App\Transformers\MarketplaceContentExportTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class MarketplaceContentExportController extends Controller
{
    use Helpers;

    private $marketplaceContentExportService;

    public function __construct(MarketplaceContentExportService $marketplaceContentExportService)
    {
        $this->marketplaceContentExportService = $marketplaceContentExportService;
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
     * @param  string  $marketplace
     * @return \Illuminate\Http\Response
     */
    public function show($marketplace)
    {
        $marketplaceContentExport = $this->marketplaceContentExportService->getMarketplaceContentExport($marketplace);

        return $this->collection($marketplaceContentExport, new MarketplaceContentExportTransformer());
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

    public function setting(Requests\MarketplaceContentExport\SettingRequest $request)
    {
        $result = $this->marketplaceContentExportService->setting($request->all());

        return response()->json($result);
    }

    public function download(Requests\MarketplaceContentExport\DownloadRequest $request)
    {
        $this->marketplaceContentExportService->download($request->all());
    }
}
