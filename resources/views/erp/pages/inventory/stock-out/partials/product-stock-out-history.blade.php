<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>{{ $item->inventoryItem->product->name ?? '-' }}</td>
            <td>{{ number_format($item->stock_out, 0, ',', '.') }} pcs</td>
        </tr>
        @endforeach
    </tbody>
</table>