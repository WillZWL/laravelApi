<form class="form-horizontal" action="{{ url('listing/create') }}" method="POST" role="form">
    <div class="form-group">
        <label for="esgSku" class="col-sm-1 col-sm-offset-2 control-label">ESG SKU</label>
        <div class="col-sm-2">
            <input type="text" class="form-control input-sm" id="esgSku" placeholder="">
        </div>
    </div>

    <div class="form-group">
        <label for="inputMarketplace" class="col-sm-1 col-sm-offset-2 control-label">Marketplace:</label>
        <div class="col-sm-2">
            <select name="marketplace" id="inputMarketplace" class="form-control">
                <option value="">-- Select One --</option>
                @foreach($marketplaces as $marketplace)
                    <option value="{{ $marketplace->id }}">{{ $marketplace->id }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="marketplaceSku" class="col-sm-1 col-sm-offset-2 control-label">Marketplace SKU</label>
        @if($marketplaceSkus)
            <div class="col-sm-2">
                <select name="marketplaceSku" id="marketplaceSku" class="form-control">
                    @foreach($marketplaceSkus as $marketplaceSku)
                        <option value="{{ $marketplaceSku->marketplace_sku }}">{{ $marketplaceSku->marketplace_sku }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-info">Add New Listing</button>
            </div>
        @else
            <div class="col-sm-2">
                <input type="text" name="marketplaceSku" class="form-control input-sm" id="marketplaceSku" placeholder="please input marketplace sku">
            </div>
        @endif
    </div>

    @foreach($listingCountries as $listingCountry)
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-1">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name='country' value="{{ $listingCountry->country_id }}"> {{ $listingCountry->country_id }}
                </label>
            </div>
        </div>
        <div class="col-sm-6">
            <label for="inputCategoryId_{{ $listingCountry->country_id  }}" class="col-sm-2 control-label">Category:</label>
            <div class="col-sm-4">
                <select name="categoryId" id="inputCategoryId_{{ $listingCountry->country_id  }}" class="form-control" required="required">
                    <option value=""></option>
                    @foreach($category[$listingCountry->control_id][1] as $topCategory)
                        <option value="{{ $topCategory->id }}" {{ ($topCategory->id == $listingCountry->parent_cat_id) ? 'selected' : '' }}>{{ $topCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <select name="subCategoryId" id="inputSubCategoryId_{{ $listingCountry->country_id  }}" class="form-control" required="required">
                    <option value=""></option>
                    @foreach($category[$listingCountry->control_id][2] as $topCategory)
                        <option value="{{ $topCategory->id }}" {{ ($topCategory->id == $listingCountry->id) ? 'selected' : '' }}>{{ $topCategory->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    @endforeach

    <div class="form-group">
        <div class="col-sm-1 col-sm-offset-5">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
<script type="text/javascript">
    $('#inputMarketplace').change(function() {
        $.ajax({
            method: "GET",
            url: 'http://admincentre.eservicesgroup.com:7890/listingSku/getListing',
            data: {esgSku: $('#esgSku').val(), marketplace: $('#inputMarketplace').val()},
            dataType: 'html'
        }).done(function () {

        })
    })
</script>
