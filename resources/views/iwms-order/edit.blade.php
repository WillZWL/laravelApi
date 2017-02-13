@extends('layouts.iwms')

@section('content')
<!-- page content -->
<div class="right_col" role="main">
    <div class="main">
        <form  method ="post" name="fm_edit" id="fm_edit">
        <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SoNo</th>
                        <th>Merchant</th>
                        <th>Product Name</th>
                        <th>Store Name</th>
                        <th>WMS Order Code</th>
                        <th>Iwms Warehouse Code</th>
                        <th>Platform Id</th>
                        <th>Iwms Courier Code</th>
                        <th>Platform Order Id</th>
                        <th>Tracking No</th>
                        <th><input type="checkbox" name="chkall" value="1"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($deliveryOrderList))
                        @foreach($deliveryOrderList as $deliveryOrder)
                            <tr>
                                <td>{{ $deliveryOrder->reference_no }}</td>
                                <td>{{ $deliveryOrder->sub_merchant_id }}</td>
                                <td>{{ $deliveryOrder->store_name }}</td>
                                <td>{{ $deliveryOrder->wms_order_code }}</td>
                                <td>{{ $deliveryOrder->iwms_warehouse_code }}</td>
                                <td>{{ $deliveryOrder->marketplace_platform_id }}</td>
                                <td>{{ $deliveryOrder->iwms_courier_code }}</td>
                                <td>{{ $deliveryOrder->platform_order_id }}</td>
                                <td>{{ $deliveryOrder->tracking_no }}</td>
                                <td><input type="checkbox" name="so_no[]" value="{{$deliveryOrder->so_no}}"></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
            @if(isset($deliveryOrderList))
                {{ $deliveryOrderList->links() }} 
            @endif
            <div>
                <input type="button" value="Invoice" class="btn btn-primary"  onclick="
                document.fm_edit.action = '/iwms-order/label/invoice';document.fm_edit.submit();"/>
                <input type="button" value="AWB label" class="btn btn-primary"  onclick="
                document.fm_edit.action = '/iwms-order/label/awb';document.fm_edit.submit();"/>
                <input type="button" value="Manifest" class="btn btn-primary"  onclick="
                document.fm_edit.action = '/iwms-order/label/manifest';document.fm_edit.submit();"/>
                <input type="button" value="Cancel Order" class="btn btn-primary"  onclick="document.fm_edit.action = '/iwms-order/cancel';document.fm_edit.submit();"/>
            </div>
        </form>
    </div>  
</div>
<!-- /page content -->
@endsection
