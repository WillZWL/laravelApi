@extends('layouts.demo')

@section('content')
<!-- page content -->
<div class="right_col" role="main">
    <div class="row title">
       <form name="api_select" id="api_select">
           <table>
                <tr>
                    <td>
                        <select name="api_platform" class="form-control" onchange="document.api_select.submit();">
                            <option value="">Platform</option>
                            <!--option value="amazon" @if($apiPlatform =="amazon") selected @endif>Amazon</option-->
                            <option value="lazada" @if($apiPlatform =="lazada") selected @endif>Lazada</option> 
                       </select>
                   </td>
                   <td></td>
                   <td></td>
                </tr>
            </table>
       </form>
    </div>
    <div class="row main">
        <form  method="" name="fm_edit" id="fm_edit">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Platform </th>
                        <th>Biz Type</th>
                        <th>Platform Order Id</th>
                        <th>Order Item Id</th>
                        <th>Sell SKU</th>
                        <th>Item Price</th>
                        <th>Shipping Price</th>
                        <th>Shipment Provider</th>
                        <th>Order Status</th>
                        <th><input type="checkbox" name="chkall" value="1"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($orderList))
                        @foreach($orderList as $order)
                            @foreach($order->platformMarketOrderItem as $orderItem)
                            <tr>
                                <td>{{ $order->platform }}<input type="hidden" value="{{ $order->platform }}" name="platform[{{$orderItem->order_item_id}}]"></td>
                                <td>{{ $order->biz_type }}</td>
                                <td>{{ $orderItem->platform_order_id }}</td>
                                <td>{{ $orderItem->order_item_id }}</td>
                                <td>{{ $orderItem->seller_sku }}</td>
                                <td>{{ $orderItem->item_price }}</td>
                                <td>{{ $orderItem->shipping_price }}</td>
                                <td>{{ $orderItem->shipment_provider }}</td>
                                <td>{{ $orderItem->status }}</td>
                                <td><input type="checkbox" name="check[]" value="{{$orderItem->order_item_id}}"></td>
                            </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
            @if(isset($orderList))
                {{ $orderList->links() }} 
            @endif
            <div>
                <input type="hidden" value="" name="dispatch_type" id="dispatch_type" />
                <input type="button" value="Cancel Order" class="btn btn-primary"  onclick="document.getElementById('dispatch_type').value='c';document.fm_edit.submit();"/>
                <input type="button" value="Shipped Order" class="btn btn-primary" onclick="document.getElementById('dispatch_type').value='s';document.fm_edit.submit();"/>
                <input type="button" value="Ready To Shiped Order" class="btn btn-primary" onclick="document.getElementById('dispatch_type').value='r';document.fm_edit.submit();"/>
            </div>
        </form>
    </div>  
    
</div>
<!-- /page content -->
@endsection
