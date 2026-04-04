{{-- <table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Catatan</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->inventoryItem->product->name ?? '-' }}</td>
                <td>{{ number_format($item->stock_in, 0, ',', '.') }} pcs</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary edit-item" data-id="{{ $item->id }}"
                        data-product="{{ $item->inventoryItem->product->name ?? '-' }}" data-qty="{{ $item->stock_in }}"
                        data-notes="{{ $item->notes }}">
                        Edit
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table> --}}


<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Catatan</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            @php $qty = $item->merged_stock_in ?? $item->stock_in; @endphp
            <tr>
                <td>{{ $item->inventoryItem->product->name ?? '-' }}</td>
                <td>{{ number_format($qty, 0, ',', '.') }} pcs</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary edit-item" data-id="{{ $item->id }}"
                        data-product="{{ $item->inventoryItem->product->name ?? '-' }}" data-qty="{{ $qty }}"
                        data-notes="{{ $item->notes }}">
                        Edit
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
