{{-- 🔧 Action button versi lama: Add Assign masuk ke form assign Operator, bukan Mesin. --}}
<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @unless ($allCompleted)
            <li>
                <a class="dropdown-item" href="/erp/productions/waiting-list-old/add-assign/{{ $progress->id }}">
                    <i class="feather feather-user-plus me-3"></i>
                    <span>Add Assign</span>
                </a>
            </li>
        @else
            <li>
                <a href="javascript:void(0)" class="dropdown-item text-danger show-assign-error">
                    <i class="feather feather-user-plus me-3"></i>
                    <span>Add Assign</span>
                </a>
            </li>
        @endunless

        <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/history-order/{{ $progress->id }}">
                <i class="feather feather-info me-3"></i>
                <span>Progress & Info</span>
            </a>
        </li>
    </ul>
</div>
