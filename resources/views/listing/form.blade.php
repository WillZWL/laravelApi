<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <form name="create-listing-form" action="{{ url('listingSku/add') }}" method="POST" class="form-horizontal" role="form">
            <div class="form-group form-group-sm">
                <label for="inputEsgSku" class="col-sm-1 control-label">ESG SKU:</label>
                <div class="col-sm-2">
                    <input type="text" name="esgSku" id="inputEsgSku" class="form-control" value="{{ $esgSku or '' }}" required="required" title="">
                </div>

                <label for="inputMarketplace" class="col-sm-1 control-label">Marketplace:</label>
                <div class="col-sm-2">
                    <select name="marketplace" id="inputMarketplace" class="form-control" required="required">
                    </select>
                </div>
                <label for="inputCountry" class="col-sm-1 control-label">Country:</label>
                <div class="col-sm-2">
                    <select name="country" id="inputCountry" class="form-control" required="required">
                        <option value="">-- Country --</option>
                    </select>
                </div>
            </div>

            <div class="form-group form-group-sm" data-group="marketplace">
                <label for="inputMarketplaceSku" class="col-sm-1 control-label">M.P. SKU:</label>
                <div class="col-sm-2">
                    <input type="text" name="marketplaceSku" id="inputMarketplaceSku" class="form-control" value="" required="required" title="">
                </div>
            </div>

            <div class="form-group form-group-sm">
                <label for="inputEAN" class="col-sm-1 control-label">EAN:</label>
                <div class="col-sm-2">
                    <input type="text" name="EAN" id="inputEAN" class="form-control" value="" title="">
                </div>

                <label for="inputUPC" class="col-sm-1 control-label">UPC:</label>
                <div class="col-sm-2">
                    <input type="text" name="UPC" id="inputUPC" class="form-control" value="" title="">
                </div>

                <label for="inputASIN" class="col-sm-1 control-label">ASIN:</label>
                <div class="col-sm-2">
                    <input type="text" name="ASIN" id="inputASIN" class="form-control" value="" required="required" title="">
                </div>
                <div class="col-sm-3">
                    <button id="getASIN" type="button" class="btn btn-info btn-sm">Get ASIN via EAN/UPC</button>
                </div>
            </div>
            <div class="form-group form-group-sm" data="category">
                <label for="inputCategoryId" class="col-sm-1 control-label">M.P. Category:</label>
                <div class="col-sm-2">
                    <select name="categoryId" id="inputCategoryId" class="form-control" required="required">
                        <option value="">-- Select --</option>
                    </select>
                </div>

                <label for="inputSubCategoryId" class="col-sm-1 control-label">M.P. Sub Category:</label>
                <div class="col-sm-2">
                    <select name="subCategoryId" id="inputSubCategoryId" class="form-control" required="required">
                        <option value="">-- Select --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-2 col-sm-offset-5">
                    <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                </div>
            </div>

        </form>
    </div>
</div>

<script type="text/javascript">
    data = JSON.parse('{!! $marketplaceAndCountry !!}');

    $(document).ready(function () {
        var marketplaceOptions = '<option value="">-- Marketplace --</option>';
        $.each(data, function (key, value) {
            marketplaceOptions += '<option value="'+key+'">'+key+'</option>';
        });

        $('select[name=marketplace]').html(marketplaceOptions);
    });

    $('select[name=marketplace]').change(function () {
        var countryOptions = '<option value="">-- Country --</option>';
        $.each(data[$(this).val()], function (i, value) {
            countryOptions += '<option value="'+value['country_id']+'">'+value['country_id']+'</option>';
        });
        $('select[name=country]').html(countryOptions);
    });

    $('body').on('click', '#addNewListing', function (e) {
        marketplaceInput = '<label for="inputMarketplaceSku" class="col-sm-1 control-label">M.P. SKU:</label><div class="col-sm-2"><input type="text" name="marketplaceSku" id="inputMarketplaceSku" class="form-control" value="" required="required" title=""></div>';
        $('div[data-group="marketplace"]').html(marketplaceInput);
        $('#inputEAN').val('');
        $('#inputUPC').val('');
        $('#inputASIN').val('');
    });

    $('body').on('change', '#inputMarketplaceSku', function (e) {
        inventory = $(this).find(':selected').data('inventory');

        uuid = $(this).find(':selected').data('uuid').split('-');
        $('#inputEAN').val(uuid[0]);
        $('#inputUPC').val(uuid[1]);
        $('#inputASIN').val(uuid[2]);

        category = $(this).find(':selected').data('category').split('-');
        $('#inputCategoryId').val(category[0]);

        $.ajax({
            method: "GET",
            url: "{{ url('listingSku/getCategory') }}",
            data: {
                categoryId: category[0],
                marketplace: $('#inputMarketplace').val(),
                country: $('#inputCountry').val(),
            },
            dataType: "json"
        }).done(function(responseJson) {
            subCategoryOptions = '<option value="">-- Select --</option>';
            if (responseJson.mpCategories) {
                $.each(responseJson.mpCategories, function (key, categoryItems) {
                    subCategoryOptions += '<option value="'+categoryItems.id+'">'+categoryItems.name+'</option>';
                });
            }
            $('#inputSubCategoryId').html(subCategoryOptions);
            $('#inputSubCategoryId').val(category[1]);
        })
    })

    $('body').on('change', '#inputCategoryId', function () {
        categoryId = $(this).find(':selected').val();
        $.ajax({
            method: "GET",
            url: "{{ url('listingSku/getCategory') }}",
            data: {
                categoryId: categoryId,
                marketplace: $('#inputMarketplace').val(),
                country: $('#inputCountry').val(),
            },
            dataType: "json"
        }).done(function(responseJson) {
            subCategoryOptions = '<option value="">-- Select --</option>';
            if (responseJson.mpCategories) {
                $.each(responseJson.mpCategories, function (key, categoryItems) {
                    subCategoryOptions += '<option value="'+categoryItems.id+'">'+categoryItems.name+'</option>';
                });
            }
            $('#inputSubCategoryId').html(subCategoryOptions);
        })

    })

    $('#getASIN').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            method: "GET",
            url: "{{ url('amazon/getASIN') }}",
            data: {
                EAN: $('input[name=EAN]').val(),
                UPC: $('input[name=UPC]').val(),
                marketplace: $('select[name=marketplace]').val(),
                country: $('select[name=country]').val()
            },
            dataType: 'json'
        }).done(function (responseJson) {
            if (responseJson.ASIN != undefined) {
                $('input[name=ASIN]').val(responseJson.ASIN);
            } else {
                alert(responseJson.Error);
            }
        })
    });

    $('#inputCountry').change(function () {
        getData();
    });

    $('form button[type=submit]').on('submit', function (e) {
        e.preventDefault();
    });

    function getData() {
        $.ajax({
            method: "GET",
            url: "{{ url('listingSku/getData') }}",
            data: {
                esgSku: $('#inputEsgSku').val(),
                marketplace: $('#inputMarketplace').val(),
                country: $('#inputCountry').val(),
                marketplaceSku: $('#inputMarketplaceSku').val()
            },
            dataType: 'json'
        }).done(function (responseJson) {
            // marketplace sku input
            if (responseJson.marketplaceSkus.length > 0) {
                addMarketplace = $('div[data-group="marketplace"]').html();
                marketplaceSelection = '<label for="inputMarketplaceSku" class="col-sm-1 control-label">M.P. SKU:</label><div class="col-sm-2"><select name="marketplaceSku" id="inputMarketplaceSku" class="form-control" required="required">';
                marketplaceSelection += '<option value="">-- Select --</option>';
                $.each(responseJson.marketplaceSkus, function (key, marketplaceSkuObj) {
                    marketplaceSelection += '<option value="'+marketplaceSkuObj.marketplace_sku+'"';
                    marketplaceSelection += 'data-category="'+marketplaceSkuObj.mp_category_id+'-'+marketplaceSkuObj.mp_sub_category_id+'"';
                    marketplaceSelection += 'data-inventory="'+marketplaceSkuObj.inventory+'"';
                    marketplaceSelection += 'data-uuid="'+marketplaceSkuObj.ean+'-'+marketplaceSkuObj.upc+'-'+marketplaceSkuObj.asin+'"';
                    marketplaceSelection += '>'+marketplaceSkuObj.marketplace_sku+'</option>';
                })
                marketplaceSelection += '</select></div><div class="col-sm-1"><button type="button" id="addNewListing" class="btn btn-info btn-sm">Add New Listing</button></div>';
                $('div[data-group="marketplace"]').html(marketplaceSelection);
            }

            // marketplace category select
            categoryOptions = '<option value="">-- Select --</option>';
            if (responseJson.mpCategories) {
                $.each(responseJson.mpCategories, function (key, categoryItems) {
                  categoryOptions += '<option value="'+categoryItems.id+'">'+categoryItems.name+'</option>';
                });
            }
            $('#inputCategoryId').html(categoryOptions);
        })
    }
</script>
