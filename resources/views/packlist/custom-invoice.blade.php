<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<title>Custom Invoice</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
   body {margin:0; text-align:center;}
   * {font-family:Helvetica,rial,verdana,sans-serif;font-size:11px;}
  .field{background-color:#e1e1e1; padding-left:5px; text-align:left; height:25px; font-size:12px;}
  .value{background-color:#ffffff;  padding-left:5px; text-align:left; height:25px; font-size:12px;}
  .tborder{background-color:#000000; width:1px; }
</style>
</head>
<body marginwidth='0' marginheight='0'>
<div style='width:100%;'>
    <table width='100%' cellpadding='0' cellspacing='0' border='0'>
        <tr><td colspan='3' align='center'><b style='font-size:16px;'>Custom Invoice</b><br/><br/></td></tr>
        <tr>
            <td width='10%'>&nbsp;</td>
            <td width='80%'>
            <table width='100%' cellpadding='0' cellspacing='1' border='0' bgcolor='#000000'>
                <tr>
                    <td class='field' width='20%'>Date of Invoice</td>
                    <td class='value' width='30%'>{{ date("d/m/Y") }}</td>
                    <td class='field' width='20%'>Order No.</td>
                    <td class='value' width='30%'>{{ $so->client_id }} - {{ $so->so_no }}</td>
                </tr>
                <tr>
                    <td class='field'>Shipper Name</td>
                    <td class='value'>{{ $shipper['shipper_name'] }}</td>
                    <td class='field'>Ship to</td>
                    <td class='value'>{{ $so->delivery_name }}</td>
                </tr>
                <tr>
                    <td class='field' rowspan='6' valign='top' style='padding-top:4px;'>Shipper Address</td>
                    <td class='value'>{{ $shipper['saddr_1'] }}</td>
                    <td class='field' rowspan='6' valign='top' style='padding-top:4px;'>Ship to Address</td>
                    <td class='value'>{{ $shipp_to['daddr_1'] }}</td>
                </tr>
                <tr>
                    <td class='value'>{{ $shipper['saddr_2'] }}</td>
                    <td class='value'>{{ $shipp_to['daddr_2'] }}</td>
                </tr>
                <tr>
                    <td class='value'>{{ $shipper['saddr_3'] }}</td>
                    <td class='value'>{{ $shipp_to['daddr_3'] }}</td>
                </tr>
                <tr>
                    <td class='value'>{{ $shipper['saddr_4'] }}</td>
                    <td class='value'>{{ $shipp_to['daddr_4'] }}</td>
                </tr>
                <tr>
                    <td class='value'>{{ $shipper['saddr_5'] }}</td>
                    <td class='value'>{{ $shipp_to['daddr_5'] }}</td>
                </tr>
                <tr>
                    <td class='value'>{{ $shipper['saddr_6'] }}</td>
                    <td class='value'>{{ $shipp_to['daddr_6'] }}</td>
                </tr>
                <tr>
                    <td class='field'>Contact person</td>
                    <td class='value'>{{ $shipper['shipper_contact'] }}</td>
                    <td class='field'>Contact person</td>
                    <td class='value'>{{ $so->delivery_name }}</td>
                </tr>
                <tr>
                    <td class='field'>Contact no.</td>
                    <td class='value'>{{ $shipper['shipper_phone'] }}</td>
                    <td class='field'>Contact no.</td>
                    <td class='value'>{{ $so->del_tel_1 }}-{{ $so->del_tel_2 }}-{{ $so->del_tel_3 }}</td>
                </tr>
            </table>
            <br/>
            <div style='float:left;'>
                <b style='font-size:16px;'>Incoterm:
                @if($so->incoterm == "DDP" && in_array($courier_id,["45","51"]))
                DDP for all VAT duties, taxes and handling fees
                @else
                {{ $so->incoterm }}
                @endif
                </b>
            </div>
            <br/>
            <br/>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr bgcolor='#000000'>
            <td colspan='13' height='1'></td>
        </tr>
        <tr>
            <td width='1' class='tborder'></td>
            <td class='field'>Product descriptions</td>
            <td width='1' class='tborder'></td>
            <td class='field'>Country Of Origin</td>
            <td width='1' class='tborder'></td>
            <td class='field'>HS Code</td>
            <td width='1' class='tborder'></td>
            <td class='field'>Qty</td>
            <td width='1' class='tborder'></td>
            <td width='15%' class='field'>Unit Value({{ $currency_courier_id }})</td>
            <td width='1' class='tborder'></td>
            <td width='15%' class='field'>Total Value({{ $currency_courier_id }})</td>
            <td width='1' class='tborder'></td>
        </tr>
        <tr bgcolor='#000000'>
            <td colspan='13' height='1'></td>
        </tr>
        @foreach($soItem as $item)
            @if($item["is_show"])
            <tr bgcolor='#000000'>
                <td width='1' class='tborder'></td>
                <td class='value'>{{ $item['prod_desc'] }}</td>
                <td width='1' class='tborder'></td>
                <td class='value'>China</td>
                <td width='1' class='tborder'></td>
                <td class='value'>{{ $item['code'] }}</td>
                <td width='1' class='tborder'></td>
                <td class='value'>{{ $item['qty'] }}</td>
                <td width='1' class='tborder'></td>
                <td class='value'>{{ $item['unit_declared_value'] }}</td>
                <td width='1' class='tborder'></td>
                <td class='value'>{{ $item['item_declared_value'] }}</td>
                <td width='1' class='tborder'></td>
            </tr>
            <tr bgcolor='#000000'><td colspan='13' height='1'></td></tr>
            @endif
        @endforeach
        <tr>
            <td colspan='8' rowspan='10' class='value'></td>
            <td width='1' class='tborder'></td>
            <td class='field'>Original Cost of Items</td>
            <td width='1' class='tborder'></td>
            <td class='value'>{{ $total_cost }}</td>
            <td width='1' class='tborder'></td>
        </tr>
        <tr bgcolor='#000000'>
            <td colspan='10' height='1'></td>
        </tr>
        <tr>
            <td width='1' class='tborder'></td>
            <td class='field'>Total Discount Applied</td>
            <td width='1' class='tborder'></td>
            <td class='value'>{{ $total_discount }}</td>
            <td width='1' class='tborder'></td>
        </tr>
        <tr bgcolor='#000000'>
            <td colspan='10' height='1'></td>
        </tr>
        <tr>
            <td width='1' class='tborder'></td>
            <td class='field'>Total Amount Charged</td>
            <td width='1' class='tborder'></td>
            <td class='value'>{{ $total_amount }}</td>
            <td width='1' class='tborder'></td>
        </tr>
        <tr bgcolor='#000000'>
            <td colspan='5' height='1'></td>
        </tr>

        </table>
        <br/><br/>
        <div style='width:100%; text-align:left; font-size:12px; position:relative'>
            @if($merchant_id == "LUMOS" && $so->delivery_country_id == "GB")
            Package is a bicycle helmet. As a piece of personal protective equipment, this product is zero-rated under UK law and therefore is not subject to VAT<br/><br/>
            @endif
            I declare that the above information is true and correct and to the best of our knowledge. The product(s) covered by this document are not subject to any export or import prohibitions & restrictions.<br/><br/>
            <div style="float: right;bottom:5px;right:0; border:1px solid black;width:220px;height: 70px;text-align: center;vertical-align: middle;">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($so->so_no, 'C128') }}" alt="barcode" style="padding-top: 10px;" /><br/>
                <span style="font-size: 16px;font-weight: 600;padding-left: 32px;">{{ $so->so_no }}</span></p>
            </div>
        </div>
        @if($fedex_custom_invoice)
        <div style='width:100%;text-align:left;clear:both;font-size:12px;'>
                         <b>Package contains lithium ion batteries or cells (PI966) </b><br><br>
                        Handle with care, flammability hazard if damage <br>
                        Special procedures must be followed in the event the package is damaged, <br>
                        to include inspection and repacking if necessary <br>
                        Emergency contact no. +852 3153 2766<br></div>
        @endif
        </td>
        <td width='10%'>&nbsp;</td>
    </tr>
    </table>
</div>
</body>
</html>