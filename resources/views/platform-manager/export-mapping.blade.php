@extends('layouts.demo')

@section('content')
<!-- page content -->
<style type="text/css">
    .main div{padding:10px 10px;}
    .demo-xls a{ text-decoration: underline; }
</style>
<div class="right_col" role="main">
    <div class="row main">
    <h3>Export LAZADA PRICE DETAILS</h3>
        <form  action="#" name="edit-form" method="post" enctype="multipart/form-data">
            <div>
                <div>
                    <input type="checkbox" name="all_marketplace" value="all">Export All Lazada accounts
                </div>
                <select name="marketplace_id">
                    <option value="">select Marketplace</option>
                    <option value="BCLAZADA">BCLAZADA</option>
                    <option value="3DLAZADA">3DLAZADA</option>
                    <option value="BMLAZADA">BMLAZADA</option>
                    <option value="CSLAZADA">CSLAZADA</option>
                    <option value="PXLAZADA">PXLAZADA</option>
                    <option value="MLLAZADA">MLLAZADA</option>  
                </select>
                <select name="country_code">
                    <option value="">select Country</option>
                    <option value="MY">MY</option>
                    <option value="SG">SG</option>
                    <option value="TH">TH</option>
                    <option value="PH">PH</option>
                    <option value="ID">ID</option>
                </select>
            </div>
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div><input type="submit" value="Submit"  name="submit"/></div>
        </form>
    </div>  
    
</div>
<!-- /page content -->
@endsection
