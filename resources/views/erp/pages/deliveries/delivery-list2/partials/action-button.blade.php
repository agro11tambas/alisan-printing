<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a href="/erp/deliveries/print-waybill/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-printer"></i>
                <span>Print Waybill</span>
            </a>
        </li>
        @if ($order->status != 'Delivered')
        <li>
            <a href="/erp/deliveries/create/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-plus"></i>
                <span>Add Delivery</span>
            </a>
        </li>
        <!-- <li>
                <button type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#modalMarkAsCompletedOrder"
                    data-id="{{ $order->id }}"
                    data-name="{{ $order->order_number }}"
                    data-url="{{ url('/erp/mark-as-delivered/' . $order->id) }}">
                    <i class="feather feather-check"></i>
                    <span>Mark as Delivered</span>
                </button>
            </li> -->
        @endif
        <!-- @if ($order->status === 'Delivered')
            <li>
                <a class="dropdown-item" href="/erp/edit-order/{{ $order->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
            @endif -->
        <li>
            <a href="/erp/deliveries/history/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-info me-3"></i>
                <span>History & Info</span>
            </a>
        </li>
        <!-- <li>
                <hr class="my-2">
            </li>
            <li>
                <a href="/delivery-list/detail-order/{{ $order->id }}" class="dropdown-item">
                    <i class="feather feather-eye"></i>
                    <span>Order Detail</span>
                </a>
            </li> -->
    </ul>
</div>