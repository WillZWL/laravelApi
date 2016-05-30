<section class="top">
    <p>
        Pricing Tool
    </p>
</section>

<section class="sub_top">
    <div class="row">
        <form action="{{ url('pricing/skuList') }}" class="form-horizontal" id="search" method="POST" role="form">
            <div class="col-md-9 col-md-offset-1">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="master_sku">Master SKU:</label>
                            <input type="text" class="input-sm" id="master_sku" name="master_sku" value="">
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="esg_sku">ESG SKU:</label>
                            <input type="text" class="input-sm" id="esg_sku" name="esg_sku" value="">
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="product_name">Product Name:</label>
                            <input type="text" class="input-sm" id="product_name" name="product_name" value="">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="brand_id" class="col-sm-2 control-label">Brand:</label>
                            <div class="col-sm-6">
                                <select name="brand_id" id="brand_id" class="form-control input-sm">
                                    <option value=""></option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="marketplace" class="col-sm-3 control-label">Marketplace:</label>
                            <div class="col-sm-6">
                                <select name="marketplace" id="marketplace" class="form-control input-sm" required="required">
                                    <option value="">--Select--</option>
                                    @foreach($marketplaces as $marketplace)
                                        <option value="{{ $marketplace->id }}">{{ $marketplace->id }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</section>

<section>
    <div class="row">
        <div class="col-md-3" id="sku-list-warp">
        </div>
        <div class="col-md-9" id="sku-listing-info-wrap">
        </div>
    </div>
</section>
<script type="text/javascript">
    $("#search").submit(function (event) {
        event.preventDefault();
        $.ajax({
            method: "GET",
            url: $(this).attr('action'),
            dataType: 'html',
            data: $(this).serialize(),
            success: function (responseText) {
                $("#sku-list-warp").html(responseText);
            },
            error: function (xhr, status, text) {
                alert('error occurred: ' + status);
            }
        });
    });
</script>
