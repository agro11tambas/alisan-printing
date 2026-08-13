<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Rusak</th>
            <th>Ditolak</th>
            <th>Mesin</th>
            <th>Note</th>
            <th style="width: 60px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->progressItem->product->name }}</td>
                <td>{{ number_format($item->completed_quantity, 0, ',', '.') }}</td>
                <td>{{ number_format($item->defect_quantity, 0, ',', '.') }}</td>
                <td>{{ number_format($item->reject_quantity, 0, ',', '.') }}</td>
                {{-- data lama masih menyimpan operator, data baru menyimpan mesin --}}
                <td>{{ $item->machines->name ?? ($item->operators->name ?? '-') }}</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td class="text-center">
                    <div class="d-flex flex-row gap-2">
                        <button type="button" class="btn btn-sm btn-primary btn-edit-history"
                            data-id="{{ $item->id }}" data-product="{{ $item->progressItem->product->name }}"
                            data-quantity="{{ $item->completed_quantity }}" data-defect="{{ $item->defect_quantity }}"
                            data-reject="{{ $item->reject_quantity }}"
                            data-operator="{{ $item->machines->name ?? ($item->operators->name ?? '') }}"
                            data-note="{{ $item->notes ?? '' }}">
                            Edit
                        </button>

                        <button type="button" class="btn btn-sm btn-danger btn-delete-history"
                            data-id="{{ $item->id }}" data-product="{{ $item->progressItem->product->name }}">
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
