<div class="hstack gap-2 justify-content-end">
    <div class="dropdown">
        <a href="javascript:void(0)" class="avatar-text avatar-md" data-bs-toggle="dropdown" data-bs-offset="0,21">
            <i class="feather feather-more-horizontal"></i>
        </a>
        <ul class="dropdown-menu">
            <!-- <li>
                <a class="dropdown-item" href="/warehouses/history-warehouse/{{ $warehouse->id }}">
                    <i class="feather feather-info me-3"></i>
                    <span>History</span>
                </a>
            </li> -->
            <li>
                <a class="dropdown-item" href="/erp/warehouses/edit-item/{{ $warehouse->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
            <li>
                <button type="button"
                    class="dropdown-item btn-delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDeleteItem"
                    data-id="{{ $warehouse->id }}"
                    data-name="{{ $warehouse->name }}"
                    data-url="{{ url('/erp/warehouses/delete/' . $warehouse->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        </ul>
    </div>
</div>