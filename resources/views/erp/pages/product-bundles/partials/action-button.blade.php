<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/products/product-bundles/edit-product-bundle/{{ $bundle->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteProduct"
                data-id="{{ $bundle->id }}"
                data-name="{{ $bundle->name }}"
                data-url="{{ url('/erp/products/product-bundles/delete/' . $bundle->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
