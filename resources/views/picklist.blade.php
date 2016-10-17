<style type="text/css">
img{max-width:960px;margin-bottom: 15px;}
table {width: 100%;border-collapse: collapse;margin: 15px 0;}
table th, table td {padding: 5px 10px;text-align: left;vertical-align: top;font-size: 16px;font-family: Arial, Helvetica, sans-serif;line-height: 30px;}
table th {font-weight: bold;border-top: 3px solid #CCCCCC;border-bottom: 3px solid #CCCCCC;}
table td {border-bottom: 2px solid #CCCCCC;}
</style>
<div class="print-pick-list">
<div class="logo"><img alt="image" class="logo-bg" src="{{ asset('/') }}assets/img/print_logo_bg.png" />
<div class="logo-label"><img alt="image" src="{{ asset('/') }}assets/img/print_logo_label.png" /></div>

<div class="logo_content">
<div class="headline">Picklist printed on: <?php echo date("Y-m-d H:i:s")?></div>
</div>
</div>

<table>
    <thead>
        <tr>
            <th>SKU</th>
            <th>DC SKU</th>
            <th>Image</th>
            <th>Product</th>
            <th>Order Number</th>
            <th class="quantity">Quantity</th>
        </tr>
    </thead>
    <tbody>
    @foreach($orderList as $orderNo => $order)
        @foreach($order as $sku => $orderItem)
            <tr>
                <td>{{ $sku }}</td>
                <td>{{ $orderItem["dc_sku"] }}</td>
                <td class="image-cell"><img src='{{ $orderItem['image'] }}'/></td>
                <td>{{ $orderItem['product_name'] }}</td>
                <td>{{ $orderNo }}</td>
                <td>{{ $orderItem["qty"] }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
</div>