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
                    <span class="fw-bold text-success">{{ $item->completed_quantity }}</span> /
                    <span class="fw-bold text-primary">{{ $item->quantity }}</span><br>
                </td>
                <td>
                    <span class="fw-bold text-dark">{{ $item->product->productionStocks->available_quantity }}</span><br>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>