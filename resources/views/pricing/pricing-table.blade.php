<?php
$link = [
        'BCAMAZONGB' => 'https://www.amazon.co.uk/dp/',
        'BCAMAZONUS' => 'https://www.amazon.com/dp/',
        'BCAMAZONFR' => 'https://www.amazon.fr/dp/',
        'BCAMAZONCA' => 'https://www.amazon.ca/dp/',
        'BCAMAZONMX' => 'https://www.amazon.com.mx/dp/',
        'BCAMAZONDE' => 'https://www.amazon.de/dp/',
        'BCAMAZONES' => 'https://www.amazon.es/dp/',
        'BCAMAZONIT' => 'https://www.amazon.it/dp/',
        'BCAMAZONJP' => 'https://www.amazon.jp/dp/',
        'PXAMAZONGB' => 'https://www.amazon.co.uk/dp/',
        'PXAMAZONUS' => 'https://www.amazon.com/dp/',
        'PXAMAZONFR' => 'https://www.amazon.fr/dp/',
        'PXAMAZONCA' => 'https://www.amazon.ca/dp/',
        'PXAMAZONMX' => 'https://www.amazon.com.mx/dp/',
        'PXAMAZONDE' => 'https://www.amazon.de/dp/',
        'PXAMAZONES' => 'https://www.amazon.es/dp/',
        'PXAMAZONIT' => 'https://www.amazon.it/dp/',
        'PXAMAZONJP' => 'https://www.amazon.jp/dp/',
        'CVAMAZONGB' => 'https://www.amazon.co.uk/dp/',
        'CVAMAZONUS' => 'https://www.amazon.com/dp/',
        'CVAMAZONFR' => 'https://www.amazon.fr/dp/',
        'CVAMAZONCA' => 'https://www.amazon.ca/dp/',
        'CVAMAZONMX' => 'https://www.amazon.com.mx/dp/',
        'CVAMAZONDE' => 'https://www.amazon.de/dp/',
        'CVAMAZONES' => 'https://www.amazon.es/dp/',
        'CVAMAZONIT' => 'https://www.amazon.it/dp/',
        'CVAMAZONJP' => 'https://www.amazon.jp/dp/',
        '3DAMAZONGB' => 'https://www.amazon.co.uk/dp/',
        '3DAMAZONUS' => 'https://www.amazon.com/dp/',
        '3DAMAZONFR' => 'https://www.amazon.fr/dp/',
        '3DAMAZONCA' => 'https://www.amazon.ca/dp/',
        '3DAMAZONMX' => 'https://www.amazon.com.mx/dp/',
        '3DAMAZONDE' => 'https://www.amazon.de/dp/',
        '3DAMAZONES' => 'https://www.amazon.es/dp/',
        '3DAMAZONIT' => 'https://www.amazon.it/dp/',
        '3DAMAZONJP' => 'https://www.amazon.jp/dp/',

];
?>
<div id="sku-listing-info">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        @foreach($data as $platform => $platformInfo)
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="head_{{ $platform }}">
                    <h4 class="panel-title">
                        <span>
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#{{ $platform }}" aria-expanded="false" aria-controls="collapseTwo">
                                {{ $platform }}
                            </a>
                        </span>
                        <span>
                            &nbsp;|&nbsp;
                            {{ $platformInfo['currency'] }}
                            &nbsp;|&nbsp;
                        </span>
                        <span>
                            {{ $platformInfo['price'] }}
                            &nbsp;|&nbsp;
                        </span>
                        <span>
                            {{ $platformInfo['delivery_type'] }}
                            &nbsp;|&nbsp;
                        </span>
                        <span>
                            {{ ($platformInfo['listingStatus'] === 'Y') ? 'Listed' : 'Not Listed' }}
                            &nbsp;|&nbsp;
                        </span>
                        <span class="price {{ ($platformInfo['margin'] > 0) ? 'text-success' : 'text-danger' }}">
                            {{ $platformInfo['margin'] }}%
                        </span>
                        @if($platformInfo['link'])
                        <span>
                            &nbsp;|&nbsp;
                            <a href="{{ $platformInfo['link'] }}" target="_blank">{{ $platformInfo['link'] }}</a>
                        </span>
                        @endif
                    </h4>
                </div>
                <div id="{{ $platform }}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="head_{{ $platform }}">
                    <div class="panel-body">
                        @if(count($platformInfo['deliveryOptions']) > 0)
                        <table class="table table-bordered table-condensed" style="table-layout: fixed">
                            <colgroup>
                                <col width="7%">
                                <col width="8%">
                                <col width="4%">
                                <col width="4%">
                                <col width="4%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="4%">
                                <col width="3%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="5%">
                                <col width="8%">
                                <col width="5%">
                            </colgroup>

                            <tbody>
                            <tr class="info">
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
                                <th>Profit</th>
                                <th>Margin</th>
                            </tr>
                            @foreach($platformInfo['deliveryOptions'] as $deliveryType => $item)
                            <tr>
                                <td>
                                    <div class="radio">
                                        <label><input type="radio" {{ $item['checked'] }} name="delivery_type_{{ $platform }}" value="{{ $deliveryType }}">{{ $deliveryType }}</label>
                                    </div>
                                </td>
                                <td>
                                    <input  name="price" style="width: 100%" value="{{ $item['price'] }}" data-marketplace-sku="{{ $item['marketplaceSku'] }}" data-selling-platform="{{ $platform }}">
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
                                <td data-name="margin" class="{{ ($item['margin'] < 0) ? 'text-danger' : '' }}">{{ $item['margin'] }}%</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="2">Listing Status:</td>
                                <td colspan="7">
                                    <select name="listingStatus" required="required">
                                        <option value="">-- Select --</option>
                                        <option value="Y" {{ ($platformInfo['listingStatus'] == 'Y') ? 'selected' : '' }}>Listed</option>
                                        <option value="N" {{ ($platformInfo['listingStatus'] == 'N') ? 'selected' : '' }}>Not listed</option>
                                    </select>
                                </td>
                                <td colspan="3">Inventory</td>
                                <td colspan="7">
                                    <input type="text" name="inventory" id="inputInventory" value="{{ $platformInfo['inventory'] }}" required="required">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="/images/amazon_latency.jpg" target="_blank">
                                        Latency
                                    </a>
                                </td>
                                <td colspan="7">
                                    <input type="text" name="latency" id="inputLatency" value="{{ $platformInfo['fulfillmentLatency'] }}" required="required">
                                </td>
                                <td colspan="3">
                                    Amazon Brand
                                </td>
                                <td colspan="7">
                                    <input type="text" name="platformBrand" id="inputPlatformBrand" value="{{ $platformInfo['platformBrand'] }}">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    Condition
                                </td>
                                <td colspan="7">
                                    <select name="condition" id="condition">
                                        <option value="New" {{ ($platformInfo['condition'] == 'New') ? 'selected' : '' }}>New</option>
                                        <option value="UsedLikeNew" {{ ($platformInfo['condition'] == 'UsedLikeNew') ? 'selected' : '' }}>UsedLikeNew</option>
                                        <option value="UsedVeryGood" {{ ($platformInfo['condition'] == 'UsedVeryGood') ? 'selected' : '' }}>UsedVeryGood</option>
                                        <option value="UsedGood" {{ ($platformInfo['condition'] == 'UsedGood') ? 'selected' : '' }}>UsedGood</option>
                                        <option value="UsedAcceptable" {{ ($platformInfo['condition'] == 'UsedAcceptable') ? 'selected' : '' }}>UsedAcceptable</option>
                                        <option value="CollectibleLikeNew" {{ ($platformInfo['condition'] == 'CollectibleLikeNew') ? 'selected' : '' }}>CollectibleLikeNew</option>
                                        <option value="CollectibleVeryGood" {{ ($platformInfo['condition'] == 'CollectibleVeryGood') ? 'selected' : '' }}>CollectibleVeryGood</option>
                                        <option value="CollectibleGood" {{ ($platformInfo['condition'] == 'CollectibleGood') ? 'selected' : '' }}>CollectibleGood</option>
                                        <option value="CollectibleAcceptable" {{ ($platformInfo['condition'] == 'CollectibleAcceptable') ? 'selected' : '' }}>CollectibleAcceptable</option>
                                        <option value="Refurbished" {{ ($platformInfo['condition'] == 'Refurbished') ? 'selected' : '' }}>Refurbished</option>
                                        <option value="Club" {{ ($platformInfo['condition'] == 'Club') ? 'selected' : '' }}>Club</option>
                                    </select>
                                </td>
                                <td colspan="3">
                                    Condition Note
                                </td>
                                <td colspan="7">
                                    <input type="text" name="conditionNote" id="conditionNote" value="{{ $platformInfo['conditionNote'] }}">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="19" align="center">
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
        var platformBrand = $('#' + $(this).data('platform') + ' input[name=platformBrand]').val();
        var condition = $('#' + $(this).data('platform') + ' select[name=condition]').val();
        var latency = $('#' + $(this).data('platform') + ' input[name=latency]').val();
        var conditionNote = $('#' + $(this).data('platform') + ' input[name=conditionNote]').val();
        var inventory = $('#' + $(this).data('platform') + ' input[name=inventory]').val();
        var price = trElement.find('input[name=price]').val();
        var sellingPlatform = trElement.find('input[name=price]').data('sellingPlatform');
        var marketplaceSku = trElement.find('input[name=price]').data('marketplaceSku');
        var profit = trElement.find('td[data-name=profit]').text();
        var margin = trElement.find('td[data-name=margin]').text();

        console.log(inventory);

        if (inventory == 0 || inventory === undefined) {
            alert('Please mind the inventory is no greater than 0');
        }

        $.ajax({
            method: "POST",
            url: "{{ url('/listingSku/save') }}",
            dataType: 'json',
            data: {
                delivery_type: deliveryType,
                price: price,
                inventory: inventory,
                profit: profit,
                margin: margin,
                sellingPlatform: sellingPlatform,
                marketplace_sku: marketplaceSku,
                listingStatus: listingStatus,
                platformBrand: platformBrand,
                condition: condition,
                conditionNote: conditionNote,
                fulfillmentLatency: latency
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
