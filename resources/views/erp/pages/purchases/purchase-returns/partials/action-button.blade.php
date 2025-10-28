<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if ($purchase->payment_status !== 'Paid')
            {{-- <li>
            <button type="button"
                class="dropdown-item btn-mark-paid"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $purchase->id }}"
                data-paid="{{ $purchase->refund_amount }}"
                data-name="{{ $purchase->purchase_number }}"
                data-total-amount="{{ $purchase->total_amount }}"
                data-paid-amount="{{ $purchase->refund_amount }}"
                data-url="{{ url('/erp/purchases/purchase-returns/mark-as-refund/' . $purchase->id) }}">
                <i class="feather feather-check"></i>
                <span>Mark as Refund</span>
            </button>
        </li> --}}
            @if ($purchase->remaining_amount_product > 0)
                <li>
                    <button type="button" class="dropdown-item btn-mark-refund-product" data-bs-toggle="modal"
                        data-bs-target="#modalRefundProduct" data-id="{{ $purchase->id }}"
                        data-url="{{ url('/erp/purchases/purchase-returns/mark-as-refund-product/' . $purchase->id) }}"
                        data-total-amount="{{ $purchase->total_amount_product }}"
                        data-paid-amount="{{ $purchase->refund_amount_product }}">
                        <i class="feather feather-check"></i>
                        <span>Mark as Refund (Product)</span>
                    </button>
                </li>
            @endif
            @if ($purchase->remaining_amount_freight > 0)
                <li>
                    <button type="button" class="dropdown-item btn-mark-refund-freight" data-bs-toggle="modal"
                        data-bs-target="#modalRefundFreight" data-id="{{ $purchase->id }}"
                        data-url="{{ url('/erp/purchases/purchase-returns/mark-as-refund-freight/' . $purchase->id) }}"
                        data-total-amount="{{ $purchase->total_amount_freight }}"
                        data-paid-amount="{{ $purchase->refund_amount_freight }}">
                        <i class="feather feather-truck"></i>
                        <span>Mark as Refund (Freight)</span>
                    </button>
                </li>
            @endif
        @endif
        <li>
            <a href="/erp/purchases/purchase-returns/payment-history/{{ $purchase->id }}" class="dropdown-item">
                <i class="feather feather-dollar-sign me-3"></i>
                <span>Payment History</span>
            </a>
        </li>
        <li>
            <a href="/erp/purchases/purchase-returns/detail-purchase/{{ $purchase->id }}" class="dropdown-item">
                <i class="feather feather-eye"></i>
                <span>Purchase Detail</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="/erp/purchases/purchase-returns/edit-purchase-return/{{ $purchase->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="/erp/purchases/purchase-returns/edit-history/{{ $purchase->id }}">
                <i class="feather feather-clock me-3"></i>
                <span>Edit History</span>
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeletePurchase" data-id="{{ $purchase->id }}" data-name="{{ $purchase->name }}"
                data-url="{{ url('/erp/purchases/purchase-returns/delete/' . $purchase->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
