<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Operator</th>
            <th>Note</th>
            <th style="width: 60px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->progressItem->product->name }}</td>
                <td>{{ number_format($item->change_quantity) }}</td>
                <td>{{ $item->operators->name ?? '-' }}</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-edit-history"
                        data-id="{{ $item->id }}" data-product="{{ $item->progressItem->product->name }}"
                        data-quantity="{{ $item->change_quantity }}" data-operator="{{ $item->operators->name ?? '' }}"
                        data-note="{{ $item->notes ?? '' }}">
                        Edit
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
