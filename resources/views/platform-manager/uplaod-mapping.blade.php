@extends('layouts.demo')

@section('content')
<!-- page content -->
<style type="text/css">
	.main div{padding:10px 10px;}
	.demo-xls a{ text-decoration: underline; }
</style>
<div class="right_col" role="main">
  	<div class="row main">
  	<h3>Marketplace SKU2222 Mapping</h3>
		<form  action="#" name="edit-form" method="post" enctype="multipart/form-data">
			<div>
				<select name="check">
					<option value="">select platform</option>
					<option value="allegro">ALLEGRO</option>
					<option value="amazon">Amazon</option>
					<!--option value="cdiscount">Cdiscount</option-->
					<option value="ebay">EBAY</option>
					<option value="fnac">FNAC</option>
					<option value="lazada">LAZADA</option>
					<option value="linio">LINIO</option>
					<!--option value="paytm">PAYTM</option-->
					<option value="priceminister">PriceMinister</option>
					<option value="mercadolibre">MERCADOLIBRE</option>
					<option value="newegg">NEWEGG</option>
					<option value="qoo10">QOO10</option>
					<option value="tanga">TANGA</option>
					<!--option value="wish">Wish</option-->
				</select>
			</div>
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
			<div><input type="file" name="sku_file" id="sku_file"></div>
			<div><input type="submit" value="Submit"  name="submit"/></div>
			<div class="demo-xls">
				<a href="download-xlsx/example.xlsx">Download Example.xlsx</a>
			</div>

			<div>Note: If there is data in the field(country_id), the function will only  add the SKU mapping on this country store, Leave blank for  the field(country_id) will be add SKU mapping for all  country stores.</div>
		</form>
  	</div>	
  	
</div>
<!-- /page content -->
@endsection
