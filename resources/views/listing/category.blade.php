<div class="form-group form-group-sm" data="category">
    <label for="inputCategoryId" class="col-sm-1 control-label">M.P. Category:</label>
    <div class="col-sm-2">
        <select name="categoryId" id="inputCategoryId" class="form-control">
            <option value="">-- Select --</option>
            @foreach($mpCategories[1] as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <label for="inputSubCategoryId" class="col-sm-1 control-label">M.P. Sub Category:</label>
    <div class="col-sm-2">
        <select name="subCategoryId" id="inputSubCategoryId" class="form-control">
            <option value="">-- Select --</option>
            @foreach($mpCategories[2] as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
</div>
