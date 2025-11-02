<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered">
        <thead>
            <tr>
                <th style="width: 25%;">Product</th>
                <th style="width: 25%;">Qty</th>
                <th style="width: 25%;">Stock In</th>
                <th style="width: 25%;">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventory->items as $item)
            <tr>
                <td><span class="fw-bold text-dark">{{ $item->product->name }}</span></td>
                <td><span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-success">{{ number_format($item->stock_in, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-danger">{{ number_format($item->quantity - $item->stock_in, 0, ',', '.') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>