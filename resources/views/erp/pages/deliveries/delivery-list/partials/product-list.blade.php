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
                        @php
                            $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                            if ($unitConversionValue <= 0) {
                                $unitConversionValue = 1;
                            }

                            $shippedDisplay = $item->shipped_quantity / $unitConversionValue;
                        @endphp

                        <span class="fw-bold text-success">
                            {{ number_format($shippedDisplay, 0, ',', '.') }}
                            {{ $item->deliveryOrderItem?->unit_name }}
                        </span>

                        <div class="small text-muted">
                            Base: {{ number_format($item->shipped_quantity, 0, ',', '.') }}
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
