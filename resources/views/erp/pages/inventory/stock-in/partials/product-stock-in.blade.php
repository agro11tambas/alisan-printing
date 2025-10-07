<div class="table-responsive">
    <table class="table bg-transparent table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 50%;">Product</th>
                <th style="width: 50%;">Qty</th>
                <th style="width: 50%;">Stock In</th>
                <th style="width: 50%;">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventory->items as $item)
            <tr>
                <td><span class="fw-bold text-dark">{{ $item->product->name }}</span></td>
                <td><span class="fw-bold text-primary">{{ $item->quantity }}</span></td>
                <td><span class="fw-bold text-success">{{ $item->stock_in }}</span></td>
                <td><span class="fw-bold text-danger">{{ $item->quantity - $item->stock_in }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>