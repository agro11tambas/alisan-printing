<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Note</th>
            <th style="width: 60px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td>{{ number_format($item->shipped_quantity ?? 0, 0, ',', '.') }}</td>
                <td>{{ $item->note ?? '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-edit-history"
                        data-id="{{ $item->id }}"
                        data-product="{{ $item->product->name ?? '-' }}"
                        data-quantity="{{ $item->shipped_quantity ?? 0 }}"
                        data-note="{{ $item->note ?? '' }}">
                        Edit
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
