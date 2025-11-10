<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            $completed = $do->items->sum(fn($item) => $item->shipped_qty);
            $allShipped = $do->items->every(fn($item) => $item->shipped_qty >= $item->ready_qty);
        @endphp

        @if (!$allShipped)
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
