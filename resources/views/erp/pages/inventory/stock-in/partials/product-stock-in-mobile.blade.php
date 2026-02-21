@foreach ($inventory->items as $item)
    <div class="stockin-product d-flex justify-content-between align-items-start">
        <div class="pe-2">
            <div class="fw-semibold text-truncate">
                {{ $item->product->name ?? '-' }}
            </div>
            <small class="text-muted">
                Qty: {{ number_format($item->quantity, 0, ',', '.') }}
            </small>
        </div>

        <div class="text-end">
            <small class="text-muted d-block">In</small>
            <span class="fw-semibold">
                {{ $item->stock_in }}
            </span>
        </div>
    </div>
@endforeach
