<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Operator</th>
            <th>Note</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>{{ $item->progressItem->product->name }}</td>
            <td>{{ $item->change_quantity }}</td>
            <td>{{ $item->operators->name ?? '-' }}</td>
            <td>{{ $item->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>