@extends('layouts.pricing')

@section('search-form')
<form data-pjax action="{{ url('v2/pricing/index') }}" class="form-horizontal" id="search" method="GET" role="form">
    <div class="col-md-9 col-md-offset-1">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="master_sku">Master SKU:</label>
                    <input type="text" class="input-sm" id="master_sku" name="master_sku" value="">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="esg_sku">ESG SKU:</label>
                    <input type="text" class="input-sm" id="esg_sku" name="esg_sku" value="">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" class="input-sm" id="product_name" name="product_name" value="">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="brand_id" class="col-sm-2 control-label">Brand:</label>
                    <div class="col-sm-6">
                        <select name="brand_id" id="brand_id" class="form-control input-sm">
                            <option value=""></option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="marketplace" class="col-sm-3 control-label">Marketplace:</label>
                    <div class="col-sm-6">
                        <select name="marketplace" id="marketplace" class="form-control input-sm" required="required">
                            <option value="">--Select--</option>
                            @foreach($marketplaces as $marketplace)
                                <option value="{{ $marketplace->id }}">{{ $marketplace->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form>
@endsection

@section('sku-list')
    <div id="sku-list">
        <table class="table table-condensed table-responsive table-striped table-hover table-bordered">
            <thead>
            <tr>
                <th>Ref.SKU</th>
                <th>Name</th>
            </tr>
            </thead>
            <tbody>
            @forelse($skuList as $item)
                <tr>
                    <td class="col-md-4">
                        <a id="{{ $item->marketplace_sku }}" href="#{{ $item->marketplace_sku }}">
                            {{ $item->marketplace_sku }}
                        </a>
                    </td>
                    <td>{{ $item->name }}</td>
                </tr>
            @empty
                <tr><td>No SKU Found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{--<ul class="list-unstyled">--}}
        {{--@forelse($data as $item)--}}
        {{--<li>--}}
        {{--<a href="?marketplace={{ $item->marketplace_id }}&marketplaceSku={{ $item->marketplace_sku }}" class="marketplace-sku">--}}
        {{--{{ $item->marketplace_sku }}--}}
        {{--</a>--}}
        {{--<br>--}}
        {{--{{ $item->name }}--}}
        {{--</li>--}}
        {{--@empty--}}
        {{--<li>--}}
        {{--No result.--}}
        {{--</li>--}}
        {{--@endforelse--}}
        {{--</ul>--}}
    </div>
@endsection


