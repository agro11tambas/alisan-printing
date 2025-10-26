<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            @if ($record->quantity > 0 && $record->status !== 'returned')
                <button type="button" class="dropdown-item btn-return" data-bs-toggle="modal"
                    data-bs-target="#modalProcessReject" data-id="{{ $record->id }}"
                    data-url="{{ url('/erp/adjustment-products/reject-products/return-to-warehouse/' . $record->id) }}"
                    data-total="{{ $record->quantity }}" data-action-type="return">
                    <i class="feather feather-corner-up-left me-3"></i>
                    <span>Return to Warehouse</span>
                </button>
            @endif
        </li>
    </ul>
</div>
