<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceSkuMapping;
use App\Models\MpCategory;
use App\Models\MpControl;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class TracerSkuController extends Controller
{
    use Helpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
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
        if ($id != 2305) {
            return redirect('/tracer/2305/edit');
        }

        $id = 2305;
        $tracerSku = MarketplaceSkuMapping::find($id);

        $mpControls = MpControl::whereStatus(1)->get(['control_id', 'marketplace_id', 'country_id']);

        $topCategories = (new MarketplaceCategoryController())->showTopCategoriesForControlId($tracerSku->mp_control_id);
        $selectedSubCategory = MpCategory::find($tracerSku->mp_sub_category_id);

        return view('setting-form', compact('tracerSku', 'mpControls', 'topCategories', 'selectedSubCategory'));
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
        $ids = [2305, 36458, 36459, 36460, 36461, 36462];
        if (!in_array($id, $ids)) {
             return;
        }
        $marketplaceProduct = MarketplaceSkuMapping::find($id);
        $marketplaceProduct->mp_category_id = $request->input('category');
        $marketplaceProduct->mp_sub_category_id = $request->input('subCategory');
        $marketplaceProduct->mp_control_id = $request->input('marketplace');
        $marketplaceProduct->load('mpControl');
        $marketplaceProduct->marketplace_id = $marketplaceProduct->mpControl->marketplace_id;
        $marketplaceProduct->currency = $marketplaceProduct->mpControl->currency_id;
        $marketplaceProduct->country_id = $marketplaceProduct->mpControl->country_id;
        $marketplaceProduct->save();

        $marketplaceProduct->product->weight = $request->input('weight');
        $marketplaceProduct->product->vol_weight = $request->input('volumetricWeight');
        $marketplaceProduct->product->length = $request->input('length');
        $marketplaceProduct->product->width = $request->input('width');
        $marketplaceProduct->product->height = $request->input('height');
        $marketplaceProduct->product->default_ship_to_warehouse = $request->input('default_warehouse');
        $marketplaceProduct->product->save();

        $this->api->be(auth()->user())->put('product-size-tier/'.$id);
        $this->api->be(auth()->user())->put('fba-fees/', $id);

        \DB::connection('mysql_esg')->table('supplier_prod')->where('prod_sku', $marketplaceProduct->product->sku)
            ->where('order_default', 1)
            ->update(['pricehkd' => $request->input('costhkd')]);

        return redirect('/tracer/'.$id.'/edit');
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
    }
}
