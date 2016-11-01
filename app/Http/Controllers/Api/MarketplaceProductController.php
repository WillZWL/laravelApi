<?php

namespace App\Http\Controllers\Api;

use App\Services\MarketplaceProductService;
use App\Transformers\InventoryFeedTransfer;
use App\Transformers\MarketplaceProductTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Excel;

class MarketplaceProductController extends Controller
{
    use Helpers;

    private $marketplaceProductService;

    public function __construct(MarketplaceProductService $marketplaceProductService)
    {
        $this->marketplaceProductService = $marketplaceProductService;
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function search(Requests\MarketplaceProductSearchRequest $searchRequest)
    {
        $products = $this->marketplaceProductService->searchWithProfit($searchRequest);

        return $this->response->paginator($products, new MarketplaceProductTransformer());
    }

    public function export(Requests\MarketplaceProductSearchRequest $searchRequest)
    {
        $searchRequest->merge(['per_page' => '']);
        $excel = $this->marketplaceProductService->export($searchRequest);

        return $excel->download('csv');
    }

    public function estimate(Requests\ProfitEstimateRequest $profitRequest)
    {
        return response()->json($this->marketplaceProductService->estimate($profitRequest));
    }

    public function bulkUpdate(Requests\BatchUpdateMarketplaceProductRequest $bulkUpdateRequest)
    {
        return $this->marketplaceProductService->update($bulkUpdateRequest);
    }

    public function addOrUpdate(Request $request)
    {
        return response()->json($request);
    }
}
