<div class="dropdown mobile-action-dropdown">
    <ul class="dropdown-menu mobile-action-menu">
        <li>
            <button type="button" class="dropdown-item btn-share-invoice" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-list/invoice/' . $order->id) }}"
                data-phone="{{ $order->customer->phone }}" data-business="{{ $order->business_name }}"
                data-invoice="{{ $order->order_number }}"
                data-total="{{ number_format($order->grand_total, 0, ',', '.') }}">
                <i class="feather feather-share-2 me-2"></i>
                <span>Share ke WA</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-share-invoice-image" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-list/invoice/' . $order->id) }}"
                data-customer="{{ $order->customer->name }}">
                <i class="feather feather-share-2 me-2"></i>
                Share Invoice
            </button>
        </li>

        <li>
            <button type="button" class="dropdown-item btn-share-invoice" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-list/invoice/' . $order->id) }}">
                <i class="feather feather-file-text me-2"></i>
                Invoice
            </button>
        </li>

        <li>
            <a href="/erp/sales/sale-list/detail-order/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-eye me-2"></i>
                Order Detail
            </a>
        </li>

        @if ($order->payment_status !== 'Paid' && $order->payment_status !== 'Overpaid')
            <li>
                <button type="button" class="dropdown-item btn-mark-paid" data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus" data-id="{{ $order->id }}"
                    data-total-amount="{{ $order->grand_total }}" data-paid-amount="{{ $order->paid_amount }}"
                    data-url="{{ url('/erp/sales/sale-list/mark-as-paid/' . $order->id) }}"
                    data-deposit="{{ $order->customer->customer_deposit }}">
                    <i class="feather feather-check me-2"></i>
                    Mark as Paid
                </button>
            </li>
        @endif

        <li>
            <a href="/erp/sales/sale-list/payment-history/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-dollar-sign me-2"></i>
                Payment History
            </a>
        </li>

        <li>
            <a href="/erp/sales/sale-list/edit-order/{{ $order->id }}" class="dropdown-item">
                <i class="feather feather-edit-3 me-2"></i>
                Edit
            </a>
        </li>

        <li>
            <button type="button" class="dropdown-item text-danger btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeleteOrder" data-id="{{ $order->id }}"
                data-url="{{ url('/erp/sales/sale-list/delete/' . $order->id) }}">
                <i class="feather feather-trash-2 me-2"></i>
                Delete
            </button>
        </li>
    </ul>
</div>
