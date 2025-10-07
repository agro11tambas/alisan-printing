<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-opname/edit-stock-opname/{{ $stockOpname->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteStockOpname"
                data-id="{{ $stockOpname->id }}"
                data-name="{{ $stockOpname->name }}"
                data-url="{{ url('/erp/inventory/stock-opname/delete/' . $stockOpname->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>