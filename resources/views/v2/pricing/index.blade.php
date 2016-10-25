@extends('layouts.pricing')
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
        <tr class="marketplaceSku {{ $item->marketplace_sku == old('marketplaceSku') ? 'success' : '' }}">
          <td class="col-md-4">
            <a data-pjax id="{{ $item->marketplace_sku }}"
               href="{{ url('/v2/pricing/index').'?marketplace='.old('marketplace').'&search='.old('search').'&marketplaceSku='.$item->marketplace_sku }}">
              {{ $item->marketplace_sku }}
            </a>
          </td>
          <td>{{ $item->name }}</td>
        </tr>
      @empty
        <tr>
          <td>No SKU Found.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
@section('content')
  <?php
    $lang = [
      'A' => 'Readily Available',
      'C' => 'Stock Constraint',
      'O' => 'Temp Out Of Stock',
      'L' => 'Last Lot',
      'D' => 'Discontinued',
    ];
  ?>
  <div id="sku-listing-info">
    <div class="row">
      <div class="col-sm-12">
        @if( $selectedSku = $skuList->where('marketplace_sku', old('marketplaceSku'))->pop())
          <p>
            ESG SKU : <span class="text-danger">{{ $selectedSku->sku}} </span> |
            Product Name : <span class="text-danger">{{ $selectedSku->name }}</span>
          </p>
          <p>
            Supplier Name : <span class="text-danger"> {{ $selectedSku->supplierProduct->supplier->name }} </span> |
            Supply Status : <span class="text-danger"> {{ $lang[$selectedSku->supplierProduct->supplier_status] }}</span> |
            Default WH : <span class="text-danger"> {{ $selectedSku->product->default_ship_to_warehouse or $selectedSku->product->merchantProductMapping->merchant->default_ship_to_warehouse }}</span> |
            Surplus: <span class="text-danger"> {{ $selectedSku->product->surplus_quantity }} </span>
          </p>
          <p>
            <?php $inventoryCollection = $selectedSku->inventory()->get() ?>

            <span class="warehouse text-danger">ES_DGME : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ES_DGME')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ES_HK : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ES_HK')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ETRADE : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ETRADE')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">4PXDG_PL : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', '4PXDG_PL')->first()) ? $warehouse->inventory : 0 }}</span>
            <br>
            <span class="warehouse text-danger">CV_AMZ_FBA_UK : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'CV_AMZ_FBA_UK')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">CV_AMZ_FBA_US : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'CV_AMZ_FBA_US')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ESG_AMZN_JP_FBA : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ESG_AMZN_JP_FBA')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ESG_AMZN_UK_FBA : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ESG_AMZN_UK_FBA')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ESG_AMZN_US_FBA : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ESG_AMZN_US_FBA')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">PX_AMZN_FBA_UK : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'PX_AMZN_FBA_UK')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">PX_AMZN_FBA_US : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'PX_AMZN_FBA_US')->first()) ? $warehouse->inventory : 0 }}</span>
            <span class="warehouse text-danger">ESG_NEWEGG_US_SBN : </span>
            <span class="inventory">{{ ($warehouse = $inventoryCollection->where('warehouse_id', 'ESG_NEWEGG_US_SBN')->first()) ? $warehouse->inventory : 0 }}</span>
          </p>
        @endif
      </div>
    </div>
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
      @include('v2.pricing.platform-pricing-info')
    </div>
  </div>
@endsection

