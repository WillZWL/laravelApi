<?php

namespace App\Http\Controllers\V2;

use App\Models\MpCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MarketplaceCategoryController extends Controller
{
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
        $parentId = $id;

        $categories = MpCategory::join('mp_control', 'mp_control.control_id', '=', 'mp_category.control_id')
            ->select(['mp_category.id', 'mp_category.control_id', 'mp_category.name', 'mp_category.parent_cat_id', \DB::raw(" '' as children ")])
            ->where('mp_category.parent_cat_id', $parentId)
            ->orderBy('id')
            ->get();

        $data = $categories->groupBy('control_id');

        return response()->json($data);
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
