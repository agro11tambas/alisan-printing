<div class="dropdown mobile-action-dropdown">
    <ul class="dropdown-menu mobile-action-menu">
        <li>
            <button type="button" class="dropdown-item btn-share-invoice-image" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-orders/invoice/' . $order->id) }}"
                data-customer="{{ $order->customer->name }}">
                <i class="feather feather-share-2 me-3"></i>
                <span>Share Invoice</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-mark-sale" data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus" data-id="{{ $order->id }}" data-name="{{ $order->order_number }}"
                data-order-number="{{ $order->order_number }}" data-total-amount="{{ $order->grand_total }}"
                data-paid-amount="{{ $order->paid_amount }}" data-deposit="{{ $order->customer?->customer_deposit ?? 0 }}"
                data-url="{{ url('/erp/sales/mark-as-sale-list/' . $order->id) }}">
                <i class="feather feather-check"></i>
                <span>Mark as Sale List</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-share-wa" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-orders/invoice/' . $order->id) }}"
                data-phone="{{ $order->customer->phone }}" data-business="{{ $order->business_name }}"
                data-invoice="{{ $order->order_number }}"
                data-total="{{ number_format($order->grand_total, 0, ',', '.') }}">
                <i class="feather feather-share-2 me-3"></i>
                <span>Share ke WA</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-view-invoice" data-id="{{ $order->id }}"
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
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeleteOrder" data-id="{{ $order->id }}" data-name="{{ $order->name }}"
                data-url="{{ url('/erp/sales/sale-orders/delete/' . $order->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
