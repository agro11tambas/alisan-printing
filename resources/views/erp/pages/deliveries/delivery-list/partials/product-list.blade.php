<div class="table-responsive">
    <table class="table table-small table-hover bg-transparent table-bordered mb-0">
        <thead>
            <tr>
                <th>Product</th>
                <!-- <th>Ordered Qty</th> -->
                <th>Shipped Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dl->items as $item)
            <tr>
                <td>
                    <span class="fw-bold text-primary">
                        {{ $item->product?->name ?? '-' }}
                    </span>
                </td>
                <!-- <td>
                    <span>{{ $item->deliveryOrderItem?->quantity ?? 0 }}</span>
                </td> -->
                <td>
                    <span class="fw-bold text-success">{{ number_format($item->shipped_quantity, 0, ',', '.') }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
