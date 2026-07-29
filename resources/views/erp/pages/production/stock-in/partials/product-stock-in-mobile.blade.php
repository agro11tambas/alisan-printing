@foreach ($inventory->items as $item)
    @php
        $conversion = max(1, (float) ($item->unit_conversion_value ?? 1));
        $unit = $item->unit_name ?? 'Pcs';
        $qtyBase = (float) ($item->qty_base ?? $item->quantity);
        $stockInBase = (float) $item->stock_in;
        $remainingBase = max(0, $qtyBase - $stockInBase);
    @endphp
    <div class="stockin-product">
        <div class="stockin-product-name">{{ $item->product->name ?? '-' }}</div>
        <div class="stockin-product-values">
            <span class="text-primary">Qty:
                {{ number_format($qtyBase / $conversion, 0, ',', '.') }} {{ $unit }}</span>
            <span class="text-success">In:
                {{ number_format($stockInBase / $conversion, 0, ',', '.') }} {{ $unit }}</span>
            <span class="text-danger">Remaining:
                {{ number_format($remainingBase / $conversion, 0, ',', '.') }} {{ $unit }}</span>
        </div>
    </div>
@endforeach
