<div id="sku-listing-info">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        @foreach($data as $platform => $platformInfo)
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
                        @if(count($platformInfo['deliveryOptions']) > 0)
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
                            @foreach($platformInfo['deliveryOptions'] as $deliveryType => $item)
                            <tr>
                                <td>Cost</td>
                                <td>
                                    <div class="radio">
                                        <label><input type="radio" {{ $item['checked'] }} name="delivery_type_{{ $platform }}" value="{{ $deliveryType }}">{{ $deliveryType }}</label>
                                    </div>
                                </td>
                                <td>
                                    <input  name="price" style="width: 70px" value="{{ $item['price'] }}" data-marketplace-sku="{{ $item['marketplaceSku'] }}" data-selling-platform="{{ $platform }}">
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
                                <td>Listing Status:</td>
                                <td>
                                    <select name="listingStatus" required="required">
                                        <option value="">-- Select --</option>
                                        <option value="Y" {{ ($platformInfo['listingStatus'] == 'Y') ? 'selected' : '' }}>Listed</option>
                                        <option value="N" {{ ($platformInfo['listingStatus'] == 'N') ? 'selected' : '' }}>Not listed</option>
                                    </select>
                                </td>
                            </tr>
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
<script type="text/javascript">
    $('.collapse:first').collapse('show');

    $(document).off('click', '.save_price_info').on('click', '.save_price_info', function (e) {
        e.preventDefault();
        var trElement = $('#' + $(this).data('platform') + ' input[name*="delivery_type"]:checked').closest('tr');
        var deliveryType = $('#' + $(this).data('platform') + ' input[name*="delivery_type"]:checked').val();
        var listingStatus = $('#' + $(this).data('platform') + ' select[name=listingStatus]').val();
        var price = trElement.find('input[name=price]').val();
        var sellingPlatform = trElement.find('input[name=price]').data('sellingPlatform');
        var marketplaceSku = trElement.find('input[name=price]').data('marketplaceSku');
        var profit = trElement.find('td[data-name=profit]').text();
        var margin = trElement.find('td[data-name=margin]').text();

        $.ajax({
            method: "POST",
            url: "{{ url('/listingSku/save') }}",
            dataType: 'json',
            data: {
                delivery_type: deliveryType,
                price: price,
                profit: profit,
                margin: margin,
                sellingPlatform: sellingPlatform,
                marketplace_sku: marketplaceSku,
                listingStatus: listingStatus
            }
        }).done(function (msg) {
            console.log(msg);
            if (msg === 'success') {
                alert('Saved success');
            } else {
                alert('Save failed');
            }
        }).fail(function (jqXHR, textStatus) {
            alert('Save failed');
        })
    });

    $(document).off('blur', 'input[name=price]').on('blur', 'input[name=price]', function () {
        var marketplaceSku = $(this).data('marketplaceSku');
        var sellingPlatform = $(this).data('sellingPlatform');
        var price = $(this).val();
        var $self = $(this);
        $.ajax({
            method: 'GET',
            url: "{{ url('/pricing/simulate') }}",
            data: {marketplaceSku: marketplaceSku, sellingPlatform: sellingPlatform, price: price},
            dataType: 'html'
        }).done(function (responseText) {
            console.log('debug');
            $self.closest('.panel').html(responseText);
            $self.closest('.panel').find('.collapse').collapse('show');
        }).fail(function () {
            alert("Can't get the new profit");
        })
    });
</script>
