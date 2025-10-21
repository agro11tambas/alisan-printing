<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            @if($record->quantity > 0 && $record->status !== 'completed')
            <button type="button"
                class="dropdown-item btn-verify"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $record->id }}"
                data-url="{{ url('/erp/adjustment-products/canceled-products/return-to-warehouse/' . $record->id) }}"
                data-total="{{ $record->quantity }}"
                data-order-id="{{ $record->order_id }}"
                data-order-item-id="{{ $record->order_item_id }}"
                data-sale-return-id="{{ $record->sale_return_id }}"
                data-sale-return-item-id="{{ $record->sale_return_item_id }}">
                <i class="feather feather-check me-3"></i>
                <span>Return to Warehouse</span>
            </button>
            @endif
        </li>
    </ul>
</div>