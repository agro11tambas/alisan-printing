@if ($category->trashed())
    <li>
        <form action="{{ route('erp.ecommerce-product-categories.restore', $category->id) }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item">
                <i class="feather-rotate-ccw me-3"></i>
                <span>Restore</span>
            </button>
        </form>
    </li>
@else
    <li>
        <a class="dropdown-item" href="{{ route('erp.ecommerce-product-categories.edit', $category->id) }}">
            <i class="feather feather-edit-3 me-3"></i>
            <span>Edit</span>
        </a>
    </li>
    <li>
        <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
            data-bs-target="#modalDeleteCategory" data-id="{{ $category->id }}" data-name="{{ $category->name }}"
            data-url="{{ route('erp.ecommerce-product-categories.destroy', $category->id) }}">
            <i class="feather feather-trash-2 me-3"></i>
            <span>Delete</span>
        </button>
    </li>
@endif
