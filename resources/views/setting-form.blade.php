@extends('layouts.tracer')

@section('setting-form')

    <section>
        <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
                <form action="{{ url('tracer').'/'.$tracerSku->id }}" method="POST" class="form-horizontal" role="form">
                    <div class="form-group">
                        <legend>{{ $tracerSku->product->name }} : {{ $tracerSku->product->sku  }}</legend>
                    </div>

                    <div class="form-group">
                        <label for="inputCost" class="col-sm-3 control-label">Cost Price (HKD):</label>
                        <div class="col-sm-7">
                            <input type="number" step="0.01" name="costhkd" id="inputCost" class="form-control" value="{{ $tracerSku->supplierProduct->pricehkd}}"
                                   required="required" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputLength" class="col-sm-3 control-label">Length (cm):</label>
                        <div class="col-sm-7">
                            <input type="number" step="0.1" name="length" id="inputLength" class="form-control" value="{{ $tracerSku->product->length }}" required="required" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputWidth" class="col-sm-3 control-label">Width (cm):</label>
                        <div class="col-sm-7">
                            <input type="number" step="0.1" name="width" id="inputWidth" class="form-control" value="{{ $tracerSku->product->width }}" required="required" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputHeight" class="col-sm-3 control-label">Height (cm):</label>
                        <div class="col-sm-7">
                            <input type="number" step="0.1" name="height" id="inputHeight" class="form-control" value="{{ $tracerSku->product->height }}" required="required" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputFactor" class="col-sm-3 control-label">Chargeable Weight Factor:</label>
                        <div class="col-sm-7">
                            <input type="number" name="factor" id="inputFactor" class="form-control" value="6000" disabled="disabled" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputVolumetricWeight" class="col-sm-3 control-label">Volumetric Weight (Kg):</label>
                        <div class="col-sm-7">
                            <input type="number" name="volumetricWeight" id="inputVolumetricWeight" class="form-control" value="{{ $tracerSku->product->vol_weight }}" max="30" step="any" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputWeight" class="col-sm-3 control-label">Weight (Kg):</label>
                        <div class="col-sm-7">
                            <input type="number" name="weight" id="inputWeight" class="form-control" value="{{ $tracerSku->product->weight }}" max="30" step="any"
                                   required="required" title="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputWarehouse" class="col-sm-3 control-label">Warehouse:</label>
                        <div class="col-sm-3">
                            <select name="default_warehouse" id="inputWarehouse" class="form-control" required="required">
                                <option value="ES_HK" {{ $tracerSku->product->default_ship_to_warehouse == 'ES_HK' ? 'selected' : '' }}>ES_HK</option>
                                <option value="ES_DGME" {{ $tracerSku->product->default_ship_to_warehouse == 'ES_DGME' ? 'selected' : '' }}>ES_DGME</option>
                                <option value="4PXDG_PL" {{ $tracerSku->product->default_ship_to_warehouse == '4PXDG_PL' ? 'selected' : '' }}>4PXDG_PL</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputMarketplace" class="col-sm-3 control-label">Marketplace:</label>
                        <div class="col-sm-3">
                            <select name="marketplace" id="inputMarketplace" class="form-control" required="required">
                                @foreach($mpControls as $mpControl)
                                    <option value="{{ $mpControl->control_id }}" {{ ($mpControl->control_id == $tracerSku->mp_control_id) ? 'selected' : '' }}>
                                    {{ $mpControl->marketplace_id.$mpControl->country_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputCategory" class="col-sm-3 control-label">Category:</label>
                        <div class="col-sm-4">
                            <select name="category" id="inputCategory" class="form-control" required="required">
                                @foreach($topCategories as $topCategory)
                                    <option value="{{ $topCategory->id }}" {{ ($topCategory->id == $tracerSku->mp_category_id) ? 'selected' : '' }}>{{ $topCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputSubCategory" class="col-sm-3 control-label">SubCategory:</label>
                        <div class="col-sm-4">
                            <select name="subCategory" id="inputSubCategory" class="form-control" required="required">
                                <option value="{{ $selectedSubCategory->id }}" selected>{{ $selectedSubCategory->name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-7 col-sm-offset-3">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                    <input type="hidden" name="_method" value="PUT">
                    {{ csrf_field() }}
                </form>
            </div>
        </div>
    </section>

@endsection
