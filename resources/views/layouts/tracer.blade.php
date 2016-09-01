<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accelerator Pricing Strategy Application</title>
    <link rel="stylesheet" href="{{ asset('/bootstrap/css/bootstrap.css') }}" type="text/css" media="all"/>
    <style type="text/css">
        #header {
            background: #00DC45;
        }

        .title {
            font-size: 16px;
            height: 30px;
            line-height: 30px;
            padding-left: 30px;
        }
    </style>
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
                url: "{{ url('/marketplaceCategory/marketplace') }}/"+$(this).val(),
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
                url: "{{ url('/marketplaceCategory') }}/"+$(this).val(),
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
        });

        $('#inputLength, #inputHeight, #inputWidth, #inputFactor').on('change', function () {
            var length = $('#inputLength').val();
            var height = $('#inputHeight').val();
            var width = $('#inputWidth').val();
            var factor = $('#inputFactor').val();

            if ($.isNumeric(length) && $.isNumeric(height) && $.isNumeric(width) && $.isNumeric(factor)) {
                volWeight = length * width * height / factor;
                $('#inputVolumetricWeight').val(parseFloat(volWeight).toFixed(2));
            }
        })

    })

</script>
</body>
</html>
