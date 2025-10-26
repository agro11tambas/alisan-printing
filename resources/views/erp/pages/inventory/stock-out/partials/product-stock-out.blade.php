<div class="table-responsive">
    <table class="table table-sm bg-transparent table-bordered">
        <thead>
            <tr>
                <th style="width: 50%;">Product</th>
                <th style="width: 50%;">Qty</th>
                <th style="width: 50%;">Stock Out</th>
                <th style="width: 50%;">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventory->items as $item)
            <tr>
                <td><span class="fw-bold text-dark">{{ $item->product->name }}</span></td>
                <td><span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-success">{{ number_format($item->stock_out, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-danger">{{ number_format($item->quantity - $item->stock_out, 0, ',', '.') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>