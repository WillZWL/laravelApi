<div class="form-group form-group-sm" data="category">
    <label for="inputCategoryId" class="col-sm-1 control-label">M.P. Category:</label>
    <div class="col-sm-2">
        <select name="categoryId" id="inputCategoryId" class="form-control">
            <option value="">-- Select --</option>
            @foreach($mpCategories[1] as $category)
                <option value="{{ $category->id }}" {{ ($category->id == $marketplaceSKU->mp_category_id) ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <label for="inputSubCategoryId" class="col-sm-1 control-label">M.P. Sub Category:</label>
    <div class="col-sm-2">
        <select name="subCategoryId" id="inputSubCategoryId" class="form-control">
            <option value="">-- Select --</option>
            @foreach($mpCategories[2] as $category)
                <option value="{{ $category->id }}" {{ ($category->id == $marketplaceSKU->mp_sub_category_id) ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#inputMarketplaceSku').val('{{ $marketplaceSKU->marketplace_sku }}');
        $('#inputEAN').val('{{ $marketplaceSKU->ean }}');
        $('#inputUPC').val('{{ $marketplaceSKU->upc }}');
        $('#inputASIN').val('{{ $marketplaceSKU->asin }}');
    });
</script>
