<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ACCELERATOR</title>

    <!-- Bootstrap -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
        <!-- jVectorMap -->
    <link href="/css/maps/jquery-jvectormap-2.0.3.css" rel="stylesheet"/>

    <!-- Custom Theme Style -->
    <link href="/css/custom.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato';
        }

        .fa-btn {
            margin-right: 6px;
        }
    </style>
</head>
<body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a href="/" class="site_title"><i class="fa fa-paw"></i> <span>Accelerator</span></a>
            </div>

            <div class="clearfix"></div>

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <ul class="nav side-menu">
                  <li><a><i class="fa fa-wrench"></i> Marketing Tool <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="/v3/tracer/1/edit">Tracer SKU Setting </a></li>
                      <li><a href="/v3/pricing/index/amazon">Amazon Pricing Tool</a></li>
                      <li><a href="/v3/pricing/index/newegg">Newegg Pricing Tool</a></li>
                      <li><a href="/v3/pricing/index/ebay">Ebay Pricing Tool</a></li>
                      <li><a href="/v3/pricing/index/lazada">Lazada Pricing Tool</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-edit"></i> Content Management <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="/index/4">Marketplace Category Management</a></li>
                      <li><a href="/index/5">Category Management</a></li>
                      <li><a href="/index/6">Product Management</a></li>
                      <li><a href="/index/7">Complementary Accessory Mapping</a></li>
                      <li><a href="/index/8">Latest Arrivals Management</a></li>
                      <li><a href="/index/9">Warranty Admin</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-table"></i> Order Management <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="/index/10">Integrated Order Fulfillment</a></li>
                      <li><a href="/index/11">Courier Order Manage</a></li>
                      <li><a href="/index/12">On Hold Admin - Logistics Approval Page</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-clone"></i>Report <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="/index/13">Aftership Cron Job</a></li>
                        <li><a href="/index/14">auto generate transfer order</a></li>
                        <li><a href="/index/15">Stock Valuation Report</a></li>
                        <li><a href="/index/16">Sales Report</a></li>
                        <li><a href="/index/17">ME Summary Sales Report</a></li>
                        <li><a href="/index/18">Expander Sales Report - Beta</a></li>
                        <li><a href="/index/19">Marketing Report</a></li>
                        <li><a href="/index/20">Dispatch Report</a></li>
                        <li><a href="/index/21">Dispatch Report with HS Code</a></li>
                        <li><a href="/index/22">Shipped Order Detail</a></li>
                        <li><a href="/index/23">Order Fulfillment Report</a></li>
                        <li><a href="/index/24">Refund Order Report</a></li>
                        <li><a href="/index/25">Inventory Movement Report</a></li>
                        <li><a href="/index/26">On Time Delivery Report</a></li>
                        <li><a href="/index/27">Failed Transactions Report</a></li>
                        <li><a href="/index/28">Delayed Orders Report</a></li>
                        <li><a href="/index/29">Sales Comparison By Period Report</a></li>
                        <li><a href="/index/30">Voucher Report</a></li>
                        <li><a href="/index/31">Price Comparison Report</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-bar-chart-o"></i> Customer Service <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="/index/32">Order Quick Search</a></li>
                        <li><a href="/index/33">Order Refund</a></li>
                        <li><a href="/index/34">Special Order</a></li>
                        <li><a href="/index/35">Manual Order</a></li>
                        <li><a href="/index/36">Phone Sales</a></li>
                        <li><a href="/index/37">Order Reassessment Page</a></li>
                        <li><a href="/index/38">RMA Admin</a></li>
                        <li><a href="/index/39">Pre-order Admin</a></li>
                        <li><a href="/index/40">On Hold Admin</a></li>
                        <li><a href="/index/41">On Hold Admin - OC Page</a></li>
                        <li><a href="/index/42">On Hold Admin - CC Page</a></li>
                        <li><a href="/index/43">On Hold Admin - VV Page</a></li>
                        <li><a href="/index/44">On Hold Admin - Quotation Requested</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-clone"></i>Finance <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="/index/50">Gateway Report Download</a></li>
                        <li><a href="/index/51">Gateway Report Upload</a></li>
                        <li><a href="/index/52">Sales Invoice</a></li>
                        <li><a href="/index/53">Refund / Chargeback Invoice</a></li>
                        <li><a href="/index/54">GatewayFee Invoice</a></li>
                        <li><a href="/index/55">Pending Order Report</a></li>
                        <li><a href="/index/56">RIA Control Report</a></li>
                        <li><a href="/index/57">Website Bank Transfer Admin</a></li>
                        <li><a href="/index/58">Product Category Report</a></li>
                        <li><a href="/index/59">Merchant Payment Admin</a></li>
                        <li><a href="/index/60">RIA Refund Order Report</a></li>
                    </ul>
                  </li>
                  <li><a><i class="fa fa-clone"></i>Competitor Analysis Tool <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="/index/62">Competitor URL Mapping</a></li>
                        <li><a href="/index/63">Competitor Report</a></li>
                        <li><a href="/index/65">Add New Competitor</a></li>
                    </ul>
                  </li>
                </ul>
              </div>
            </div>
            <!-- /sidebar menu -->

            <!-- /menu footer buttons -->
            <div class="sidebar-footer hidden-small">
              <a data-toggle="tooltip" data-placement="top" title="Settings">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Lock">
                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
              </a>
              <a href="{{url('logout')}}" data-toggle="tooltip" data-placement="top" title="Logout">
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
              </a>
            </div>
            <!-- /menu footer buttons -->
          </div>
        </div>

        <!-- top navigation -->
        <div class="top_nav">
          <div class="nav_menu">
            <nav class="" role="navigation">
              <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
              </div>
            </nav>
          </div>
        </div>
        <!-- /top navigation -->

        @yield('content')

        <!-- footer content -->
        <footer>
          <div class="pull-right">

          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>




        <!-- jQuery -->
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="/vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="/vendors/nprogress/nprogress.js"></script>
    <!-- Chart.js -->
    <script src="/vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- gauge.js -->
    <script src="/vendors/bernii/gauge.js/dist/gauge.min.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="/vendors/iCheck/icheck.min.js"></script>
    <!-- Skycons -->
    <script src="/vendors/skycons/skycons.js"></script>
    <!-- Flot -->
    <script src="/vendors/Flot/jquery.flot.js"></script>
    <script src="/vendors/Flot/jquery.flot.pie.js"></script>
    <script src="/vendors/Flot/jquery.flot.time.js"></script>
    <script src="/vendors/Flot/jquery.flot.stack.js"></script>
    <script src="/vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="/js/flot/jquery.flot.orderBars.js"></script>
    <script src="/js/flot/date.js"></script>
    <script src="/js/flot/jquery.flot.spline.js"></script>
    <script src="/js/flot/curvedLines.js"></script>
    <!-- jVectorMap -->
    <script src="/js/maps/jquery-jvectormap-2.0.3.min.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="/js/moment/moment.min.js"></script>
    <script src="/js/datepicker/daterangepicker.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="/js/custom.min.js"></script>
        <script>
      $(document).ready(function() {
        var data1 = [
          [gd(2012, 1, 1), 17],
          [gd(2012, 1, 2), 74],
          [gd(2012, 1, 3), 6],
          [gd(2012, 1, 4), 39],
          [gd(2012, 1, 5), 20],
          [gd(2012, 1, 6), 85],
          [gd(2012, 1, 7), 7]
        ];

        var data2 = [
          [gd(2012, 1, 1), 82],
          [gd(2012, 1, 2), 23],
          [gd(2012, 1, 3), 66],
          [gd(2012, 1, 4), 9],
          [gd(2012, 1, 5), 119],
          [gd(2012, 1, 6), 6],
          [gd(2012, 1, 7), 9]
        ];
        $("#canvas_dahs").length && $.plot($("#canvas_dahs"), [
          data1, data2
        ], {
          series: {
            lines: {
              show: false,
              fill: true
            },
            splines: {
              show: true,
              tension: 0.4,
              lineWidth: 1,
              fill: 0.4
            },
            points: {
              radius: 0,
              show: true
            },
            shadowSize: 2
          },
          grid: {
            verticalLines: true,
            hoverable: true,
            clickable: true,
            tickColor: "#d5d5d5",
            borderWidth: 1,
            color: '#fff'
          },
          colors: ["rgba(38, 185, 154, 0.38)", "rgba(3, 88, 106, 0.38)"],
          xaxis: {
            tickColor: "rgba(51, 51, 51, 0.06)",
            mode: "time",
            tickSize: [1, "day"],
            //tickLength: 10,
            axisLabel: "Date",
            axisLabelUseCanvas: true,
            axisLabelFontSizePixels: 12,
            axisLabelFontFamily: 'Verdana, Arial',
            axisLabelPadding: 10
          },
          yaxis: {
            ticks: 8,
            tickColor: "rgba(51, 51, 51, 0.06)",
          },
          tooltip: false
        });

        function gd(year, month, day) {
          return new Date(year, month - 1, day).getTime();
        }
      });
    </script>
    <!-- /Flot -->

    <!-- Skycons -->
    <script>
      $(document).ready(function() {
        var icons = new Skycons({
            "color": "#73879C"
          }),
          list = [
            "clear-day", "clear-night", "partly-cloudy-day",
            "partly-cloudy-night", "cloudy", "rain", "sleet", "snow", "wind",
            "fog"
          ],
          i;

        for (i = list.length; i--;)
          icons.set(list[i], list[i]);

        icons.play();
      });
    </script>
    <!-- /Skycons -->

    <!-- Doughnut Chart -->
    <script>
      $(document).ready(function(){
        var options = {
          legend: false,
          responsive: false
        };

        if (document.getElementById("canvas1")) {
            new Chart(document.getElementById("canvas1"), {
              type: 'doughnut',
              tooltipFillColor: "rgba(51, 51, 51, 0.55)",
              data: {
                labels: [
                  "Symbian",
                  "Blackberry",
                  "Other",
                  "Android",
                  "IOS"
                ],
                datasets: [{
                  data: [15, 20, 30, 10, 30],
                  backgroundColor: [
                    "#BDC3C7",
                    "#9B59B6",
                    "#E74C3C",
                    "#26B99A",
                    "#3498DB"
                  ],
                  hoverBackgroundColor: [
                    "#CFD4D8",
                    "#B370CF",
                    "#E95E4F",
                    "#36CAAB",
                    "#49A9EA"
                  ]
                }]
              },
              options: options
            });
        }


      });
    </script>
    <!-- /Doughnut Chart -->

    <!-- bootstrap-daterangepicker -->
    <script>
      $(document).ready(function() {

        var cb = function(start, end, label) {
          console.log(start.toISOString(), end.toISOString(), label);
          $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        };

        var optionSet1 = {
          startDate: moment().subtract(29, 'days'),
          endDate: moment(),
          minDate: '01/01/2012',
          maxDate: '12/31/2015',
          dateLimit: {
            days: 60
          },
          showDropdowns: true,
          showWeekNumbers: true,
          timePicker: false,
          timePickerIncrement: 1,
          timePicker12Hour: true,
          ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
          },
          opens: 'left',
          buttonClasses: ['btn btn-default'],
          applyClass: 'btn-small btn-primary',
          cancelClass: 'btn-small',
          format: 'MM/DD/YYYY',
          separator: ' to ',
          locale: {
            applyLabel: 'Submit',
            cancelLabel: 'Clear',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
          }
        };
        $('#reportrange span').html(moment().subtract(29, 'days').format('MMMM D, YYYY') + ' - ' + moment().format('MMMM D, YYYY'));
        $('#reportrange').daterangepicker(optionSet1, cb);
        $('#reportrange').on('show.daterangepicker', function() {
          console.log("show event fired");
        });
        $('#reportrange').on('hide.daterangepicker', function() {
          console.log("hide event fired");
        });
        $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
          console.log("apply event fired, start/end dates are " + picker.startDate.format('MMMM D, YYYY') + " to " + picker.endDate.format('MMMM D, YYYY'));
        });
        $('#reportrange').on('cancel.daterangepicker', function(ev, picker) {
          console.log("cancel event fired");
        });
        $('#options1').click(function() {
          $('#reportrange').data('daterangepicker').setOptions(optionSet1, cb);
        });
        $('#options2').click(function() {
          $('#reportrange').data('daterangepicker').setOptions(optionSet2, cb);
        });
        $('#destroy').click(function() {
          $('#reportrange').data('daterangepicker').remove();
        });
      });
    </script>
    <!-- /bootstrap-daterangepicker -->

    <!-- gauge.js -->
<!--     <script>
      var opts = {
          lines: 12,
          angle: 0,
          lineWidth: 0.4,
          pointer: {
              length: 0.75,
              strokeWidth: 0.042,
              color: '#1D212A'
          },
          limitMax: 'false',
          colorStart: '#1ABC9C',
          colorStop: '#1ABC9C',
          strokeColor: '#F0F3F3',
          generateGradient: true
      };
      var target = document.getElementById('foo'),
          gauge = new Gauge(target).setOptions(opts);

      gauge.maxValue = 6000;
      gauge.animationSpeed = 32;
      gauge.set(3200);
      gauge.setTextField(document.getElementById("gauge-text"));
    </script> -->
    <!-- /gauge.js -->

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
            url: "{{ url('/v3/listingSku/save') }}",
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
