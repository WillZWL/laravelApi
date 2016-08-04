@extends('layouts.demo')

@section('content')
<!-- page content -->
<style type="text/css">
	.main div{padding:10px 10px;}
</style>
<div class="right_col" role="main">
  	<div class="row main">
  	<h3>Marketplace SKU Mapping</h3>
		<form  action="#" name="edit-form" method="post" enctype="multipart/form-data">
			<div>
				<select name="check">
					<option value="">select platform</option>
					<option value="lazada">LAZADA</option>
				</select>
			</div>
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
			<div><input type="file" name="sku_file" id="sku_file"></div>
			<div><input type="submit" value="Submit"  name="submit"/></div>
		</form>
  	</div>	
  	
</div>
<!-- /page content -->
@endsection
