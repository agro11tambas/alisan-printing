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
                @php
                    $conv = $item->unit_conversion_value ?? 1;
                    $unit = $item->unit_name ?? 'Pcs';
                    $qtyBase = $item->qty_base ?? $item->quantity;
                    $remaining = $qtyBase - $item->stock_in;
                @endphp
                <tr>
                    <td><span class="fw-bold text-dark">{{ $item->product->name }}</span></td>
                    <td>
                        <span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}
                            {{ $unit }}</span>
                        @if ($conv > 1)
                            <br><small class="text-muted">{{ number_format($qtyBase, 0, ',', '.') }} Pcs</small>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold text-success">{{ number_format($item->stock_in / $conv, 0, ',', '.') }}
                            {{ $unit }}</span>
                        @if ($conv > 1)
                            <br><small class="text-muted">{{ number_format($item->stock_in, 0, ',', '.') }} Pcs</small>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold text-danger">{{ number_format($remaining / $conv, 0, ',', '.') }}
                            {{ $unit }}</span>
                        @if ($conv > 1)
                            <br><small class="text-muted">{{ number_format($remaining, 0, ',', '.') }} Pcs</small>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
