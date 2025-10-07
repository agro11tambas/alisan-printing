<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 33%;">Product</th>
                <th style="width: 33%;">Progress</th>
                <th style="width: 33%;">Available Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
            <tr>
                <td>
                    <span class="fw-bold text-dark">@if($item->product)
                        {{ $item->product->name }}
                        @elseif($item->productBundle)
                        {{ $item->productBundle->name }}
                        @endif
                    </span>
                </td>
                <td><span class="fw-bold text-success">{{ $item->completed_quantity }}</span>/<span class="fw-bold text-primary">{{ $item->quantity }}</td>
                <td><span class="fw-bold text-danger">{{ $item->stock_out }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>