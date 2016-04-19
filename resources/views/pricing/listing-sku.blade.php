<div id="sku-list">
    <ul class="list-unstyled">
        @forelse($data as $item)
        <li>
            <a href="?marketplace={{ $item->marketplace_id }}&marketplaceSku={{ $item->marketplace_sku }}" class="marketplace-sku">
                {{ $item->marketplace_sku }}
            </a>
            <br>
            {{ $item->name }}
        </li>
        @empty
            <li>
                No result.
            </li>
        @endforelse
    </ul>
</div>
<script>
    $(document).on('click', '#sku-list a', function(e) {
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
