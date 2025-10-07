<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/suppliers/edit-supplier/{{ $supplier->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteSupplier"
                data-id="{{ $supplier->id }}"
                data-name="{{ $supplier->name }}"
                data-url="{{ url('/erp/suppliers/delete/' . $supplier->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>