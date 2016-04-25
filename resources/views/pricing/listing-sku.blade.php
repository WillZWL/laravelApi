<div id="sku-list">
    <table class="table table-condensed table-responsive table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Name</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    <td class="col-md-4">
                        <a href="?marketplace={{ $item->marketplace_id }}&marketplaceSku={{ $item->marketplace_sku }}">
                            {{ $item->marketplace_sku }}
                        </a>
                    </td>
                    <td>{{ $item->name }}</td>
                </tr>
            @empty
                <tr><td>No SKU Found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{--<ul class="list-unstyled">--}}
        {{--@forelse($data as $item)--}}
        {{--<li>--}}
            {{--<a href="?marketplace={{ $item->marketplace_id }}&marketplaceSku={{ $item->marketplace_sku }}" class="marketplace-sku">--}}
                {{--{{ $item->marketplace_sku }}--}}
            {{--</a>--}}
            {{--<br>--}}
            {{--{{ $item->name }}--}}
        {{--</li>--}}
        {{--@empty--}}
            {{--<li>--}}
                {{--No result.--}}
            {{--</li>--}}
        {{--@endforelse--}}
    {{--</ul>--}}
</div>
<script type="text/javascript">
    $(document).off('click', '#sku-list a').on('click', '#sku-list a', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'GET',
            url: "{{ url('/pricing/info') }}",
            data: e.target.href.split('?')[1],
            dataType: 'html'
        }).done(function (responseText) {
            $('#sku-listing-info-wrap').html(responseText);
        }).fail(function (jqXHR, textStatus) {
            alert( "Request failed: " + textStatus );
        });
    });
</script>
