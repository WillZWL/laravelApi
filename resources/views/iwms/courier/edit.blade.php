@extends('layouts.iwms')

@section('content')
<!-- page content -->
<div class="right_col" role="main">
    <div class="title">
       <form name="courier_select" id="courier_select">
           <table>
                <tr>
                   <td><select name="courier" class="form-control" onchange="document.courier_select.submit();">
                            <option value="">Courier</option>
                            @if(isset($courierList))
                                @foreach($courierList as $courier)
                                    <option value="{{ $courier->merchant_courier_id }} @if( $currentCourier == "dhl") selected="selected" @endif">
                                        {{ $courier->merchant_courier_name }}
                                    </option>
                                @endforeach
                            @endif
                       </select></td>
                   <td></td>
                </tr>
            </table>
       </form>
    </div>
    <div class="main">
        <form  method ="post" name="fm_edit" id="fm_edit">
        <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Batch Id</th>
                        <th>SoNo</th>
                        <th>Merchant</th>
                        <th>Store Name</th>
                        <th>Platform Id</th>
                        <th>ESG Courier ID</th>
                        <th>Platform Order Id</th>
                        <th>Tracking No</th>
                        <th>AWB</th>
                        <th>Custom Invoice</th>
                        <th>Delivery Note</th>
                        <th><input type="checkbox" name="chkall" value="1"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($courierOrderList))
                        @foreach($courierOrderList as $courierOrder)
                            <tr>
                                <td>{{ $courierOrder->batch_id }}</td>
                                <td>{{ $courierOrder->reference_no }}</td>
                                <td>{{ $courierOrder->sub_merchant_id }}</td>
                                <td>{{ $courierOrder->store_name }}</td>
                                <td>{{ $courierOrder->marketplace_platform_id }}</td>
                                <td>{{ $courierOrder->so->esg_quotation_courier_id }}</td>
                                <td>{{ $courierOrder->platform_order_id }}</td>
                                <td>{{ $courierOrder->wms_order_code }}</td>
                                <td>
                                    <a href="/iwms/{{ $courierOrder->so->pick_list_no }}/AWB?so_no={{ $courierOrder->reference_no }}">downloand</a>
                                </td>
                                <td>
                                    <a href="/order/{{ $courierOrder->so->pick_list_no }}/invoice?so_no={{ $courierOrder->reference_no }}">downloand</a>
                                </td>
                                <td>
                                    <a href="/order/{{ $courierOrder->so->pick_list_no }}/dnote?so_no={{ $courierOrder->reference_no }} ">downloand</a>
                                </td>
                                <td>{{ $courierOrder->tracking_no }}</td>
                                <td><input type="checkbox" name="so_no[]" value="{{$courierOrder->so_no}}"></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
            @if(isset($courierOrderList))
                {{ $courierOrderList->links() }} 
            @endif
            <div>
                <input type="button" value="Cancel Order" class="btn btn-primary"  onclick="document.fm_edit.action = '/iwms/courier-order/cancel';document.fm_edit.submit();"/>
            </div>
        </form>
    </div>  
</div>
<!-- /page content -->
@endsection
