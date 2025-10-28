<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/customers/detail-customer/{{ $customer->id }}">
                <i class="feather feather-eye me-3"></i>
                <span>Detail</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="/erp/customers/edit-customer/{{ $customer->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteCustomer"
                data-id="{{ $customer->id }}"
                data-name="{{ $customer->name }}"
                data-url="{{ url('/erp/customers/delete/' . $customer->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>