@extends('layouts.demo')

@section('content')
<!-- page content -->
<div class="right_col" role="main">
  	<div class="row main">
		<form  action="#" name="edit-form" id="order">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>Platform </th>
						<th>Biz Type</th>
						<th>Platform Order Id</th>
						<th>Order Status</th>
						<th>Total Amount</th>
						<th>Currency</th>
						<th>Payment Method</th>
						<th>Purchase Date</th>
						<th><input type="checkbox" name="chkall" value="1"></th>
					</tr>
				</thead>
				<tbody>
					@foreach($orderList as $order)
					<tr>
						<td>{{ $order->platform }}</td>
						<td>{{ $order->biz_type }}</td>
						<td>{{ $order->platform_order_id }}</td>
						<td>{{ $order->order_status }}</td>
						<td>{{ $order->total_amount }}</td>
						<td>{{ $order->currency }}</td>
						<td>{{ $order->payment_method }}</td>
						<td>{{ $order->purchase_date }}</td>
						<td><input type="checkbox" name="check[]" value="{{$order->id}}"></td>
					</tr>
					@endforeach
				</tbody>
			</table>
			{{ $orderList->links() }}
			<div><input type="submit" value="Transfer Order"  name=""/></div>
		</form>
  	</div>	
  	
</div>
<!-- /page content -->
@endsection
