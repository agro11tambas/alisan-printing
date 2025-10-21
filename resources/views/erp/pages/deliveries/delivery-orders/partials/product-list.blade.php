<div class="table-responsive">
    <table class="table table-hover bg-transparent table-sm table-bordered align-middle">
        <thead>
            <tr>
                <th>Product</th>
                <th>Completed Waiting List</th>
                <th>Delivered (Finished)</th>
                <th>On Delivery (Not Finished)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($do->items as $item)
            @php
            // ✅ ambil progress dari relasi orderProgressItem
            $completed = optional(
            $item->orderProgress->items
            ->where('product_id', $item->product_id)
            ->first()
            )->completed_quantity ?? 0;

            // ✅ hitung total shipped_qty berdasarkan status shipment
            $finishedQty = $item->deliveryListItems
            ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
            ->sum('shipped_quantity');

            $notFinishedQty = $item->deliveryListItems
            ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
            ->sum('shipped_quantity');
            @endphp

            <tr>
                <td>
                    <span class="fw-bold text-primary">
                        {{ $item->product?->name ?? '-' }}
                    </span>
                </td>
                <td>
                    <span>{{ number_format($completed) }}</span>
                </td>
                <td>
                    <span class="fw-bold text-success">{{ number_format($finishedQty) }}</span> / {{ number_format($item->progress_qty) }}
                </td>
                <td>
                    <span class="fw-bold text-warning">{{ number_format($notFinishedQty) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>