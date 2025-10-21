<!-- <div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <button type="button"
                class="dropdown-item btn-verify"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $canceledProduct->id }}"
                data-name="{{ $canceledProduct->product->name }}"
                data-url="{{ url('/erp/adjustment-products/canceled-products/return-to-warehouse/' . $canceledProduct->id) }}"
                data-total="{{ $canceledProduct->canceled_product_stock }}">
                <i class="feather feather-check me-3"></i>
                <span>Return to Warehouse</span>
            </button>
        </li>
        <li>
            <a href="{{ url('/erp/adjustment-products/canceled-products/history/' . $canceledProduct->id) }}"
                class="dropdown-item">
                <i class="feather feather-clock me-3"></i>
                <span>History</span>
            </a>
        </li>
    </ul>
</div> -->

<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a href="{{ url('/erp/adjustment-products/canceled-products/detail/' . $canceledProduct->id) }}"
                class="dropdown-item">
                <i class="feather feather-eye me-3"></i>
                <span>Detail</span>
            </a>
        </li>
    </ul>
</div>