<div id="sku-listing-info">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        @foreach($data as $platform => $deliveryItems)
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="head_{{ $platform }}">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#{{ $platform }}" aria-expanded="false" aria-controls="collapseTwo">
                            {{ $platform }}
                        </a>
                    </h4>
                </div>
                <div id="{{ $platform }}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="head_{{ $platform }}">
                    <div class="panel-body">
                        @if(count($deliveryItems) > 0)
                        <table class="table table-bordered table-condensed">
                            <tbody>
                            <tr class="info">
                                <th>Type</th>
                                <th>Delivery Type</th>
                                <th>Price</th>
                                <th>Decl.</th>
                                <th>Tax</th>
                                <th>Duty</th>
                                <th>esg COMM.</th>
                                <th>MP. COMM.</th>
                                <th>Listing Fee</th>
                                <th>Fixed Fee</th>
                                <th>PSP Fee</th>
                                <th>PSP Adm. Fee</th>
                                <th>Freight Cost</th>
                                <th>Supp. Cost</th>
                                <th>Acce. Cost</th>
                                <th>Total Cost</th>
                                <th>Delivery Charge</th>
                                <th>Total Charged</th>
                                <th>Proft</th>
                                <th>Margin</th>
                            </tr>
                            @foreach($deliveryItems as $deliveryType => $item)
                            <tr>
                                <td>Cost</td>
                                <td>
                                    <div class="radio">
                                        <label><input type="radio" {{ $item['checked'] }} name="delivery_type_{{ $platform }}" value="{{ $deliveryType }}">{{ $deliveryType }}</label>
                                    </div>
                                </td>
                                <td data-marketplace-sku="{{ $item['marketplaceSku'] }}" data-selling-platform="{{ $platform }}" data-name="price" class="editable">
                                    <input name="price" style="width: 70px" value="{{ $item['price'] }}">
                                </td>
                                <td>{{ $item['declaredValue'] }}</td>
                                <td>{{ $item['tax'] }}</td>
                                <td>{{ $item['duty'] }}</td>
                                <td>{{ $item['esgCommission'] }}</td>
                                <td>{{ $item['marketplaceCommission'] }}</td>
                                <td>{{ $item['marketplaceListingFee'] }}</td>
                                <td>{{ $item['marketplaceFixedFee'] }}</td>
                                <td>{{ $item['paymentGatewayFee'] }}</td>
                                <td>{{ $item['paymentGatewayAdminFee'] }}</td>
                                <td>{{ $item['freightCost'] }}</td>
                                <td>{{ $item['supplierCost'] }}</td>
                                <td>{{ $item['accessoryCost'] }}</td>
                                <td>{{ $item['totalCost'] }}</td>
                                <td>{{ $item['deliveryCharge'] }}</td>
                                <td>{{ $item['totalCharged'] }}</td>
                                <td data-name="profit">{{ $item['profit'] }}</td>
                                <td data-name="margin">{{ $item['margin'] }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="20" align="center">
                                    <button type="button" data-platform="{{ $platform }}" class="btn btn-danger save_price_info"> Save </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        @else
                            Please complete all the data before list this sku.
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(document).on('click', '#sku-listing-info .save_price_info', function (e) {
        e.preventDefault();
        var trElement = $('#'+e.target.data('platform') + ' input[name*="delivery_type"]:checked').closest('tr');
        var price = trElement.find('input[name=price]').text();
        var profit = trElement.find('td[data-name=profit]').text();
        var margin = trElement.find('td[data-name=margin]').text();
        var sellingPlatform = trElement.find('td[data=sellingPlatform]').data('sellingPlatform');
        var marketplaceSku = trElement.find('td[name=marketplaceSku]').data('marketplaceSku');
    })
</script>
