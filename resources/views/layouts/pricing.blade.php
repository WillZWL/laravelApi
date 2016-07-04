<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Pricing Tool</title>
  <link rel="stylesheet" href="{{ asset('/bootstrap/css/pricing_tool.css') }}" type="text/css" media="all"/>
  <link rel="stylesheet" href="{{ asset('/bootstrap/css/bootstrap.css') }}" type="text/css" media="all"/>
</head>
<body>
  <div id="container">
    <section class="top">
      <p>
        Pricing Tool
      </p>
    </section>

    <section class="sub_top">
      <div class="row">
        @yield('search-form')
      </div>
    </section>

    <section>
      <div class="row">
        <div class="col-md-3" id="sku-list-warp">
          @yield('sku-list')
        </div>
        <div class="col-md-9" id="sku-listing-info-wrap">
          @yield('content')
        </div>
      </div>
    </section>
  </div>

  <script type="text/javascript" src="{{ asset('/bootstrap/js/jquery.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/bootstrap/js/bootstrap.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/bootstrap/js/jquery.pjax.js') }}"></script>
  <script type="text/javascript">
    $(function () {
      $(document).on('submit', 'form[data-pjax]', function(event) {
        $.pajx.submit(event, '#sku-list-warp');
      });

      $(document).on('click', '#sku-list a', function(e) {
        e.preventDefault();
        $.ajax({
          method: 'GET',
          url: "{{ url('/v2/pricing/info') }}",
          data:   {
            marketplaceSku: e.target.href.split('#')[1],
            marketplace: 'BCAMAZON'
          },
          dataType: 'html'
        }).done(function (responseText) {
          $('#sku-listing-info-wrap').html(responseText);
        }).fail(function (jqXHR, textStatus) {
          alert( "Request failed: " + textStatus );
        });
      });

      var selectSku = window.location.href.split('#')[1];

      if (selectSku) {
        $('#'+selectSku).click();
      }


    });
  </script>


</body>
</html>
