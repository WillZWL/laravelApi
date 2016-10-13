<div class="print-pick-list">
<div class="logo"><img alt="image" class="logo-bg" src="/assets/img/print_logo_bg.png" />
<div class="logo-label"><img alt="image" src="/assets/img/print_logo_label.png" /></div>

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