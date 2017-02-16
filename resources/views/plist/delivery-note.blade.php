<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dispatch Note</title>
<style type="text/css">
.pb
{
page-break-after : always  ;
}


body {
margin:10;
-webkit-print-color-adjust: exact;
}
* {
font-family:Helvetica,verdana,arial,sans-serif;
font-size:8pt;
}
</style>
</head>
<body topmargin="5" leftmargin="5" rightmargin="5" bgcolor="#FFFFFF" style="overflow:none;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF">
    <tr>
        <td align="left" colspan="2">
            <p><img src="{{ $website }}/images/valuebasket_logo.png"></p>
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
            <p>{{ $delivery_address }}</p>
            <p>&nbsp;</p>
        </td>

        <td width="50%" valign=top>
            <b>Bill To:</b>
            <p>{{ $so->bill_name }}</p>
            <p>&nbsp;</p>
            <p>{{ $billing_address }}</p>
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
                <tr>
                    <td align='center'>
                        <img src="{{ $item->imagePath }}"><br>
                        {{ $item->merchant_sku }}
                    </td>
                    <td valign=top>
                        {{ $item->item_sku }} - {{ $item->product->name }}

                        @if(in_array($item->item_sku,['15768-AA-NA', '15767-AA-NA', '15766-AA-NA', '15765-AA-NA']))
                        <br><img src='{{ $website }}/order/integrated_order_fulfillment/get_barcode2/{{ $item->item_sku }}' style='float:right'>
                        @endif

                        @if($item->product->special_request)
                        <p style='font-size: 11pt !important;font-weight:bold;font-style:italic;'>{{ $item->product->special_request }}</p>
                        @endif
                    </td>
                    <td valign=top>{{ $item->battery_type }}</td>
                    <td valign=top>{{ $item->qty }}</td>
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
<p class="pb"></p>
</body>
</html>