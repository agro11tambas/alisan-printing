<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <!-- <li>
                <a href="/waiting-list/detail-order/{{ $progress->id }}" class="dropdown-item">
                    <i class="feather feather-eye"></i>
                    <span>Order Detail</span>
                </a>
            </li> -->
        <!-- <li>
                <button type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus"
                    data-id="{{ $progress->id }}"
                    data-name="{{ $progress->order_number }}"
                    data-url="{{ url('/erp/mark-as-complete-list/' . $progress->id) }}">
                    <i class="feather feather-check"></i>
                    <span>Mark as Complete</span>
                </button>
            </li> -->
        <!-- <li>
                <hr class="my-2">
            </li> -->
        <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/add-progress-order/{{ $progress->id }}">
                <i class="feather feather-plus me-3"></i>
                <span>Add Progress</span>
            </a>
        </li>
        <!-- <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/add-request-stocks/{{ $progress->id }}">
                <i class="feather feather-box me-3"></i>
                <span>Add Request Stocks</span>
            </a>
        </li> -->
        <!-- <li>
                <button type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus"
                    data-id="{{ $progress->id }}"
                    data-name="{{ $progress->order_number }}"
                    data-url="{{ url('/mark-as-delivery/' . $progress->id) }}">
                    <i class="feather feather-check"></i>
                    <span>Mark as Delivery</span>
                </button>
            </li> -->
        <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/history-order/{{ $progress->id }}">
                <i class="feather feather-info me-3"></i>
                <span>Progress & Info</span>
            </a>
        </li>
        <!-- <li>
                <hr class="my-2">
            </li>
            <li>
                <a class="dropdown-item" href="/edit-order/{{ $progress->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
            <li>
                <button type="button"
                    class="dropdown-item btn-delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDeleteOrder"
                    data-id="{{ $progress->id }}"
                    data-name="{{ $progress->name }}"
                    data-url="{{ url('/' . $progress->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li> -->
    </ul>
</div>