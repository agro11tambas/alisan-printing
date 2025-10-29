<div class="table-responsive">
    <table class="table table-hover bg-transparent table-sm table-bordered align-middle">
        <thead>
            <tr>
                <th>Product</th>
                <th>Completed Waiting List</th>
                <th>Delivered</th>
                <th>On Delivery</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($do->items as $item)
                @php
                    $completed =
                        optional($item->orderProgress->items->where('product_id', $item->product_id)->first())
                            ->completed_quantity ?? 0;

                    $waitingListQty = 
                        optional($item->orderProgress->items->where('product_id', $item->product_id)->first())
                            ->quantity ?? 0;

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
                        <span>{{ number_format($completed, 0, ',', '.') }} / {{ number_format($waitingListQty, 0, ',', '.') }}</span>
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
