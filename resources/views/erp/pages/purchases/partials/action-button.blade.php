<div class="hstack gap-2 justify-content-end">
    <!-- <a href="/purchases/add-progress-purchase/{{ $purchase->id }}" class="avatar-text avatar-md">
        <i class="feather feather-plus"></i>
    </a>
    <a href="/purchases/detail-purchase/{{ $purchase->id }}" class="avatar-text avatar-md">
        <i class="feather feather-eye"></i>
    </a> -->
    <a href="{{ asset('storage/' . $purchase->image) }}" target="_blank" class="avatar-text avatar-md">
        <i class="feather feather-file-text"></i>
    </a>
    <div class="dropdown">
        <a href="javascript:void(0)" class="avatar-text avatar-md" data-bs-toggle="dropdown" data-bs-offset="0,21">
            <i class="feather feather-more-horizontal"></i>
        </a>
        <ul class="dropdown-menu">
            <!-- <li>
                <a class="dropdown-item" href="/erp/purchases/history-purchase/{{ $purchase->id }}">
                    <i class="feather feather-info me-3"></i>
                    <span>History</span>
                </a>
            </li> -->
            <li>
                <a class="dropdown-item" href="/erp/purchases/edit-purchase/{{ $purchase->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
            <li>
                <button type="button"
                    class="dropdown-item btn-delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDeletePurchase"
                    data-id="{{ $purchase->id }}"
                    data-name="{{ $purchase->name }}"
                    data-url="{{ url('/erp/purchases/purchase-orders/delete/' . $purchase->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        </ul>
    </div>
</div>