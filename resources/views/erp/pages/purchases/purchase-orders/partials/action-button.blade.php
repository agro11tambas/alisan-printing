<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        {{-- PO cuma wadah per supplier. Create Purchase List ada di halaman Purchase List anaknya. --}}
        @if (Auth::user()->hasSubPermission('purchase-list'))
        <li>
            <a href="/erp/purchases/purchase-list?purchase_order_id={{ $purchase->id }}"
                class="dropdown-item">
                <i class="feather feather-list"></i>
                <span>Purchase List</span>
            </a>
        </li>
        @endif
        {{-- Tombol Delete hilang kalau salah satu Purchase List anaknya sudah
             punya Stock In atau sudah dibayar. --}}
        @if (!($hasStockIn ?? false) && !($hasPaidPurchaseList ?? false))
            <li>
                <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                    data-bs-target="#modalDeletePurchase" data-id="{{ $purchase->id }}"
                    data-name="{{ $purchase->purchase_number ?? $purchase->name }}"
                    data-url="{{ url('/erp/purchases/purchase-orders/delete/' . $purchase->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        @endif

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
