<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller;
use App\Services\MarketplaceCourierMappingService;
use App\Transformers\MarketplaceCourierMappingTransformer;

class MarketplaceCourierMappingController extends Controller
{
    use Helpers;

    private $marketplaceCourierMappingService;

    public function __construct(MarketplaceCourierMappingService $marketplaceCourierMappingService)
    {
        $this->marketplaceCourierMappingService = $marketplaceCourierMappingService;
    }

    public function index(Request $request)
    {
        $mappings = $this->marketplaceCourierMappingService->getAllMappings($request->all());
        return $this->collection($mappings, new MarketplaceCourierMappingTransformer());
    }


    public function store(Requests\MarketplaceCourierMappingRequest $request)
    {
        $result = $this->marketplaceCourierMappingService->store($request->all());
        return response()->json($result);
    }

    public function update(Requests\MarketplaceCourierMappingRequest $request)
    {
        $result = $this->marketplaceCourierMappingService->update($request);
        return response()->json($result);
    }
}
