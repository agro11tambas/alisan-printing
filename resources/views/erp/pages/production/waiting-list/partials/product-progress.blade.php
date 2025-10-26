<div class="table-responsive">
    <table class="table bg-transparent table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 33%;">Product</th>
                <th style="width: 33%;">Progress</th>
                <th style="width: 33%;">Available Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($progress->items as $item)
            <tr>
                <td>
                    <span class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</span><br>
                </td>
                <td>
                    <span class="fw-bold text-success">{{ number_format($item->completed_quantity, 0, ',', '.') }}</span> /
                    <span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span><br>
                </td>
                <td>
                    <span class="fw-bold text-dark">{{ number_format($item->product->productionStocks->available_quantity, 0, ',', '.') }}</span><br>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>