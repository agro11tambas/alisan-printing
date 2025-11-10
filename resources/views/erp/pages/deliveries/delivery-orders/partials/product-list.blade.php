<div class="table-responsive">
    <table class="table table-hover bg-transparent table-small table-bordered align-middle">
        <thead>
            <tr>
                <th>Product</th>
                <th>Ready Qty</th>
                <th>Delivered</th>
                <th>On Delivery</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($do->items as $item)
                @php
                    $readyQty = $item->ready_qty ?? 0;

                    // 🔹 completed_quantity hanya kalau ada design_item_id
                    $completedQty = $item->design_item_id
                        ? optional($item->orderProgressItem)->completed_quantity ?? 0
                        : 0;

                    $finishedQty = $item->deliveryListItems
                        ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
                        ->sum('shipped_quantity');

                    $notFinishedQty = $item->deliveryListItems
                        ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
                        ->sum('shipped_quantity');
                @endphp

                <tr>
                    <td>
                        <span class="fw-bold text-primary">{{ $item->product?->name ?? '-' }}</span>
                        @if ($item->satuan)
                            <span class="badge bg-secondary ms-1">{{ $item->satuan }}</span>
                        @endif
                    </td>
                    <td>
                        <span>
                            {{ number_format($readyQty, 0, ',', '.') }}
                            @if ($completedQty > 0)
                                / {{ number_format($completedQty, 0, ',', '.') }}
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="fw-bold text-success">{{ number_format($finishedQty, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        <span class="fw-bold text-warning">{{ number_format($notFinishedQty, 0, ',', '.') }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
