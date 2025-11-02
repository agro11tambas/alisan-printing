<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <button type="button"
                class="dropdown-item btn-mark-sale"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $order->id }}"
                data-name="{{ $order->order_number }}"
                data-order-number="{{ $order->order_number }}"
                data-total-amount="{{ $order->grand_total }}"
                data-paid-amount="{{ $order->paid_amount }}"
                data-url="{{ url('/erp/sales/mark-as-sale-list/' . $order->id) }}">
                <i class="feather feather-check"></i>
                <span>Mark as Sale List</span>
            </button>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-share-invoice"
                data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-orders/invoice/' . $order->id) }}">
                <i class="feather feather-file-text me-3"></i>
                <span>Invoice</span>
            </button>
        </li>
        <li>
            <a href="/erp/sales/sale-orders/detail-order/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-eye"></i>
                <span>Order Detail</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="/erp/sales/sale-orders/edit-order/{{ $order->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteOrder"
                data-id="{{ $order->id }}"
                data-name="{{ $order->name }}"
                data-url="{{ url('/erp/sales/sale-orders/delete/' . $order->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
