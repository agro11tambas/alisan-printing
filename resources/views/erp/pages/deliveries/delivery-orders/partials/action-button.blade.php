<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            $completed = $do->items->sum(function ($item) {
                return optional($item->orderProgress->items->where('product_id', $item->product_id)->first())
                    ->completed_quantity ?? 0;
            });

            $allShipped = $do->items->every(function ($item) {
                return $item->shipped_qty >= $item->progress_qty;
            });
        @endphp

        @if ($completed > 0 && !$allShipped)
            <li>
                <a href="{{ url('/erp/deliveries/delivery-list/create-delivery-list/' . $do->id) }}"
                    class="dropdown-item">
                    <i class="feather feather-plus"></i>
                    <span>Add Delivery List</span>
                </a>
            </li>
        @endif
        <li>
            <a href="{{ url('/erp/deliveries/delivery-orders/history-delivery-order/' . $do->id) }}"
                class="dropdown-item">
                <i class="feather feather-clock"></i>
                <span>Delivery History</span>
            </a>
        </li>
    </ul>
</div>
