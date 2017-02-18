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
                        <th>SoNo</th>
                        <th>Courier ID</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Delivery Name</th>
                        <th>Email</th>
                        <th>Delivery Address</th>
                        <th>Delivery City</th>
                        <th>Delivery State</th>
                        <th>Phone</th>
                        <th>Delivery PostCode</th>
                        <th>Delivery Country</th>
                        <th>Unit Price</th>
                        <th>Declared Desc</th>
                        <th>HsCode</th>
                        <th>Declared Value</th>
                        <th>Declared Currency</th>
                        <th><input type="checkbox" name="chkall" value="1"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($esgOrderList))
                        @foreach($esgOrderList as $esgOrder)
                            @foreach($esgOrder->soItem as $esgOrderItem)
                            <tr>
                                <td>{{ $esgOrder->so_no }}</td>
                                <td>{{ $esgOrder->esg_quotation_courier_id }}</td>
                                <td>{{ $esgOrderItem->product->name }}</td>
                                <td>{{ $esgOrderItem->qty }}</td>
                                <td>{{ $esgOrderItem->prod_sku }}</td>
                                <td>{{ $esgOrder->delivery_name }}</td>
                                <td>{{ $esgOrder->Email }}</td>
                                <td>{{ $esgOrder->delivery_address }}</td>
                                <td>{{ $esgOrder->delivery_city }}</td>
                                <td>{{ $esgOrder->delivery_state }}</td>
                                <td>{{ $esgOrder->phone }}</td>
                                <td>{{ $esgOrder->delivery_postcode }}</td>
                                <td>{{ $esgOrder->delivery_country_id }}</td>
                                <td>{{ $esgOrderItem->unit_price }}</td>
                                @if(isset($esgOrderItem->hscodeCategory))
                                <td>{{ $esgOrderItem->hscodeCategory->general_hscode }}</td>
                                <td>{{ $esgOrderItem->hscodeCategory->description }}</td>
                                @else
                                <td></td>
                                <td></td>
                                @endif
                                <td>{{ $esgOrder->declared_value }}</td>
                                <td>{{ $esgOrder->currency_id }}</td>
                                <td><input type="checkbox" name="so_no[]" value="{{$esgOrder->so_no}}"></td>
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
                <input type="button" value="Ship Order" class="btn btn-primary"  onclick="
                document.fm_edit.action = '/iwms-order/create';document.fm_edit.submit();"/>
                <!--input type="button" value="Cancel Order" class="btn btn-primary"  onclick="document.fm_edit.action = '/iwms-order/cancel';document.fm_edit.submit();"/-->
            </div>
        </form>
    </div>  
</div>
<!-- /page content -->
@endsection
