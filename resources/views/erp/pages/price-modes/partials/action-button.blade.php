<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/products/price-modes/{{ $mode->id }}/edit">
                <i class="feather-edit-3 me-3"></i><span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeleteMode" data-name="{{ $mode->name }}"
                data-url="{{ url('/erp/products/price-modes/' . $mode->id) }}">
                <i class="feather-trash-2 me-3"></i><span>Delete</span>
            </button>
        </li>
    </ul>
</div>
