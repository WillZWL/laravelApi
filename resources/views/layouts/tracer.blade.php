<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing Tool</title>
    <link rel="stylesheet" href="{{ asset('/bootstrap/css/pricing_tool.css') }}" type="text/css" media="all"/>
    <link rel="stylesheet" href="{{ asset('/bootstrap/css/bootstrap.css') }}" type="text/css" media="all"/>
</head>
<body>
<div id="container">
    <section id="header">
        <p class="title">
            Tracer SKU setting
        </p>
    </section>

    @yield('setting-form')
</div>

<script type="text/javascript" src="{{ asset('/bootstrap/js/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('/bootstrap/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('/bootstrap/js/jquery.pjax.js') }}"></script>
<script type="text/javascript">
    $(function(){
        $('#inputMarketplace').on('change', function () {
            $.ajax({
                method: 'GET',
                url: "{{ url('api/v1/marketplaceCategory/marketplace') }}/"+$(this).val(),
                dataType: 'json'
            }).done(function (responseJson) {
                var categoryOptions = '';
                $.each(responseJson, function (key, category) {
                    categoryOptions += '<option value="'+category.id+'">'+category.name+'</option>'
                });
                $('#inputCategory').html(categoryOptions);
                $('#inputCategory').selectedIndex = 0;
                $('#inputCategory').change();
            })
        });

        $('#inputCategory').on('change', function () {
            $.ajax({
                method: 'GET',
                url: "{{ url('api/v1/marketplaceCategory') }}/"+$(this).val(),
                dataType: 'json'
            }).done(function (responseJson) {
                var subCategoryOptions = '';

                $.each(responseJson, function (mpControlId, subCategoryCollection) {
                    $.each(subCategoryCollection, function (key, subCategory) {
                        subCategoryOptions += '<option value="'+subCategory.id+'">'+subCategory.name+'</option>';
                    });
                });

                $('#inputSubCategory').html(subCategoryOptions);
            })
        })
    })

</script>
</body>
</html>