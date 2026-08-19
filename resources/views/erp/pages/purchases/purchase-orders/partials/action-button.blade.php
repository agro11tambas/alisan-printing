<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if (($purchase->approval_status ?? 'Draft') === 'Draft')
        <li>
            <button type="button" class="dropdown-item btn-approve-po"
                data-bs-toggle="modal" data-bs-target="#modalApprovePurchaseOrder"
                data-number="{{ $purchase->purchase_number }}"
                data-url="{{ route('purchase-orders.approve', $purchase->id) }}">
                <i class="feather feather-check-circle"></i>
                <span>Verify PO</span>
            </button>
        </li>
        @endif
        @if (in_array($purchase->approval_status, ['Approved', 'Partial'], true))
        <li>
            <a href="/erp/purchases/purchase-orders/mark-as-purchase-list/{{ $purchase->id }}"
                class="dropdown-item">
                <i class="feather feather-plus-circle"></i>
                <span>Create Purchase List</span>
            </a>
        </li>
        @endif
        @if (Auth::user()->hasSubPermission('purchase-list'))
        <li>
            <a href="/erp/purchases/purchase-list?purchase_order_id={{ $purchase->id }}"
                class="dropdown-item">
                <i class="feather feather-list"></i>
                <span>Purchase List</span>
            </a>
        </li>
        @endif
        <li>
            <a href="/erp/purchases/purchase-orders/detail-purchase/{{ $purchase->id }}" class="dropdown-item">
                <i class="feather feather-eye"></i>
                <span>Purchase Detail</span>
            </a>
        </li>
        {{-- PO tetap bisa diedit setelah verify; qty yang sudah dibuatkan PL dikunci di form edit. --}}
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

        @php $isOwner = auth()->check() && auth()->user()->role === 'Owner'; @endphp

        @if ($isOwner)
        <li>
            <button type="button" class="dropdown-item text-danger btn-force-delete-owner"
                data-bs-toggle="modal" data-bs-target="#modalForceDeleteOwnerPurchaseOrder"
                data-id="{{ $purchase->id }}"
                data-name="{{ $purchase->purchase_number ?? 'Purchase Order #' . $purchase->id }}"
                data-url="{{ route('purchases.purchase-orders.forceDeleteOwner', $purchase->id) }}">
                <i class="feather feather-zap-off me-3"></i>
                <span>Force Delete (Owner)</span>
            </button>
        </li>
        @endif
    </ul>
</div>
