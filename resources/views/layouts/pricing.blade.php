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
        Pricing Tool
      </p>
    </section>

    @include('v2.pricing.search-form')

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
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      method: "POST",
      url: "{{ url('/v2/listingSku/save') }}",
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
  <script type="text/javascript">
    $(function () {


      // $('.marketplaceSku a[data-pjax]').on('pjax:clicked', function(e) {

      //   alert('ffdsa');
      //   $.ajax({
      //     method: 'GET',
      //     url: "{{ url('/v2/pricing/info') }}",
      //     data:   {
      //       marketplaceSku: e.target.href.split('#')[1],
      //       marketplace: 'BCAMAZON'
      //     },
      //     dataType: 'html'
      //   }).done(function (responseText) {
      //     $('#sku-listing-info-wrap').html(responseText);
      //   }).fail(function (jqXHR, textStatus) {
      //     alert( "Request failed: " + textStatus );
      //   });
      // })

      $(document).pjax('.marketplaceSku a[data-pjax]', '#sku-listing-info-wrap');

      // $(document).on('click', '#sku-list a', function(e) {
      //   e.preventDefault();
      //   $.ajax({
      //     method: 'GET',
      //     url: "{{ url('/v2/pricing/info') }}",
      //     data:   {
      //       marketplaceSku: e.target.href.split('#')[1],
      //       marketplace: 'BCAMAZON'
      //     },
      //     dataType: 'html'
      //   }).done(function (responseText) {
      //     $('#sku-listing-info-wrap').html(responseText);
      //   }).fail(function (jqXHR, textStatus) {
      //     alert( "Request failed: " + textStatus );
      //   });
      // });

      // var selectSku = window.location.href.split('#')[1];

      // if (selectSku) {
      //   $('#'+selectSku).click();
      // }
    });
  </script>
</body>
</html>
