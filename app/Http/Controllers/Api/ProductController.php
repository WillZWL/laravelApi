<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use App\Services\FileUploadService;
use App\Transformers\ProductTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;

class ProductController extends Controller
{
    use Helpers;

    private $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
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
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\Product\CreateRequest $request)
    {
        $result = $this->productService->store($request->all());

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function show($sku, $lang='en')
    {
        $product = $this->productService->getProduct($sku);

        return $this->item($product, new productTransformer($lang));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function edit($sku)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\Product\UpdateRequest $request, $sku)
    {
        $result = $this->productService->update($request->all(), $sku);

        return response()->json($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function destroy($sku)
    {
        //
    }

    public function productMapping(Requests\Product\MappingRequest $request)
    {
        $result = $this->productService->productMapping($request->all());

        return response()->json($result);
    }

    public function weightDimension(Requests\Product\WeightDimensionRequest $request) {
        $result = $this->productService->weightDimension($request->all());

        return response()->json($result);
    }

    public function supplierProduct(Requests\Product\SupplierProductRequest $request) {
        $result = $this->productService->supplierProduct($request->all());

        return response()->json($result);
    }

    public function productContent(Requests\Product\ProductContentRequest $request) {
        $result = $this->productService->productContent($request->all());

        return response()->json($result);
    }

    public function productCode(Requests\Product\ProductCodeRequest $request) {
        $result = $this->productService->productCode($request->all());

        return response()->json($result);
    }

    public function productFeatures(Requests\Product\ProductFeaturesRequest $request) {
        $result = $this->productService->productFeatures($request->all());

        return response()->json($result);
    }

    public function uploadProductImage(Requests\Product\UploadImageRequest $request)
    {
        $result = $this->productService->saveProductImage($request->all());

        return response()->json($result);
    }

    public function deleteImage(Requests\Product\DeleteImageRequest $request)
    {
        \Log::info('yyy');
        $result = $this->productService->deleteImage($request->all());

        return response()->json($result);
    }
}
