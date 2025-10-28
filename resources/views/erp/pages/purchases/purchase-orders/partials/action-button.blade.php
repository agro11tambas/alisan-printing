<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            {{-- <button type="button" class="dropdown-item btn-mark-purchase" data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus" data-id="{{ $purchase->id }}"
                data-purchase-number="{{ $purchase->purchase_number }}"
                data-total-amount-product="{{ $purchase->total_amount_product }}"
                data-paid-amount-product="{{ $purchase->paid_amount_product }}"
                data-total-amount-freight="{{ $purchase->total_amount_freight }}"
                data-paid-amount-freight="{{ $purchase->paid_amount_freight }}"
                data-url="{{ url('/erp/purchases/purchase-orders/mark-as-purchase-list/' . $purchase->id) }}">
                <i class="feather feather-check"></i>
                <span>Mark as Purchase List</span>
            </button> --}}
            <a href="/erp/purchases/purchase-orders/mark-as-purchase-list/{{ $purchase->id }}"
                class="dropdown-item">
                <i class="feather feather-check"></i>
                <span>Mark as Purchase List</span>
            </a>
        </li>
        <li>
            <a href="/erp/purchases/purchase-orders/detail-purchase/{{ $purchase->id }}" class="dropdown-item">
                <i class="feather feather-eye"></i>
                <span>Purchase Detail</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="/erp/purchases/purchase-orders/edit-purchase/{{ $purchase->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeletePurchase" data-id="{{ $purchase->id }}" data-name="{{ $purchase->name }}"
                data-url="{{ url('/erp/purchases/purchase-orders/delete/' . $purchase->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
