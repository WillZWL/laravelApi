<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dispatch Note</title>
<style type="text/css">
* {
font-family:Helvetica,verdana,arial,sans-serif;
font-size:8pt;
}
</style>
</head>
<body bgcolor="#FFFFFF" style="overflow:none;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF">
    <tr>
        <td align="left" colspan="2">
        </td>
    </tr>

    <tr>
        <td width="50%" valign=top>
            <b>Order Number:</b>
            <p>{{ $so->client_id }}-{{ $so->so_no }}({{ $so->platform_order_id }})</p>
            <p>&nbsp;</p>
        </td>

        <td width="50%" valign=top>
            <b>Order Date:</b>
            <p>{{ date("d/m/Y",strtotime($so->order_create_date)) }}</p>
            <p>&nbsp;</p>
        </td>
    </tr>

    <tr>
        <td width="50%" valign=top>
            <b>Ship To:</b>
            <p>{{ $so->delivery_name }}</p>
            <p>&nbsp;</p>
            <p>{!! $delivery_address !!}</p>
            <p>&nbsp;</p>
        </td>

        <td width="50%" valign=top>
            <b>Bill To:</b>
            <p>{{ $so->bill_name }}</p>
            <p>&nbsp;</p>
            <p>{!! $billing_address !!}</p>
            <p>&nbsp;</p>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <table border="1" cellpadding="4" cellspacing="0" width="100%">
                <tr>
                    <td colspan=4 valign=top bgcolor="#FFCC00" bordercolor="#666666" height="10px">
                        <b>Order Details</b>
                    </td>
                </tr>
                <tr>
                    <td width=521 colspan=2 valign=top height="10px" bgcolor="#CCCCCC">
                        <b>Description</b>
                    </td>
                    <td width=117 valign=top height="10px" bgcolor="#CCCCCC">
                        <b>Battery Type</b>
                    </td>
                    <td width=117 valign=top height="10px" bgcolor="#CCCCCC">
                        <b>Quantity</b>
                    </td>
                </tr>
            <!--item start-->
                @foreach($soItem as $item)
                <tr @if($item['assembly'] == 2) style="background:#CCC;" @endif>
                    <td align="center">
                        <img src="{{ $item['imagePath'] }}"><br>
                        {{ $item['merchant_sku'] }}
                    </td>
                    <td valign="top">
                        @if($item['assembly'] == 0)
                        {{ $item['item_sku'] }} - {{ $item['name'] }}
                        @else
                        {{ $item['main_prod_sku'] }} - {{ $item['item_sku'] }} - {{ $item['prod_name'] }} - {{ $item['name'] }}
                        @endif

                        @if(in_array($item['item_sku'],['15768-AA-NA', '15767-AA-NA', '15766-AA-NA', '15765-AA-NA']))
                        <br>
                        <div style="text-align: center;vertical-align: middle;float:right; padding: 5px 8px;">
                            <img src='data:image/png;base64,{{ DNS1D::getBarcodePNG($item["item_sku"], "C128", 1, 20) }}' alt='barcode'/><br/>
                            <span style="font-size: 8px">{{ $item["item_sku"] }}</span>
                        </div>
                        @endif

                        @if($item['special_request'])
                        <p style='font-size: 11pt !important;font-weight:bold;font-style:italic;'>{{ $item['special_request'] }}</p>
                        @endif
                    </td>
                    <td valign=top>{{ $item['battery_type'] }}</td>
                    <td valign=top>@if($item['assembly'] != 2) {{ $item['qty'] }} @endif</td>
                </tr>
                @endforeach
            <!--item end-->
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <p>&nbsp;</p>
            <div style="float: right;border:1px solid black;width:220px;height: 70px;text-align: center;vertical-align: middle;">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($so->so_no, 'C128') }}" alt="barcode" style="padding-top: 10px;" /><br/>
                <span style="font-size: 16px;font-weight: 600;padding-left: 32px;">{{ $so->so_no }}</span></p>
            </div>
        </td>
    </tr>
</table>
</body>
</html>