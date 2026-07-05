@if ($product->trashed())
    <li>
        <form action="{{ route('erp.ecommerce-products.restore', $product->id) }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item">
                <i class="feather-rotate-ccw me-3"></i>
                <span>Restore</span>
            </button>
        </form>
    </li>
@else
    <li>
        <a class="dropdown-item" href="{{ route('erp.ecommerce-products.show', $product->id) }}">
            <i class="feather feather-eye me-3"></i>
            <span>Detail</span>
        </a>
    </li>
    <li>
        <a class="dropdown-item" href="{{ route('erp.ecommerce-products.edit', $product->id) }}">
            <i class="feather feather-edit-3 me-3"></i>
            <span>Edit</span>
        </a>
    </li>
    <li>
        <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
            data-bs-target="#modalDeleteProduct" data-id="{{ $product->id }}" data-name="{{ $product->title }}"
            data-url="{{ route('erp.ecommerce-products.destroy', $product->id) }}">
            <i class="feather feather-trash-2 me-3"></i>
            <span>Delete</span>
        </button>
    </li>
@endif
