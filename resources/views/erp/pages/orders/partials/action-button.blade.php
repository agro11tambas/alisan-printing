<div class="hstack gap-2 justify-content-end">
    <a href="/erp/orders/add-progress-order/{{ $order->id }}" class="avatar-text avatar-md">
        <i class="feather feather-plus"></i>
    </a>
    <a href="/erp/orders/detail-order/{{ $order->id }}" class="avatar-text avatar-md">
        <i class="feather feather-eye"></i>
    </a>
    <div class="dropdown">
        <a href="javascript:void(0)" class="avatar-text avatar-md" data-bs-toggle="dropdown" data-bs-offset="0,21">
            <i class="feather feather-more-horizontal"></i>
        </a>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="/erp/orders/history-order/{{ $order->id }}">
                    <i class="feather feather-info me-3"></i>
                    <span>History</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/erp/orders/edit-order/{{ $order->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
            <li>
                <button type="button"
                    class="dropdown-item btn-delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDeleteOrder"
                    data-id="{{ $order->id }}"
                    data-name="{{ $order->name }}"
                    data-url="{{ url('/erp/orders/' . $order->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        </ul>
    </div>
</div>