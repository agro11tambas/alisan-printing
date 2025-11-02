<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Kurir</th>
            <th>Note</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>
                @if($item->orderItem->product)
                {{ $item->orderItem->product->name }}
                @endif

                @if($item->orderItem->productBundle)
                {{ $item->orderItem->productBundle->name }}
                @endif
            </td>
            <td>{{ number_format($item->delivered_quantity, 0, ',', '.') }} pcs</td>
            <td>{{ $item->kurir ?? '-' }}</td>
            <td>{{ $item->note ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
