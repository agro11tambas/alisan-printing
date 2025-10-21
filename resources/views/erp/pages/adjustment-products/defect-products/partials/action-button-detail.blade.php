<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if ($record->quantity > 0 && $record->status !== 'completed')
            <li>
                <button type="button" class="dropdown-item btn-return-supplier" data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus" data-id="{{ $record->id }}"
                    data-url="{{ url('/erp/adjustment-products/defect-products/return-to-supplier/' . $record->id) }}"
                    data-total="{{ $record->quantity }}" data-action-type="return"
                    data-supplier="{{ $record->supplier->name ?? '' }}">
                    <i class="feather feather-truck me-3"></i>
                    <span>Return to Supplier</span>
                </button>
            </li>
            <li>

                <button type="button" class="dropdown-item btn-eliminate" data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus" data-id="{{ $record->id }}"
                    data-url="{{ url('/erp/adjustment-products/defect-products/eliminate/' . $record->id) }}"
                    data-total="{{ $record->quantity }}" data-action-type="eliminate">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Eliminate Defect</span>
                </button>
            </li>
        @endif

        <li>
            <a href="{{ url('/erp/adjustment-products/defect-products/history/' . $record->id) }}"
                class="dropdown-item">
                <i class="feather feather-clock me-3"></i>
                <span>View History</span>
            </a>
        </li>
    </ul>
</div>
